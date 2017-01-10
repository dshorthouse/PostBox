<?php

/**************************************************************************

File: postbox.Excel.classification.class.php

Description: This class validates all classification formats then pushes 
data into its parent postbox.meta.class.php class

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/

//load the conf
require_once (dirname(__FILE__) . '/conf/conf.php');

//load the meta class
require_once (dirname(__FILE__) . '/postbox.meta.class.php');

//load the vocabularies class
require_once (dirname(__FILE__) . '/postbox.vocabularies.class.php');

//load the names parsing class
require_once (dirname(__FILE__) . '/names.class.php');

//load the MySQL generator class
require_once (dirname(__FILE__) . '/postbox.mysql.class.php');

class PostBox_ExcelClassification extends PostBox_Meta {
    
    public static $_errors = array();
    
    /**
    * Constructor
    * @param obj $wk_classification
    */
    function __construct($wk_classification) {
        $this->valid = true;
        $this->wk_classification = $wk_classification;
        $this->acceptedHeaders = array();
        $this->rowindex = 0; //for use in formats other than parent-child
        $this->lastTaxonID = 0; //for use in parent-child
        $this->rowcount = 0;
        $this->maxcol = '';
        $this->colcount = 0;
        $this->header = array();
        $this->headerIntersect = array();
        $this->format = 'parent-child';
        $this->core = array();
        $this->synonymy = array();
        $this->vernaculars = array();
        $this->distribution = array();
        $this->MySQL = '';
    }
    
    function __destruct() {
        unset($this->wk_classification);
        unset($this->header);
        unset($this->headerIntersect);
        unset($this->core);
        unset($this->synonymy);
        unset($this->vernaculars);
        unset($this->distribution);
        unset($this->MySQL);
        unset($this->options);
    }

    /**
    * Parse the $wk_classification object & execute additional methods
    */
    public function parse() {
        
        $this->acceptedHeaders = array_keys(self::coreHeaders());
        
        $this->rowcount = $this->wk_classification->getHighestRow();
        $this->maxcol = $this->wk_classification->getHighestColumn();
        $this->colcount = PHPExcel_Cell::columnIndexFromString($this->maxcol);
        
        //special validation that merely checks if row count is below what we can safely accept
        if($this->rowcount > POSTBOX_CLASSIFICATION_ROWLIMIT) {
            $this->setError("Sorry, there is a limit of ".POSTBOX_CLASSIFICATION_ROWLIMIT." rows in your classification");
        }
        else {
            //set the header for use later
            $this->setHeader();

            //guess the file structure
            $this->guessFormat();

            if($this->validateClassification()) {

                //set core
                $this->setCore();

                //set synonyms
                $this->setSynonymy();
                
                //set vernaculars extension
                $this->setVernaculars();
                
                //set distribution extension
                $this->setDistribution();

                //set MySQL dump
                $this->setMySQL();

            }
            else {
                return false;
            }
        }
    }
    
    /**
    * Set the format of the $wk_classification object
    * @param string $format
    */
    public function setFormat($format) {
        $this->format = $format;
    }
    
    /**
    * Get the format of the $wk_classification object
    * @return string $this->format
    */
    private function getFormat() {
        return $this->format;
    }
    
    /**
    * Get the XML stream for the meta.xml file
    * @return xml stream
    */
    public function getRawMeta() {
        parent::generateXml(false);
        return parent::getRawXml();
    }
    
    /**
    * A mapping method to translate the header cell into DwC items
    * @param string $header
    * @return array $header (or string $item value)
    */
    public static function coreHeaders($header = '') {
        $item['taxonid'] = 'taxonID';
        $item['parent'] = 'parentNameUsageID';
        $item['taxon'] = 'scientificName';
        $item['scientificname'] = 'scientificName';
        $item['authorship'] = 'scientificNameAuthorship';
        $item['acceptedname'] = 'acceptedNameUsage';
        $item['originalname'] = 'originalNameUsage';
        $item['specificepithet'] = 'specificEpithet';
        $item['infraspecificepithet'] = 'infraspecificEpithet';
        $item['rank'] = 'taxonRank';  
        $item['nomenclaturalcode'] = 'nomenclaturalCode';
        $item['nomenclaturalstatus'] = 'nomenclaturalStatus';
        $item['taxonomicstatus'] = 'taxonomicStatus';
        $item['remarks'] = 'taxonRemarks';

        $item['nameaccordingto'] = 'nameAccordingTo';
        $item['establishmentmeans'] = 'establishmentMeans';        
        
        $header = strtolower(str_replace(" ", "", $header));
        
        if(!$header) {
            return $item;
        }
        elseif(array_key_exists($header, $item)) {
            return $item[$header];
        }
        else {
            return;
        }
    }
    
    /**
    * Set the header and headerIntersect object from the first row in the $wk_classification object for use later
    */
    private function setHeader() {
        foreach(PHPExcel_Cell::extractAllCellReferencesInRange('A1:' . $this->maxcol . '1') as $cell) {
            $this->header[$cell] = str_replace(" ", "", strtolower($this->wk_classification->getCell($cell)->getCalculatedValue()));
        }
        $this->headerIntersect = array_intersect($this->acceptedHeaders, $this->header);
    }

    /**
    * Guess and then set the format of the $wk_classification object
    */
    private function guessFormat() {
        if(!$this->headerIntersect) {
            $this->setError("This file cannot be accepted because it is missing columns included in: " . implode(', ', $this->acceptedHeaders) . " that permit automated format detection");
        }
        elseif(in_array('parent', $this->header)) {
            $this->setFormat('parent-child');   
        }
        elseif(array_intersect(PostBox_Vocabularies::$allRanks, $this->header)) {
            $this->setFormat('full-hierarchy-rank');
        }
        elseif(in_array('taxon1', $this->header)) {
            $this->setFormat('full-hierarchy-taxon');
        }
        else {
            $this->setError("This file cannot be accepted because it does not have a recognized structure.");
        }
    }
    
    /**
    * Validate the $wk_classification object by looking for required header cells, among other validation checks
    * @return true/false for valid or not valid, respectively
    */
    public function validateClassification() {

        //check to see if required columns "Authorship", "Synonyms", "Nomenclatural Code" columns are present
        if(!in_array('authorship', $this->header)) {
            $this->setError("The 'Authorship' column is missing in the Checklist sheet");
            $this->valid = false;
        }

        if(!in_array('synonyms', $this->header)) {
            $this->setError("The 'Synonyms' column is missing in the Checklist sheet");
            $this->valid = false;
        }

        if(!in_array('nomenclaturalcode', $this->header)) {
            $this->setError("The 'Nomenclatural Code' column is missing in the Checklist sheet");
            $this->valid = false;
        }
        
        //check to make sure all Nomenclatural Code cells are properly indicated if filled
        if(in_array('nomenclaturalcode', $this->header)) {
            $this->validateColumnContent("Nomenclatural Code", array_keys(PostBox_Vocabularies::$nomenclaturalCodes), true);
        }

        //check to make sure all Country cells are properly indicated if filled
        if(in_array('countries', $this->header)) {
            $this->validateColumnMultipleContent("Countries", array_keys(PostBox_Vocabularies::$countries));
        }

        //check State/Province column
        if(in_array('stateprovinces', $this->header)) {
            $this->validateColumnMultipleContent("State Provinces", array_keys(PostBox_Vocabularies::$stateProvinces));
        }

        //check Establishment Means column
        if(in_array('establishmentmeans', $this->header)) {
            $this->validateColumnContent("Establishment Means", PostBox_Vocabularies::$establishmentMeans);
        }

        //check the occurrence status column
        if(in_array('occurrencestatus', $this->header)) {
            $this->validateColumnContent("Occurrence Status", PostBox_Vocabularies::$occurrenceStatus);
        }

        //check the threat status column
        if(in_array('threatstatus', $this->header)) {
            $this->validateColumnContent("Threat Status", array_keys(PostBox_Vocabularies::$threatStatus));
        }

        if(in_array('taxonomicstatus', $this->header)) {
            $this->validateColumnContent("Taxonomic Status", PostBox_Vocabularies::$taxonomicStatus);
        }


        /*
         * Non-core controlled vocabularies beyond this point
         */

        //check Terrestrial Ecozone column
        if(in_array('terrestrialecozone', $this->header)) {
            $this->validateColumnContent("Terrestrial Ecozone", PostBox_Vocabularies::$terrestrialEcozones);
        }

        //check Canadian Ecozone column
        if(in_array('canadianecozone', $this->header)) {
            $this->validateColumnContent("Canadian Ecozone", PostBox_Vocabularies::$canadianEcozones);
        }
        
        //check Canadian Ecoregion column
        if(in_array('canadianecoregion', $this->header)) {
            $this->validateColumnContent("Canadian Ecoregion", PostBox_Vocabularies::$canadianEcoregions);
        }

        //check Canadian Ecoprovince column
        if(in_array('canadianecoprovince', $this->header)) {
            $this->validateColumnContent("Canadian Ecoprovince", PostBox_Vocabularies::$canadianEcoprovinces);
        }

        //check Canadian Ecodistrict column
        if(in_array('canadianecodistrict', $this->header)) {
            $this->validateColumnContent("Canadian Ecodistrict", PostBox_Vocabularies::$canadianEcodistricts);
        }

        if($this->valid) {
        
            switch($this->getFormat()) {
                case 'parent-child':
                    //MUST have a 'taxonID' column
                    if(!in_array('taxonid', $this->header)) {
                        $this->setError("The 'taxonID' column is missing in the Checklist sheet");
                        $this->valid = false;
                    }
                    //MUST have a 'Parent' column
                    if(!in_array('parent', $this->header)) {
                        $this->setError("The 'Parent' column is missing in the Checklist sheet");
                        $this->valid = false;
                    }
                    //MUST have a 'Taxon' column
                    if(!in_array('taxon', $this->header)) {
                        $this->setError("The 'Taxon' column is missing in the Checklist sheet");
                        $this->valid = false;
                    }
                    //MUST have a 'Rank' column
                    if(!in_array('rank', $this->header)) {
                        $this->setError("The 'Rank' column is missing in the Checklist sheet");
                        $this->valid = false;
                    }
                    //MUST NOT have a 'Taxon(int)' column header
                    foreach($this->header as $header) {
                        if(is_numeric(str_replace("taxon","",$header))) {
                            $this->setError("A 'TaxonX' column where 'X' is an integer should not be included in the parent-child format");
                            $this->valid = false;
                        }
                    }
                    //check to make sure that all Child and Parent cells are filled (zero for Parent is an exception)
                    $counter = 0;
                    foreach(PHPExcel_Cell::extractAllCellReferencesInRange('A2:B'.$this->rowcount) as $taxonCell) {
                        if(!$this->wk_classification->getCell($taxonCell)->getCalculatedValue() && $this->wk_classification->getCell($taxonCell)->getCalculatedValue() != 0) {
                            $col = $this->wk_classification->getCell($taxonCell)->getColumn();
                            $this->setError($this->wk_classification->getCell($col.'1')->getCalculatedValue() . " cell " . $taxonCell . " is missing a value");
                            $this->valid = false;
                        }
                        
                        $counter++;
                    }
                
                break;
            
                case 'full-hierarchy-taxon':
                    //MUST have a 'Taxon1' column
                    if(!in_array('taxon1', $this->header)) {
                        $this->setError("The 'Taxon1' column is missing in the Checklist sheet");
                        $this->valid = false;
                    }
                    //MUST have a 'rank' column
                    if(!in_array('rank', $this->header)) {
                        $this->setError("The 'Rank' column is missing in the Checklist sheet");
                        $this->valid = false;
                    }
                    //MUST NOT have a parent column
                    if(in_array('parent', $this->header)) {
                        $this->setError("A 'Parent' column should not be included in this format");
                        $this->valid = false;
                    }
                break;
            
                case 'full-hierarchy-rank':
                    //MUST NOT have a parent or a child column
                    if(in_array('parent', $this->header)) {
                        $this->setError("A 'Parent' column should not be included in this format");
                        $this->valid = false;
                    }
                    //MUST NOT have a 'Taxon(int)' column header
                    foreach($this->header as $header) {
                        if(is_numeric(str_replace("taxon","",$header))) {
                            $this->setError("A 'TaxonX' column where 'X' is an integer should not be included in this format");
                            $this->valid = false;
                        }
                    }
                break;
            }
        }
        
        return $this->valid;
    }
    
    /**
    * Validate column content using controlled vocabularies
    * @param string $column
    * @param array $vocabulary
    * @param boolean $include
    */ 
    private function validateColumnContent($column, $vocabulary, $include = false) {
        $column_search = strtolower(str_replace(" ", "", $column));
        $col = $this->wk_classification->getCell(array_search($column_search, $this->header))->getColumn();
        foreach(PHPExcel_Cell::extractAllCellReferencesInRange($col.'2:'.$col.$this->rowcount) as $nomCode) {
            $value = $this->wk_classification->getCell($nomCode)->getCalculatedValue();
            if($value) {
                if(!in_array($value, $vocabulary)) {
                    $error_message = $column . " in cell " . $nomCode . " has an unrecognized value, <em>" . $value . "</em> in the Checklist sheet.";
                    if($include) {
                      $error_message .= " Acceptable options are " . implode(", ", $vocabulary);
                    }
                    $this->setError($error_message);
                    $this->valid = false;
                }
            }
        }
    }

    /**
    * Validate column  multiple content using controlled vocabularies
    * @param string $column
    * @param array $vocabulary
    * @param boolean $include
    */ 
    private function validateColumnMultipleContent($column, $vocabulary, $include = false) {
        $column_search = strtolower(str_replace(" ", "", $column));
        $col = $this->wk_classification->getCell(array_search($column_search, $this->header))->getColumn();
        $error_messge = '';
        foreach(PHPExcel_Cell::extractAllCellReferencesInRange($col.'2:'.$col.$this->rowcount) as $nomCode) {
            $val = $this->wk_classification->getCell($nomCode)->getCalculatedValue();
            if($val) {
                $val_array = preg_split("/[\s\|,;]+/",$val);
                foreach($val_array as $value) {
                    if(!in_array($value, $vocabulary)) {
                        $this->valid = false;
                    }
                }
                if(!$this->valid) {
                    $error_message = $column . " in cell " . $nomCode . " has an unrecognized value, <em>" . $value . "</em> in the Checklist sheet.";
                    if($include) {
                      $error_message .= " Acceptable options are " . implode(", ", $vocabulary);
                    }
                    $this->setError($error_message);
                }
            }
        }
    }
    
    /**
    * Create the core object for eventual dump into the core csv
    * @return obj $this->core
    */
    private function setCore() {
        
        //parent-child variables
        $taxonIDCol = (array_search('taxonid', $this->header)) ? $this->wk_classification->getCell(array_search('taxonid', $this->header))->getColumn() : '';
        $this->lastTaxonID = ($taxonIDCol) ? $this->wk_classification->getCell($taxonIDCol.$this->rowcount)->getCalculatedValue() : '';

        //find first instance of a column whose rows contain taxon names (e.g. Taxon1 or Family)
        switch($this->getFormat()) {
          case 'full-hierarchy-rank':
            foreach ($this->header as $key => $header) {
              if(in_array($header, PostBox_Vocabularies::$allRanks)) {
                $firstTaxonCol = $this->wk_classification->getCell($key)->getColumn();
                break;
              }
            }
          break;
    
          case 'full-hierarchy-taxon':
            $firstTaxonCol = $this->wk_classification->getCell(array_search('taxon1', $this->header))->getColumn();
          break;
        }

        //find the 'Authorship' column that signals the end of the relevant taxon columns
        $authorCol = PHPExcel_Cell::columnIndexFromString($this->wk_classification->getCell(array_search('authorship', $this->header))->getColumn());
        //find the 'NomenclaturalCode' column for use when parents need to be added
        $nomCodeCol = $this->wk_classification->getCell(array_search('nomenclaturalcode', $this->header))->getColumn();
        $missing_parent_errors = array();
        $counter = 0;
        foreach ($this->wk_classification->getRowIterator() as $row) {
            $taxaCells = array();
            $taxa = array();
            $this->rowindex = $row->getRowIndex();
            if($this->rowindex > 1) {
                $nomenclaturalCode = $this->wk_classification->getCell($nomCodeCol . $this->rowindex)->getCalculatedValue();
                switch($this->getFormat()) {
                    case 'parent-child':
                        $taxonID = $this->wk_classification->getCell($taxonIDCol . $this->rowindex)->getCalculatedValue();
                    break;
                    
                    default:
                        $taxaKey = $this->rowindex;
                        $taxaCells = PHPExcel_Cell::extractAllCellReferencesInRange($firstTaxonCol.$this->rowindex.':'.PHPExcel_Cell::stringFromColumnIndex($authorCol-2).$this->rowindex);
                        foreach($taxaCells as $taxaCell) {
                            $value = $this->wk_classification->getCell($taxaCell)->getCalculatedValue();
                            $taxa[$taxaCell] = ($value) ? $value : "";
                        }
                        $taxaKey = $this->getTreePath($taxa);
                        $scientificName = $this->getScientificName($taxa);
                        $taxonRank = $this->getTaxonRank($taxa);

                        // blank out core values to preserve order of array elements
                        foreach(PostBox_Vocabularies::$coreTerms as $term) {
                          $this->core[$taxaKey][$term] = '';
                        }

                        // Now flesh-out contents of elements
                        $this->core[$taxaKey]['taxonID'] = $this->rowindex;
                        $this->core[$taxaKey]['scientificName'] = $scientificName;

                        if($this->getParent($taxaKey) == 'root') {
                            $this->core[$taxaKey]['parentNameUsageID'] = 0;
                        }
                        elseif(!isset($this->core[$this->getParent($taxaKey)])) {
                            $taxaRanks = ($this->getFormat() == 'full-hierarchy-rank') ? $this->getTaxaRanks($taxa) : "";
                            $this->addMissingParents($taxaKey, $nomenclaturalCode, $taxaRanks);
                            $this->core[$taxaKey]['parentNameUsageID'] = $this->core[$this->getParent($taxaKey)]['taxonID'];
                        }
                        else {
                            $this->core[$taxaKey]['parentNameUsageID'] = $this->core[$this->getParent($taxaKey)]['taxonID'];
                        }

                        if($this->getFormat() == 'full-hierarchy-rank') {
                            $this->core[$taxaKey]['taxonRank'] = $taxonRank;
                        }
                }

                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                foreach($cellIterator as $cell) {
                    $header = strtolower(str_replace(" ", "", $this->wk_classification->getCell($cell->getColumn() . '1')->getCalculatedValue()));
                    if($header != "") {
                        switch($this->getFormat()) {
                            case 'parent-child':
                                if(self::coreHeaders($header)) {
                                    if($header == 'parent' && $cell->getCalculatedValue() == "") {
                                        $this->core[$taxonID][self::coreHeaders($header)] = 0;
                                    }
                                    else {
                                        $this->core[$taxonID][self::coreHeaders($header)] = $cell->getCalculatedValue();
                                    }
                                }
                                elseif($header == 'synonyms' && $cell->getCalculatedValue()){
                                    $this->synonymy[$taxonID] = array(
                                      'taxa'              => $cell->getCalculatedValue(),
                                      'nomenclaturalCode' => $nomenclaturalCode
                                    );
                                }
                                elseif($header == 'commonnames' && $cell->getCalculatedValue()) {
                                    $this->vernaculars[$taxonID] = $cell->getCalculatedValue();
                                }
                                elseif($header == 'countries' && $cell->getCalculatedValue()) {
                                    $this->distribution[$taxonID] = $cell->getCalculatedValue();
                                }
                                // set unknown columns into the core
                                elseif(!self::coreHeaders($header) && $header != 'synonyms' && $header != 'commonnames' && $header != 'countries') {
                                    $this->core[$taxonID][$header] = ($cell->getCalculatedValue()) ? $cell->getCalculatedValue() : "";
                                }
                                else {}
                            break;
                            
                            default:

                                if(self::coreHeaders($header)) {
                                    $this->core[$taxaKey][self::coreHeaders($header)] = $cell->getCalculatedValue();
                                }
                                elseif($header == 'synonyms' && $cell->getCalculatedValue()) {
                                    $this->synonymy[$this->rowindex] = array(
                                      'taxa'              => $cell->getCalculatedValue(),
                                      'nomenclaturalCode' => $nomenclaturalCode
                                    );
                                }
                                elseif($header == 'commonnames' && $cell->getCalculatedValue()) {
                                    $this->vernaculars[$this->rowindex] = $cell->getCalculatedValue();
                                }
                                elseif($header == 'countries' && $cell->getCalculatedValue()) {
                                    $this->distribution[$this->rowindex] = $cell->getCalculatedValue();
                                }
                                // set unknown columns into the core
                                elseif(!is_numeric(str_replace("taxon","",$header)) && !in_array($header, PostBox_Vocabularies::$allRanks) && !self::coreHeaders($header) && $header != 'synonyms' && $header != 'commonnames' && $header != 'countries') {
                                    $this->core[$taxaKey][$header] = ($cell->getCalculatedValue()) ? $cell->getCalculatedValue() : "";
                                }
                                else {}
                        }
                    }
                }
            }
            
            $counter++;
        }
        
        if($missing_parent_errors) {
            foreach($missing_parent_errors as $error) {
                $this->setError($error);
            }
        }
        
        //set the headers for the core file
        parent::setCoreHeaders(array_keys(max($this->core)));

    }
    
    /**
    * Get the core object for eventual write into a DwC core
    * @return obj $this->core
    */
    public function getCore() {
        return $this->core;
    }

    /**
    * Add missing parents if not found in treepath
    * @param string $taxaKey
    * @param string $nomenclatural_code
    * @param string $taxaRanks
    */
    private function addMissingParents($taxaKey, $nomenclaturalCode = "", $taxaRanks = "") {
        $taxa = explode("|", $taxaKey);
        $ranks = ($taxaRanks) ? explode("|", $taxaRanks) : array();
        $parent = array();
        $parent_old = array();
        $taxa_cnt = count($taxa);
        for($i=0; $i<$taxa_cnt; $i++) {
            $taxon = array_shift($taxa);
            $parent = implode("|", $parent_old);
            array_push($parent_old, $taxon);
            $key = implode("|", $parent_old);
            if(!isset($this->core[$key]) && $taxon) {
                $data = array(
                  'taxonID' => ++$this->rowcount,
                  'parentNameUsageID' => (!isset($this->core[$parent]['taxonID'])) ? 0 : $this->core[$parent]['taxonID'],
                  'scientificName' => Names::canonical_form($taxon),
                  'scientificNameAuthorship' => trim(str_replace(Names::canonical_form($taxon), "", $taxon)),
                  'taxonRank' => ($ranks) ? $ranks[$i] : "",
                  'nomenclaturalCode' => $nomenclaturalCode
                );
               $this->core[$key] = array_merge(PostBox_Vocabularies::getBlankCoreTerms(), $data);
            }
        }
    }
    
    /**
    * Set the synonym object for eventual dump into synonym csv extension
    * @return obj $this->synonymy
    */
    private function setSynonymy() {
        if(!$this->core) return;

        if($this->synonymy) {
	        $taxonID = $this->rowcount;
            $coreHeaders = array_keys(max($this->core));
            foreach($this->synonymy as $key => $data) {
                $allSynonyms = explode('|', $data['taxa']);
                foreach($allSynonyms as $synonym) {
                    unset($this->synonymy[$key]);
                    ++$taxonID;
                    $this->synonymy[] = array(
                        'taxonID'                   => $taxonID,
                        'parentNameUsageID'         => $key,
                        'acceptedNameUsageID'       => $key,
                        'scientificName'            => Names::canonical_form($synonym),
                        'scientificNameAuthorship'  => trim(str_replace(Names::canonical_form($synonym), "", $synonym)),
                        'nomenclaturalCode'         => $data['nomenclaturalCode'],
                        'taxonomicStatus'           => 'synonym',
                    );
                    if(!parent::getOption('make-synonymy-file')) {
                        //retain the order of the core headers
                        foreach($coreHeaders as $coreHeader) {
                            $this->core[$taxonID][$coreHeader] = "";
                        }
                        //add the additional headers we need
                        $this->core[$taxonID]['parentNameUsageID'] = "";
                        $this->core[$taxonID]['acceptedNameUsageID'] = "";
                        $this->core[$taxonID]['nomenclaturalCode'] = "";
                        $this->core[$taxonID]['taxonomicStatus'] = "";
                        
                        //now overwrite the values
                        $this->core[$taxonID]['taxonID'] = $taxonID;
                        $this->core[$taxonID]['parentNameUsageID'] = $key;
                        $this->core[$taxonID]['scientificName'] = Names::canonical_form($synonym);
                        $this->core[$taxonID]['scientificNameAuthorship'] = trim(str_replace(Names::canonical_form($synonym), "", $synonym));
                        $this->core[$taxonID]['nomenclaturalCode'] = $data['nomenclaturalCode'];
                        $this->core[$taxonID]['acceptedNameUsageID'] = $key;
                        $this->core[$taxonID]['taxonomicStatus'] = 'synonym';
                    }
                }
            }

            //set the headers for the synonymy (used in MySQL dump)
            parent::setSynonymyHeaders(array_keys(current($this->synonymy)));

        }
    }
    
    /**
    * Get the synonymy object for eventual write into a DwC synonymy extension
    * @return obj $this->synonymy
    */
    public function getSynonymy() {
        return $this->synonymy;
    }
    
    /**
    * Set the vernaculars object for eventual dump into vernaculars csv extension
    * @return obj $this->vernaculars
    */
    private function setVernaculars() {
        if(!$this->core) return;
        
        if($this->vernaculars) {
            foreach($this->vernaculars as $key => $data) {
                $allVernaculars = explode('|', $data);
                foreach($allVernaculars as $vernacular) {
                    unset($this->vernaculars[$key]);
                    $this->vernaculars[] = array(
                        'taxonID'           => $key,
                        'vernacularName'    => trim($vernacular), 
                    );
                }
            }

            //set the extension in the meta file
            parent::setVernacularsHeaders(array_keys(current($this->vernaculars)));
        }
    }
    
    /**
    * Get the vernacular object for eventual write into a DwC vernacular extension
    * @return obj $this->vernaculars
    */
    public function getVernaculars() {
        return $this->vernaculars;
    }
    
    /**
    * TODO: occurrenceStatus & establishMeans should be added if present.
    * See http://rs.gbif.org/extension/gbif/1.0/distribution.xml
    *
    * Set the distribution object for eventual dump into distribution csv extension
    * @return obj $this->distribution
    */
    private function setDistribution() {
        if(!$this->core) return;
        
        if($this->distribution) {
            foreach($this->distribution as $key => $data) {
                $allCountries = explode('|', $data);
                foreach($allCountries as $country) {
                    $locality = $this->getLocalities(trim($country));
                    unset($this->distribution[$key]);
                    $this->distribution[] = array(
                        'taxonID'            => $key,
                        'locality'           => ($locality['locality']) ? $locality['locality'] : '',
                        'countryCode'        => ($locality['countryCode']) ? $locality['countryCode'] : '',
                        'occurrenceStatus'   => '',
                        'establishmentMeans' => ''
                    );
                }
            }

            //set the extension in the meta file
            parent::setDistributionHeaders(array_keys(current($this->distribution)));
        }
    }
    
    /**
    * Get the distribution object for eventual write into a DwC distribution extension
    * @return obj $this->distribution
    */
    public function getDistribution() {
        return $this->distribution;
    }
    
    /**
    * Set the the MySQL object for eventual write into a dump file
    */
    private function setMySQL() {
        $this->MySQL = new PostBox_MySQL;
        $this->MySQL->createHeader();
        $fields = array();
        foreach(parent::getCoreHeaders() as $field) {
            switch($field) {
                case 'taxonID':
                    $fields[$field] = "int(11) NOT NULL";
                break;
                
                case 'parentNameUsageID':
                    $fields[$field] = "int(11) NOT NULL";
                break;
                
                case 'taxonRemarks':
                    $fields[$field] = "text";
                break;
                
                default:
                    $fields[$field] = "varchar(255) DEFAULT NULL";
            }
        }
        $this->MySQL->createTable('taxa', $fields, array('primary' => 'taxonID'));
        foreach($this->getCore() as $taxonid => $values) {
            $this->MySQL->insertData('taxa', $values);
        }
        
        //synonymy
        if(parent::getOption('make-synonymy-file') && $this->getSynonymy()) $this->setMySQLExtension('synonymy', parent::getSynonymyHeaders(), $this->getSynonymy());
        
        //vernaculars
        if($this->getVernaculars()) $this->setMySQLExtension('vernacular', parent::getVernacularsHeaders(), $this->getVernaculars());
        
        //distribution
        if($this->getDistribution())  $this->setMySQLExtension('distribution', parent::getDistributionHeaders(), $this->getDistribution());

    }
    
    /**
    * Get the MySQL object for eventual write into an optional dump file
    * @return obj
    */
    public function getMySQL() {
        return $this->MySQL->getStructure();
    }
    
    /**
    * Helper function to create a MySQL table in a dump file for an DwC-A extension
    * @param string $tableName
    * @param array $headers (field)
    * @param array $data
    */
    private function setMySQLExtension($tableName = '', $headers = array(), $data = array()) {
        foreach($headers as $field) {
            switch($field) {
                case 'taxonID':
                    $fields[$field] = "int(11) NOT NULL";
                break;

                default:
                    $fields[$field] = "varchar(255) DEFAULT NULL";
            }
        }
        $this->MySQL->createTable($tableName, $fields, array());
        foreach($data as $values) {
            $this->MySQL->insertData($tableName, $values);
        }
    }
    
    /**
    * Helper function to get the treepath from memory for a taxon
    * @return string pipe-separated treepath
    */
    private function getTreePath($taxa = array()) {
        if(!$taxa) return;

        foreach($taxa as $key => $value) {
            if(!$value) $taxa[$key] = 'unassigned';
        }

        return implode('|', $taxa);
    }
    
    /**
    * Helper function to get the terminal taxon name from an array of taxon names
    * @param array $taxa
    * @return array terminal taxon
    */
    private function getScientificName($taxa = array()) {
        if(!$taxa) return;
        $cleaned = array_filter($taxa);
        return array_pop($cleaned);
    }
    
    /**
    * Helper function to get the parental treepath for a taxon
    * @param string $taxa
    * @return string treepath
    */
    private function getParent($taxa = "") {
        $taxa = explode("|", $taxa);
        if(count($taxa) == 1) return 'root';
        array_pop($taxa);
        return implode("|", $taxa); 
    }
    
    /**
    * Helper function to get the rank (if recognized in static array) of a terminal taxon from an array of taxa
    * @param array $taxa
    * @return string rank
    */
    private function getTaxonRank($taxa = array()) {
        if(!$taxa) return;
        $cleaned = array_filter($taxa);
        end($cleaned);
        list($key) = each($cleaned);
        $header = strtolower(str_replace(" ", "", $this->wk_classification->getCell($this->wk_classification->getCell($key)->getColumn() . '1')->getCalculatedValue()));
        if(in_array($header, PostBox_Vocabularies::$allRanks)) {
            return $header;
        }
        return;
    }

    /**
    * Helper function to get ranks of all taxa cells in a row when format is full-hierarchy-rank
    * @param array $taxa
    * @return string $ranks
    */
    private function getTaxaRanks($taxa = array()) {
      if(!$taxa) return;
      $ranks = array();
      foreach($taxa as $key => $taxon) {
        $header = strtolower(str_replace(" ", "", $this->wk_classification->getCell($this->wk_classification->getCell($key)->getColumn() . '1')->getCalculatedValue()));
        $ranks[] = (in_array($header, PostBox_Vocabularies::$allRanks)) ? $header : "";
      }
      return implode("|", $ranks); 
    }
    
    /**
    * Retrieve ISO Country Code and/or Country name
    * @param string $loc
    * @return array locality & countryCode
    */ 
    private function getLocalities($loc) {
        $locality = array();
        //assume a small $loc refers to a country code
        if(strlen($loc) == 2) {
            $locality['locality'] = (PostBox_Vocabularies::getCountryName($loc)) ? PostBox_Vocabularies::getCountryName($loc) : '';
            $locality['countryCode'] = (array_key_exists($loc, PostBox_Vocabularies::$countries)) ? $loc : '';
        }
        else {
            $locality['locality'] = $loc;
            $locality['countryCode'] = (PostBox_Vocabularies::getCountryCode($loc)) ? PostBox_Vocabularies::getCountryCode($loc) : '';
        }
        return $locality;
    }
    
    /**
    * Set an error into the error static array
    * @param string $message
    */
    private function setError($message) {
        self::$_errors[] = $message; 
    }
    
    /**
    * Get error static variable
    */
    public static function getErrors() {
        return self::$_errors;
    }
    
}

?>