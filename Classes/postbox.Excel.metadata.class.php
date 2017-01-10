<?php

/**************************************************************************

File: postbox.Excel.metadata.class.php

Description: This class extends the PostBox_Eml class by validating the 
contents of an Excel object "Metadata" sheet then pushes data into the
PostBox_Eml class for later production of an Ecological Markup Language
file.

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/

//load the PHPExcel IOFactory
require_once (dirname(__FILE__) . '/PHPExcel/Classes/PHPExcel/IOFactory.php');

//load the PHPExcel advanced cell binder
require_once (dirname(__FILE__) . '/PHPExcel/Classes/PHPExcel/Cell/AdvancedValueBinder.php');

//load the UUID class
require_once (dirname(__FILE__) . '/uuid.class.php');

//load the eml class
require_once (dirname(__FILE__) . '/postbox.eml.class.php');

//load the vocabularies class
require_once (dirname(__FILE__) . '/postbox.vocabularies.class.php');

class PostBox_ExcelMetadata extends PostBox_Eml {
    
    public static $_errors = array();
    
    /**
    * Constructor
    * @param obj $wk_metadata
    */
    function __construct($wk_metadata) {
        $this->valid = true;
        $this->wk_metadata = $wk_metadata;
        
        $this->rowcount = $this->wk_metadata->getHighestRow();
        
        //Cell Titles
        $this->cellTitle_uuid               = $this->wk_metadata->getCell('A3')->getCalculatedValue();
        $this->cellTitle_title              = $this->wk_metadata->getCell('A4')->getCalculatedValue();
        $this->cellTitle_pubDate            = $this->wk_metadata->getCell('A5')->getCalculatedValue();
        $this->cellTitle_citation           = $this->wk_metadata->getCell('A6')->getCalculatedValue();
        $this->cellTitle_abstract           = $this->wk_metadata->getCell('A7')->getCalculatedValue();
        $this->cellTitle_intellectualRights = $this->wk_metadata->getCell('A11')->getCalculatedValue();
        $this->cellTitle_primaryContact     = $this->wk_metadata->getCell('A17')->getCalculatedValue();
        $this->cellTitle_metadataAuthor     = $this->wk_metadata->getCell('A18')->getCalculatedValue();
        $this->cellTitle_author             = $this->wk_metadata->getCell('A19')->getCalculatedValue();
        $this->cellTitle_firstName          = $this->wk_metadata->getCell('B16')->getCalculatedValue();
        $this->cellTitle_lastName           = $this->wk_metadata->getCell('C16')->getCalculatedValue();
        $this->cellTitle_organization       = $this->wk_metadata->getCell('D16')->getCalculatedValue();
        $this->cellTitle_phone              = $this->wk_metadata->getCell('E16')->getCalculatedValue();
        $this->cellTitle_email              = $this->wk_metadata->getCell('F16')->getCalculatedValue();
        $this->cellTitle_homepage           = $this->wk_metadata->getCell('G16')->getCalculatedValue();
        $this->cellTitle_address            = $this->wk_metadata->getCell('H16')->getCalculatedValue();
        $this->cellTitle_city               = $this->wk_metadata->getCell('I16')->getCalculatedValue();
        $this->cellTitle_stateProvince      = $this->wk_metadata->getCell('J16')->getCalculatedValue();
        $this->cellTitle_country            = $this->wk_metadata->getCell('K16')->getCalculatedValue();
        $this->cellTitle_zipPostal          = $this->wk_metadata->getCell('L16')->getCalculatedValue();
        
        //Cell Values
        $this->uuid                 = $this->wk_metadata->getCell('B3')->getCalculatedValue();
        $this->title                = $this->wk_metadata->getCell('B4')->getCalculatedValue();
        $this->pubDate              = $this->wk_metadata->getCell('B5')->getCalculatedValue();
        $this->citation             = $this->wk_metadata->getCell('B6')->getCalculatedValue();
        $this->abstract             = $this->wk_metadata->getCell('B7')->getCalculatedValue();
        $this->additionalInfo       = $this->wk_metadata->getCell('B8')->getCalculatedValue();
        $this->resourceLanguage     = $this->wk_metadata->getCell('B9')->getCalculatedValue();
        $this->resourceUrl          = $this->wk_metadata->getCell('B10')->getCalculatedValue();
        $this->metadataLanguage     = $this->wk_metadata->getCell('F9')->getCalculatedValue();
        $this->resourceLogoUrl      = $this->wk_metadata->getCell('F10')->getCalculatedValue();
        $this->intellectualRights   = $this->wk_metadata->getCell('B11')->getCalculatedValue();
        $this->projectTitle         = $this->wk_metadata->getCell('J3')->getCalculatedValue();
        $this->projectOrganization  = $this->wk_metadata->getCell('J4')->getCalculatedValue();
        $this->projectDescription   = $this->wk_metadata->getCell('J5')->getCalculatedValue();
        $this->startDate            = $this->wk_metadata->getCell('B39')->getCalculatedValue();
        $this->endDate              = $this->wk_metadata->getCell('B40')->getCalculatedValue();
        $this->keywords             = $this->wk_metadata->getCell('B37')->getCalculatedValue();
        $this->keywordThesaurus     = $this->wk_metadata->getCell('B38')->getCalculatedValue();
        $this->minx                 = $this->wk_metadata->getCell('H37')->getCalculatedValue(); //ul long
        $this->maxx                 = $this->wk_metadata->getCell('H38')->getCalculatedValue(); //lr long
        $this->miny                 = $this->wk_metadata->getCell('G37')->getCalculatedValue(); //lr lat
        $this->maxy                 = $this->wk_metadata->getCell('G38')->getCalculatedValue(); //ul lat
        
        //Contact values
        $this->contact_firstName        = $this->wk_metadata->getCell('B17')->getCalculatedValue();
        $this->contact_lastName         = $this->wk_metadata->getCell('C17')->getCalculatedValue();
        $this->contact_organization     = $this->wk_metadata->getCell('D17')->getCalculatedValue();
        $this->contact_phone            = $this->wk_metadata->getCell('E17')->getCalculatedValue();
        $this->contact_email            = $this->wk_metadata->getCell('F17')->getCalculatedValue();
        $this->contact_homepage         = $this->wk_metadata->getCell('G17')->getCalculatedValue();
        $this->contact_address          = $this->wk_metadata->getCell('H17')->getCalculatedValue();
        $this->contact_city             = $this->wk_metadata->getCell('I17')->getCalculatedValue();
        $this->contact_stateProvince    = $this->wk_metadata->getCell('J17')->getCalculatedValue();
        $this->contact_country          = $this->wk_metadata->getCell('K17')->getCalculatedValue();
        $this->contact_zipPostal        = $this->wk_metadata->getCell('L17')->getCalculatedValue();
        
        //Metadata Author values
        $this->metaAuthor_givenName     = $this->wk_metadata->getCell('B18')->getCalculatedValue();
        $this->metaAuthor_lastName      = $this->wk_metadata->getCell('C18')->getCalculatedValue();
        $this->metaAuthor_organization  = $this->wk_metadata->getCell('D18')->getCalculatedValue();
        $this->metaAuthor_phone         = $this->wk_metadata->getCell('E18')->getCalculatedValue();
        $this->metaAuthor_email         = $this->wk_metadata->getCell('F18')->getCalculatedValue();
        $this->metaAuthor_homepage      = $this->wk_metadata->getCell('G18')->getCalculatedValue();
        $this->metaAuthor_city          = $this->wk_metadata->getCell('I18')->getCalculatedValue();
        $this->metaAuthor_stateProvince = $this->wk_metadata->getCell('J18')->getCalculatedValue();
        $this->metaAuthor_country       = $this->wk_metadata->getCell('K18')->getCalculatedValue();
        $this->metaAuthor_zipPostal     = $this->wk_metadata->getCell('L18')->getCalculatedValue();
        
    }
    
    function __destruct() {
        unset($this->wk_metadata);
    }

    /**
    * Parser method to first execute validation then produce content for eml file
    */
    public function parse() {
        
        //set Basic Metadata if no errors
        if($this->validateMetaData()) {
            $this->setEmlMetaData();
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
    * Produce the XML stream for the eml
    * @return xml stream
    */
    public function getRawEml() {
        PostBox_Eml::generateXml(false);
        return PostBox_Eml::getRawXml();
    }
    
    /**
    * Validate the contents of the metadata object
    * @return true/false for valid/invalid
    */
    public function validateMetaData() {
        
        //check if all Basic Metadata cell titles are properly named and arranged
        if($this->cellTitle_uuid != 'UUID') {
            $this->setError("The 'UUID' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_title != 'Title') {
            $this->setError("The 'Title' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_pubDate != 'Publication Date (MM/DD/YYYY)') {
            $this->setError("The 'Publication Date (MM/DD/YYYY)' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_citation != 'Expected Citation') {
            $this->setError("The 'Expected Citation' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_abstract != 'Abstract') {
            $this->setError("The 'Abstract' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_intellectualRights != 'Creative Commons Licensing') {
            $this->setError("The 'Creative Commons Licensing' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        
        //check if required Basic Metadata cells are filled
        if(!UUID::is_valid($this->getUUID())) {
            $this->setError("The UUID is not validly formed in the Metadata sheet");
            $this->valid = false;
        }

        if(!trim($this->title)) {
            $this->setError("The 'Title' cell is empty in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->pubDate)) {
            $this->setError("The 'Publication Date' cell is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->citation)) {
            $this->setError("The 'Expected Citation' cell is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->abstract)) {
            $this->setError("The 'Abstract' cell is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->intellectualRights)) {
            $this->setError("The 'Creative Commons Licensing' cell is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->resourceLanguage)) {
            $this->setError("The 'Resource Language' cell is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->metadataLanguage)) {
            $this->setError("The 'Metadata Language' cell is missing in the Metadata sheet");
            $this->valid = false;
        }
        
        //validate the Creative Commons Licensing selection
        if(!in_array($this->intellectualRights, PostBox_Vocabularies::$ccLicenses)) {
            $this->setError("A valid Creative Commons License was not chosen");
            $this->valid = false;
        }
        
        //check to see if all People and Organizations cell titles are properly named and arranged
        if($this->cellTitle_primaryContact != 'Primary Contact') {
            $this->setError("The 'Contact' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_metadataAuthor != 'Metadata Author') {
            $this->setError("The 'Metadata Author' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_author != 'Author') {
            $this->setError("The 'Resource Author' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_firstName != 'First Name') {
            $this->setError("The 'First Name' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_lastName != 'Last Name') {
            $this->setError("The 'Last Name' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_organization != 'Organization') {
            $this->setError("The 'Organization' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_phone != 'Phone') {
            $this->setError("The 'Phone' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_email != 'Email') {
            $this->setError("The 'Email' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_homepage != 'Homepage') {
            $this->setError("The 'Homepage' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_address != 'Address') {
            $this->setError("The 'Address' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_city != 'City') {
            $this->setError("The 'City' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_stateProvince != 'State/Province') {
            $this->setError("The 'State/Province' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_country != 'Country') {
            $this->setError("The 'Country' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        if($this->cellTitle_zipPostal != 'Zip/Postal Code') {
            $this->setError("The 'Postal Code' cell title is missing in the Metadata sheet");
            $this->valid = false;
        }
        
        //check to make sure the Primary Contact has all required cells filled
        if(!trim($this->contact_firstName)) {
            $this->setError("The contact's First Name is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->contact_lastName)) {
            $this->setError("The contact's Last Name is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->contact_phone)) {
            $this->setError("The contact's Phone is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->contact_email)) {
            $this->setError("The contact's Email is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->contact_address)) {
            $this->setError("The contact's Address is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->contact_city)) {
            $this->setError("The contact's City is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->contact_stateProvince)) {
            $this->setError("The contact's State/Province is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->contact_country)) {
            $this->setError("The contact's Country is missing in the Metadata sheet");
            $this->valid = false;
        }
        if(!trim($this->contact_zipPostal)) {
            $this->setError("The contact's Zip/Postal Code is missing in the Metadata sheet");
            $this->valid = false;
        }
        
        //validate bounding box
        //NOTE: this does not work if region straddles the international date line
        if(trim($this->maxy) || trim($this->miny) || trim($this->maxx) || trim($this->minx)) {
            if($this->maxy > 90 || $this->miny < -90 || $this->maxx > 180 || $this->minx < -180) {
                $this->setError("Your bounding box coordinates are out of range. Upper left Latitude should be less than 90, upper left longitude should be greater than -180, lower left latitude should be greater than -90 and lower left longitude should be less than 180.");
                $this->valid = false;
            }
        }
        
        return $this->valid;
        
    }
    
    /*
    * Set a UUID
    * @param string $id
    */
    public function setUUID($id) {
        $this->uuid = $id;
    }
    
    /**
    * Get the UUID
    * @return string $this->uuid
    */
    public function getUUID() {
        return $this->uuid;
    }
    
    /*
    * Create a UUID and set the value in the UUID cell
    */
    public function createUUID() {
        $this->uuid = UUID::v4();
        $this->wk_metadata->getCell('B3')->setValue($this->uuid);
    }
    
    /**
    * Set the eml metadata by passing data to parent PostBox_Eml class
    */
    private function setEmlMetaData() {
        
        // Authors and biblio items are handled below
        
        //UUID
        PostBox_Eml::setBasicMetaData('uuid', $this->uuid);
        
        //title
        if(trim($this->title)) PostBox_Eml::setBasicMetaData('title', $this->title);
        
        //pubDate
        if(trim($this->pubDate))
        PostBox_Eml::setBasicMetaData('pubDate', gmdate('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($this->pubDate)));
        
        //citation
        if(trim($this->citation)) PostBox_Eml::setBasicMetaData('citation', $this->citation);
        
        //abstract
        if(trim($this->abstract)) PostBox_Eml::setBasicMetaData('abstract', $this->abstract);
        
        //additionalInfo
        if(trim($this->additionalInfo)) PostBox_Eml::setBasicMetaData('additionalInfo', $this->additionalInfo);
        
        //resourceLanguage
        if(trim($this->resourceLanguage)) PostBox_Eml::setBasicMetaData('resourceLanguage', $this->resourceLanguage);
        
        //resourceUrl
        if(trim($this->resourceUrl)) PostBox_Eml::setBasicMetaData('resourceUrl', $this->resourceUrl);
        
        //metadataLanguage
        if(trim($this->metadataLanguage)) PostBox_Eml::setBasicMetaData('metadataLanguage', $this->metadataLanguage);
        
        //resourceLogoUrl
        if(trim($this->resourceLogoUrl)) PostBox_Eml::setBasicMetaData('resourceLogoUrl', $this->resourceLogoUrl);
        
        //Intellectual Rights (i.e. Creative Commons)
        if(trim($this->intellectualRights)) PostBox_Eml::setBasicMetaData('intellectualRights', 'Creative Commons: ' . $this->intellectualRights);
        
        //projectTitle
        if(trim($this->projectTitle)) PostBox_Eml::setBasicMetaData('projectTitle', $this->projectTitle);
        
        //projectOrganization
        if(trim($this->projectOrganization)) PostBox_Eml::setBasicMetaData('projectOrganization', $this->projectOrganization);
        
        //projectDescription
        if(trim($this->projectDescription)) PostBox_Eml::setBasicMetaData('projectDescription', $this->projectDescription);
        
        //creator author
        $authorID = 1;
        $contact = array(
            'creatorID'             => $authorID,
            'givenName'             => $this->contact_firstName,
            'surName'               => $this->contact_lastName,
            'organizationName'      => $this->contact_organization,
            'phone'                 => $this->contact_phone,
            'electronicMailAddress' => $this->contact_email,
            'onlineUrl'             => $this->contact_homepage,
            'city'                  => $this->contact_city,
            'administrativeArea'    => $this->contact_stateProvince,
            'country'               => $this->contact_country,
            'postalCode'            => $this->contact_zipPostal,
        );
        foreach($contact as $key => $value) {
            PostBox_Eml::setAuthor('contact', $authorID, $key, $value);
        }
        
        $authorID++;
        $metadataAuthor = array(
            'creatorID'             => $authorID,
            'givenName'             => $this->metaAuthor_givenName,
            'surName'               => $this->metaAuthor_lastName,
            'organizationName'      => $this->metaAuthor_organization,
            'phone'                 => $this->metaAuthor_phone,
            'electronicMailAddress' => $this->metaAuthor_email,
            'onlineUrl'             => $this->metaAuthor_homepage,
            'city'                  => $this->metaAuthor_city,
            'administrativeArea'    => $this->metaAuthor_stateProvince,
            'country'               => $this->metaAuthor_country,
            'postalCode'            => $this->metaAuthor_zipPostal,
        );
        
        foreach($metadataAuthor as $key => $value) {
            PostBox_Eml::setAuthor('metadata', $authorID, $key, $value);
        }
        
        //resource creator authors
        for($i=17; $i<=30; $i++) {
            //check if surname column is filled as indication that there is a resource author
            if($this->wk_metadata->getCell('C'.$i)->getCalculatedValue()) {
                $authorID++;
                $resourceAuthor = array(
                    'creatorID'             => $authorID,
                    'givenName'             => $this->wk_metadata->getCell('B'.$i)->getCalculatedValue(),
                    'surName'               => $this->wk_metadata->getCell('C'.$i)->getCalculatedValue(),
                    'organizationName'      => $this->wk_metadata->getCell('D'.$i)->getCalculatedValue(),
                    'phone'                 => $this->wk_metadata->getCell('E'.$i)->getCalculatedValue(),
                    'electronicMailAddress' => $this->wk_metadata->getCell('F'.$i)->getCalculatedValue(),
                    'onlineUrl'             => $this->wk_metadata->getCell('G'.$i)->getCalculatedValue(),
                    'city'                  => $this->wk_metadata->getCell('I'.$i)->getCalculatedValue(),
                    'administrativeArea'    => $this->wk_metadata->getCell('J'.$i)->getCalculatedValue(),
                    'country'               => $this->wk_metadata->getCell('K'.$i)->getCalculatedValue(),
                    'postalCode'            => $this->wk_metadata->getCell('L'.$i)->getCalculatedValue(),
                );
                foreach($resourceAuthor as $key => $value) {
                    PostBox_Eml::setAuthor('resource', $authorID, $key, $value);
                }
            }
        }
        
        //Temporal Coverage
        
        //Single Date
        if(trim($this->startDate) && !trim($this->endDate)) {
            PostBox_Eml::setDateCoverage('single', gmdate('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($this->startDate)));
        }
        //Start and End Date
        elseif(trim($this->startDate) && trim($this->endDate)) {
            PostBox_Eml::setDateCoverage('begin', gmdate('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($this->startDate)));
            PostBox_Eml::setDateCoverage('end', gmdate('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($this->endDate)));
        }
        else {}
        
        //Keywords
        if(trim($this->keywords)) {
            $keywords = preg_split("/[\s,;]+/", $this->keywords);
            foreach($keywords as $keyword) {
                PostBox_Eml::setKeyWords($keyword);
            }
        }
        
        //Keyword Thesuarus
        if(trim($this->keywordThesaurus)) PostBox_Eml::setKeyWordThesaurus($this->keywordThesaurus);
        
        //Bounding Box
        if(trim($this->minx) && trim($this->maxx) && trim($this->miny) && trim($this->maxy)) {
            PostBox_Eml::setBoundingBox('minx', $this->minx);
            PostBox_Eml::setBoundingBox('maxx', $this->maxx);
            PostBox_Eml::setBoundingBox('miny', $this->miny);
            PostBox_Eml::setBoundingBox('maxy', $this->maxy);
        }
        
        //Bibliographic Items
        for($i=46; $i<=$this->rowcount; $i++) {
            $biblioItem = $this->wk_metadata->getCell('B'.$i)->getCalculatedValue();
            if(trim($biblioItem)) {
                PostBox_Eml::setBibliographicItem($biblioItem);
            }
        }

    }
    
    /**
    * Set an error
    * @param string $message
    */
    private function setError($message) {
        self::$_errors[] = $message; 
    }
    
    /**
    * Get all errors
    * @return array
    */
    public static function getErrors() {
        return self::$_errors;
    }
}

?>