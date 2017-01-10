<?php

/**************************************************************************

File: postbox.eml.class.php

Description: This class produces an XML stream for an eml.xml file as part 
of a Darwin Core Archive. It is extended by postbox.Excel.metadata.class.php

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/

class PostBox_Eml {
    
    private $_xml = '';
    private $_basicMetaData = array();
    private $_contactAuthors = array();
    private $_metadataAuthors = array();
    private $_resourceAuthors = array();
    private $_bibliography = array();
    private $_keyWords = array();
    private $_keyWordThesaurus = '';
    private $_boundingBox = array();
    private $_dateCoverage = array();
    private $_generalTaxonomicCoverage = '';

    public function __construct() {
    }

    /**
    * Generate XML for DwC-A eml file
    * @param true/false $header
    */
    public function generateXml($header = true) {
        $this->_xml = new XMLWriter();
        
        if ($header) {
            header("content-type: text/xml");
            $this->_xml->openURI('php://output');
        }
        else {
            $this->_xml->openMemory();
        }
        
        $this->_xml->startDocument('1.0', 'UTF-8');
        $this->_xml->setIndent(4);
        $this->_xml->startElement('eml:eml');
            $this->_xml->writeAttribute('packageId', 'eml.1.1');
            $this->_xml->writeAttribute('system', 'knb');
            $this->_xml->writeAttribute('xmlns:eml', 'eml://ecoinformatics.org/eml-2.1.0');
            $this->_xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $this->_xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/terms/');
            $this->_xml->writeAttribute('xsi:schemaLocation', 'eml://ecoinformatics.org/eml-2.1.0 eml.xsd');
            
            $this->_xml->startElement('dataset');
            
            /************************************************
            * Identifier REQUIRED
            ************************************************/
            $this->_xml->writeElement('alternateIdentifier', $this->getBasicMetaData('uuid'));
            
            /************************************************
            * Title REQUIRED
            ************************************************/
            $this->_xml->writeElement('title', $this->getBasicMetaData('title'));
            
            /************************************************
            * Creator(s) (aka "Authors")
            ************************************************/
            foreach($this->getResourceAuthors() as $authorID => $value) {
                $this->_xml->startElement('creator');
                $this->_xml->writeAttribute('id', $this->getResourceAuthor($authorID,'creatorID'));
                $this->_xml->writeAttribute('scope', 'document');
                $this->_xml->startElement('individualName');
                $this->_xml->writeElement('givenName', $this->getResourceAuthor($authorID, 'givenName'));
                $this->_xml->writeElement('surName', $this->getResourceAuthor($authorID, 'surName'));
                $this->_xml->endElement(); //end individualName
                $this->_xml->startElement('address');
                $this->_xml->writeElement('city', $this->getResourceAuthor($authorID, 'city'));
                $this->_xml->writeElement('administrativeArea', $this->getResourceAuthor($authorID, 'administrativeArea'));
                $this->_xml->writeElement('postalCode', $this->getResourceAuthor($authorID, 'postalCode'));
                $this->_xml->writeElement('country', $this->getResourceAuthor($authorID, 'country'));
                $this->_xml->endElement(); //end address
                $this->_xml->writeElement('phone', $this->getResourceAuthor($authorID, 'phone'));
                $this->_xml->writeElement('electronicMailAddress', $this->getResourceAuthor($authorID, 'electronicMailAddress'));
                $this->_xml->writeElement('onlineUrl', $this->getResourceAuthor($authorID, 'onlineUrl'));
                $this->_xml->endElement(); //end creator    
            }
            
            /************************************************
            * Metadata Provider(s) (aka "Metadata Author")
            ************************************************/
            foreach($this->getMetaDataAuthors() as $authorID => $value) {
                $this->_xml->startElement('metadataProvider');
                $this->_xml->startElement('individualName');
                $this->_xml->writeElement('givenName', $this->getMetaDataAuthor($authorID, 'givenName'));
                $this->_xml->writeElement('surName', $this->getMetaDataAuthor($authorID, 'surName'));
                $this->_xml->endElement(); //end individualName
                $this->_xml->startElement('address');
                $this->_xml->writeElement('city', $this->getMetaDataAuthor($authorID, 'city'));
                $this->_xml->writeElement('administrativeArea', $this->getMetaDataAuthor($authorID, 'administrativeArea'));
                $this->_xml->writeElement('postalCode', $this->getMetaDataAuthor($authorID, 'postalCode'));
                $this->_xml->writeElement('country', $this->getMetaDataAuthor($authorID, 'country'));
                $this->_xml->endElement(); //end address
                $this->_xml->writeElement('phone', $this->getMetaDataAuthor($authorID, 'phone'));
                $this->_xml->writeElement('electronicMailAddress', $this->getMetaDataAuthor($authorID, 'electronicMailAddress'));
                $this->_xml->writeElement('onlineUrl', $this->getMetaDataAuthor($authorID, 'onlineUrl'));
                $this->_xml->endElement(); //end metadataProvider
            }
            
            /************************************************
            * Publication Date REQUIRED
            ************************************************/
            $this->_xml->writeElement('pubDate', $this->getBasicMetaData('pubDate'));
            
            /************************************************
            * Resource Language REQUIRED
            ************************************************/
            $this->_xml->writeElement('language', $this->getBasicMetadata('resourceLanguage'));
            
            /************************************************
            * Abstract REQUIRED
            ************************************************/
            $this->_xml->startElement('abstract');
            $this->_xml->writeElement('para', $this->getBasicMetaData('abstract'));
            $this->_xml->endElement(); //end abstract
            
            /************************************************
            * Keywords
            ************************************************/
            if($this->getKeyWords()) {
                $this->_xml->startElement('keywordSet');
                foreach($this->getKeyWords() as $keyword) {
                    $this->_xml->writeElement('keyword', $keyword);
                }
                if($this->getKeyWordThesaurus()) $this->_xml->writeElement('keywordThesaurus', $this->getKeyWordThesaurus());
                $this->_xml->endElement(); //end keywordSet
            }
            
            /************************************************
            * Additional Information
            ************************************************/
            if($this->getBasicMetadata('additionalInfo')) {
                $this->_xml->startElement('additionalInfo');
                $this->_xml->writeElement('para', $this->getBasicMetadata('additionalInfo'));
                $this->_xml->endElement(); //end additionalInfo
            }
            
            /************************************************
            * Intellectual Rights REQUIRED
            ************************************************/
            $this->_xml->startElement('intellectualRights');
            $this->_xml->writeElement('para', $this->getBasicMetaData('intellectualRights'));
            $this->_xml->endElement(); //end intellectualRights
            
            /************************************************
            * Coverage: Geographic, Temporal
            ************************************************/
            if($this->getBoundingBox('minx') || $this->getDateCoverage('begin') || $this->getDateCoverage('single')) {
                $this->_xml->startElement('coverage');
                    if($this->getBoundingBox('minx')) {
                        $this->_xml->startElement('geographicCoverage');
                            $this->_xml->writeElement('geographicDescription', 'Bounding Box');
                            $this->_xml->startElement('boundingCoordinates');
                            $this->_xml->writeElement('westBoundingCoordinate', $this->getBoundingBox('minx'));
                            $this->_xml->writeElement('eastBoundingCoordinate', $this->getBoundingBox('maxx'));
                            $this->_xml->writeElement('northBoundingCoordinate', $this->getBoundingBox('maxy'));
                            $this->_xml->writeElement('southBoundingCoordinate', $this->getBoundingBox('miny'));
                            $this->_xml->endElement(); //end boundingCoordinates
                        $this->_xml->endElement(); //end geographicCoverage
                    }
                    if($this->getDateCoverage('begin') && $this->getDateCoverage('end')) {
                        $this->_xml->startElement('temporalCoverage');
                            $this->_xml->startElement('rangeOfDates');
                            $this->_xml->startElement('beginDate');
                            $this->_xml->writeElement('calendarDate', $this->getDateCoverage('begin'));
                            $this->_xml->endElement(); //end beginDate
                            $this->_xml->startElement('endDate');
                            $this->_xml->writeElement('calendarDate', $this->getDateCoverage('end'));
                            $this->_xml->endElement(); //end endDate
                            $this->_xml->endElement(); //end rangeOfDates
                        $this->_xml->endElement(); //end temporalCoverage
                    }
                    elseif($this->getDateCoverage('single')) {
                        $this->_xml->startElement('temporalCoverage');
                            $this->_xml->startElement('singleDateTime');
                            $this->_xml->writeElement('calendarDate', $this->getDateCoverage('single'));
                            $this->_xml->endElement(); //end singleDateTime
                        $this->_xml->endElement(); //end temporalCoverage
                    }
                    else {}
                $this->_xml->endElement(); //end coverage
            }
            
            /************************************************
            * Contact(s) REQUIRED
            ************************************************/
            foreach($this->getContactAuthors() as $authorID => $value) {
                $this->_xml->startElement('contact');
                $this->_xml->startElement('individualName');
                $this->_xml->writeElement('givenName', $this->getContact($authorID, 'givenName'));
                $this->_xml->writeElement('surName', $this->getContact($authorID, 'surName'));
                $this->_xml->endElement(); //end individualName
                $this->_xml->startElement('address');
                $this->_xml->writeElement('city', $this->getContact($authorID, 'city'));
                $this->_xml->writeElement('administrativeArea', $this->getContact($authorID, 'administrativeArea'));
                $this->_xml->writeElement('postalCode', $this->getContact($authorID, 'postalCode'));
                $this->_xml->writeElement('country', $this->getContact($authorID, 'country'));
                $this->_xml->endElement(); //end address
                $this->_xml->writeElement('phone', $this->getContact($authorID, 'phone'));
                $this->_xml->writeElement('electronicMailAddress', $this->getContact($authorID, 'electronicMailAddress'));
                $this->_xml->writeElement('onlineUrl', $this->getContact($authorID, 'onlineUrl'));
                $this->_xml->endElement(); //end contact    
            }
            
            /************************************************
            * Project(s)
            ************************************************/
            if($this->getBasicMetadata('researchProjectTitle') && $this->getBasicMetadata('projectOrganization')) {
                $this->_xml->startElement('project');
                    $this->_xml->writeElement('title', $this->getBasicMetadata('researchProjectTitle'));
                    $this->_xml->startElement('personnel');
                    $this->_xml->writeElement('organizationName', $this->getBasicMetadata('projectOrganization'));
                    $this->_xml->writeElement('role', 'Distributor');
                    $this->_xml->endElement(); //end personnel
                    $this->_xml->startElement('designDescription');
                    $this->_xml->writeElement('description', $this->getBasicMetadata('projectDescription'));
                    $this->_xml->endElement(); //end designDescription
                $this->_xml->endElement(); //end project
            }
            
            $this->_xml->endElement(); //end dataset
            
            /************************************************
            * Citation REQUIRED
            ************************************************/
            $this->_xml->startElement('additionalMetadata');
            $this->_xml->startElement('metadata');
            $this->_xml->writeElement('citation', $this->getBasicMetaData('citation'));
            $this->_xml->endElement(); //end metadata
            $this->_xml->endElement(); //end additionalMetadata
            
            /************************************************
            * Bibliography
            ************************************************/
            if($this->getBibliography()) {
                $this->_xml->startElement('additionalMetadata');
                $this->_xml->startElement('metadata');
                $this->_xml->startElement('bibliography');
                foreach($this->getBibliography() as $biblioItem) {
                    $this->_xml->writeElement('citation', $biblioItem);
                }
                $this->_xml->endElement(); //end bibliography
                $this->_xml->endElement(); //end metadata
                $this->_xml->endElement(); //end additionalMetadata
            }
            
            /************************************************
            * Metadata Language REQUIRED
            ************************************************/
            $this->_xml->startElement('additionalMetadata');
            $this->_xml->startElement('metadata');
            $this->_xml->writeElement('metadataLanguage', $this->getBasicMetadata('metadataLanguage'));
            $this->_xml->endElement(); //end metadata
            $this->_xml->endElement(); //end additionalMetadata
            
            /************************************************
            * Resource Logo URL
            ************************************************/
            if($this->getBasicMetadata('resourceLogoUrl')) {
                $this->_xml->startElement('additionalMetadata');
                $this->_xml->startElement('metadata');
                $this->_xml->writeElement('resourceLogoUrl', $this->getBasicMetadata('resourceLogoUrl'));
                $this->_xml->endElement(); //end metadata
                $this->_xml->endElement(); //end additionalMetadata
            }
            
        $this->_xml->endElement(); //end eml:eml
        $this->_xml->endDocument();
        
        if($header) $this->_xml->flush();
    }
    
    /**
    *  Produce Raw XML after generateXml method
    * @return xml
    */
    public function getRawXml() {
        return $this->_xml->outputMemory(true);
    }
    
    /**
    *  Set basic metadata for DwC-A eml
    * @param string $name
    * @param string $value
    */
    public function setBasicMetaData($name, $value) {
        $this->_basicMetaData[$name] = $value;
    }

    /**
    * Get value of a basic metadata element
    * @param string $name
    * @return string value
    */
    public function getBasicMetaData($name) {
        return (array_key_exists($name, $this->_basicMetaData)) ? $this->_basicMetaData[$name] : "";
    }
    
    /**
    * Set an author for contact, metadata or resource element in DwC-A eml
    * @param string $type
    * @param int $author_key
    * @param string $name
    * @param string $value
    */
    public function setAuthor($type='metadata', $author_key=0, $name, $value) {
        switch($type) {
            case 'contact':
                $this->_contactAuthors[$author_key][$name] = $value;
            break;
            case 'metadata':
                $this->_metaDataAuthors[$author_key][$name] = $value;
            break;
            
            case 'resource':
                $this->_resourceAuthors[$author_key][$name] = $value;
            break;
        }
    }
    
    /**
    * Get contact element
    * @param int $author_key
    * @param string $name
    * @return string value of contact element
    */
    private function getContact($author_key, $name) {
        return (array_key_exists($author_key, $this->_contactAuthors)) ? $this->_contactAuthors[$author_key][$name] : "";
    }
    
    /**
    * Get array of all contact authors
    * @return array contact authors
    */
    private function getContactAuthors() {
        return $this->_contactAuthors;
    }
    
    /**
    * Get metadata author element
    * @param int $author_key
    * @param string $name
    * @return string value of metadata author element
    */
    private function getMetaDataAuthor($author_key, $name) {
        return (array_key_exists($author_key, $this->_metaDataAuthors)) ? $this->_metaDataAuthors[$author_key][$name] : "";
    }
    
    /**
    * Get array of metadata authors
    * @return array metadata authors
    */
    private function getMetaDataAuthors() {
        return $this->_metaDataAuthors;
    }
    
    /**
    * Get resource author element
    * @param int $author_key
    * @param string $name
    * @return string value of resource author element
    */
    private function getResourceAuthor($author_key, $name) {
        return (array_key_exists($author_key, $this->_resourceAuthors)) ? $this->_resourceAuthors[$author_key][$name] : "";
    }
    
    /**
    * Get array of resource authors
    * @return array resource authors
    */
    private function getResourceAuthors() {
        return $this->_resourceAuthors;
    }
    
    /**
     * Set reference
     * @param string reference
     */
    public function setBibliographicItem($reference) {
        $this->_bibliography[] = $reference;
    }
    
    /**
     * Get array of all references
     * @return array references
     */
    private function getBibliography() {
        return $this->_bibliography;
    }
    
    /**
     * Set keyword
     * @param string keyword
     */
    public function setKeyWords($keyword) {
        $this->_keyWords[] = $keyword;
    }
    
    /**
     * Get keywords
     * @return array keywords
     */
    private function getKeyWords() {
        return $this->_keyWords;
    }
    
    /**
     * Set keyword thesaurus
     * @param string thesaurus
     */
    public function setKeyWordThesaurus($thesaurus) {
        $this->_keyWordThesaurus = $thesaurus;
    }
    
    private function getKeyWordThesaurus() {
        return $this->_keyWordThesaurus;
    }
    
    /**
     * Set bounding box parameters
     * @param string bound (one of minx, miny, maxx, maxy)
     * @param int value
     */
    public function setBoundingBox($bound, $value) {
        $this->_boundingBox[$bound] = $value;
    }
    
    /**
     * Get bounding box value
     * @param string bound
     * @return value
     */
    private function getBoundingBox($bound) {
        return (array_key_exists($bound, $this->_boundingBox)) ? $this->_boundingBox[$bound] : "";
    }
    
    /**
     * Set date coverage for resource
     * @param string type (options are begin, end, single)
     * @param date value (in form 2010-01-15)
     */
    public function setDateCoverage($type, $value) {
        $this->_dateCoverage[$type] = $value;
    }
    
    /**
     * Get a date coverage value
     * @param string type (options are begin, end, single)
     * @return date value
     */
    private function getDateCoverage($type) {
        return (array_key_exists($type, $this->_dateCoverage)) ? $this->_dateCoverage[$type] : "";
    }
    
    /**
     * Set some taxonomic keywords
     * @param string $value
     */
    public function setGeneralTaxonomicCoverage($value) {
        $this->_generalTaxonomicCoverage = $value;
    }
    
    /**
     * Get general taxonomic keywords
     * @return string value
     */
    private function getGeneralTaxonomicCoverage() {
        return $this->_generalTaxonomicCoverage;
    }

}
?>