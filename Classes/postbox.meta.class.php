<?php

/**************************************************************************

File: postbox.meta.class.php

Description: This class produces an XML stream for a meta.xml file as part
of a Darwin Core Archive.
It is extended by postbox.Excel.classification.class.php

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/

//load the controlled vocabularies
require_once (dirname(__FILE__) . '/postbox.vocabularies.class.php');

class PostBox_Meta {
    
    private $_xml = '';
    private $_coreFileName = 'taxa.txt';
    private $_synonymyFileName = 'synonymy.txt';
    private $_vernacularsFileName = 'vernacular.txt';
    private $_distributionFileName = 'distribution.txt';
    private $_lineTermination = '\r';
    private $_fieldTermination = '\t';
    private $_fieldEnclosure = '"';
    private $_ignoreHeaderLines = '1';
    private $_options = array();

    private static $_coreHeaders = array();
    private static $_synonymyHeaders = array();
    private static $_vernacularsHeaders = array();
    private static $_distributionHeaders = array();
    
    function __construct() {
    }
    
    /**
    *  Generate XML for DwC-A meta file
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
        $this->_xml->startElement('archive');
        $this->_xml->writeAttribute('xmlns', 'http://rs.tdwg.org/dwc/text/');

            $this->_xml->startElement('core');
            $this->_xml->writeAttribute('encoding', 'UTF-8');
            $this->_xml->writeAttribute('linesTerminatedBy', $this->getLineTermination());
            $this->_xml->writeAttribute('fieldsTerminatedBy', $this->getFieldTermination());
            $this->_xml->writeAttribute('fieldsEnclosedBy', $this->getFieldEnclosure());
            $this->_xml->writeAttribute('ignoreHeaderLines', $this->getIgnoreHeaderLines());
            $this->_xml->writeAttribute('rowType', 'http://rs.tdwg.org/dwc/terms/Taxon');

                $this->_xml->startElement('files');
                    $this->_xml->writeElement('location', $this->getCoreFileName());
                $this->_xml->endElement(); //end files

                $index = 0;
                foreach($this->getCoreHeaders() as $field) {
                    if(in_array($field, PostBox_Vocabularies::$coreTerms)) {
                        if($field == 'taxonID') {
                            $this->_xml->startElement('id');
                            $this->_xml->writeAttribute('index', $index);
                        }
                        else {
                            $this->_xml->startElement('field');
                            $this->_xml->writeAttribute('index', $index);
                            $this->_xml->writeAttribute('term', 'http://rs.tdwg.org/dwc/terms/'.$field);
                        }
                        $this->_xml->endElement(); //end id or field element
                    }
                    $index++;
                }
        
            $this->_xml->endElement(); //end core
            
            // Synonymy Extension
            if($this->getOption('make-synonymy-file') && $this->getSynonymyHeaders()) $this->generateExtensionXml($this->getSynonymyFileName(), $this->getSynonymyHeaders());
            
            // Vernaculars Extension
            if($this->getVernacularsHeaders()) $this->generateExtensionXml($this->getVernacularsFileName(), $this->getVernacularsHeaders());
            
            // Distribution Extension
            if($this->getDistributionHeaders()) $this->generateExtensionXml($this->getDistributionFileName(), $this->getDistributionHeaders());

            $this->_xml->endElement(); //end archive
        $this->_xml->endDocument(); //end document
        
        if($header) $this->_xml->flush();
        
    }
    
    /**
    * Helper function to generate elements in the xml for DwC-A extensions
    * @param string $fileName
    * @param array $headers
    */
    private function generateExtensionXml($fileName = '', $headers = array()) {
        $this->_xml->startElement('extension');
        $this->_xml->writeAttribute('encoding', 'UTF-8');
        $this->_xml->writeAttribute('linesTerminatedBy', $this->getLineTermination());
        $this->_xml->writeAttribute('fieldsTerminatedBy', $this->getFieldTermination());
        $this->_xml->writeAttribute('fieldsEnclosedBy', $this->getFieldEnclosure());
        $this->_xml->writeAttribute('ignoreHeaderLines', $this->getIgnoreHeaderLines());
        $this->_xml->writeAttribute('rowType', 'http://rs.tdwg.org/dwc/terms/Taxon');

        $this->_xml->startElement('files');
            $this->_xml->writeElement('location', $fileName);
        $this->_xml->endElement(); //end files
        
        $index = 0;
        foreach($headers as $field) {
            if(strtolower($field) == 'taxonid') {
                $this->_xml->startElement('coreid');
                $this->_xml->writeAttribute('index', $index);
            }
            else {
                $this->_xml->startElement('field');
                $this->_xml->writeAttribute('index', $index);
                $this->_xml->writeAttribute('term', 'http://rs.tdwg.org/dwc/terms/' . $field);
            }
            $this->_xml->endElement(); //end coreid or field
            $index++;
        }
        
        $this->_xml->endElement(); //end extension
    }
    
    /**
    *  Produce Raw XML after generateXml method
    * @return xml
    */
    public function getRawXml() {
        return $this->_xml->outputMemory(true);
    }
    
    /**
    * Set core "star" file name for DwC-A
    * @param string $filename
    */
    public function setCoreFileName($filename) {
        $this->_coreFileName = $filename;
    }
    
    /**
    * Get core "star" file name
    * @return string file name
    */
    public function getCoreFileName() {
        return $this->_coreFileName;
    }

    /**
    * Set synonymy extension file name for DwC-A (if desired & synonyms not put in core file)
    * @param string $filename
    */
    public function setSynonymyFileName($filename) {
        $this->_synonymyFileName = $filename;
    }
    
    /**
    * Get synonymy extension file name
    * @return string file name
    */
    public function getSynonymyFileName() {
        return $this->_synonymyFileName;
    }
    
    /**
    * Set vernacular extension file name for DwC-A
    * @param string $filename
    */
    public function setVernacularsFileName($filename) {
        $this->_vernacularsFileName = $filename;
    }
    
    /**
    * Get vernacular extension file name
    * @return string file name
    */
    public function getVernacularsFileName() {
        return $this->_vernacularsFileName;
    }
    
    /**
    * Set dsitrbution extension file name for DwC-A
    * @param string $filename
    */
    public function setDistributionFileName($filename) {
        $this->_distributionFileName = $filename;
    }
    
    /**
    * Get distribution extension file name
    * @return string file name
    */
    public function getDistributionFileName() {
        return $this->_distributionFileName;
    }
    
    /**
    * Set a core term for the core "star" file in the DwC-A
    * @param array $terms
    */
    public function setCoreHeaders($fields = array()) {
        self::$_coreHeaders = $fields;
    }
    
    /**
    * Get the core terms for the core "star" file in the DwC-A
    * @return array core terms
    */
    public function getCoreHeaders() {
        return self::$_coreHeaders;
    }
    
    /**
    * Set synonymy extension headers
    * @param array $fields
    */
    public function setSynonymyHeaders($fields = array()) {
        self::$_synonymyHeaders = $fields;
    }
    
    /**
    * Get synonymy extension headers
    * @return array
    */
    public function getSynonymyHeaders() {
        return self::$_synonymyHeaders;
    }

    /**
    * Set vernacular extension headers
    * @param array $fields
    */
    public function setVernacularsHeaders($fields = array()) {
        self::$_vernacularsHeaders = $fields;
    }
    
    /**
    * Get vernacular extension headers
    * @return array
    */
    public function getVernacularsHeaders() {
        return self::$_vernacularsHeaders;
    }
    
    /**
    * Set distribution extension headers
    * @param array $fields
    */
    public function setDistributionHeaders($fields = array()) {
        self::$_distributionHeaders = $fields;
    }
    
    /**
    * Get distribution extension headers
    * @return array
    */
    public function getDistributionHeaders() {
        return self::$_distributionHeaders;
    }
    
    /**
    * Set the line termination attributes in the DwC-A meta file
    * @param string $termination
    */
    public function setLineTermination($termination) {
        $this->_lineTermination = $termination;
    }
    
    /**
    * Get the line termination attributes for the DwC-A meta file
    * @return string line termination
    */
    private function getLineTermination() {
        return $this->_lineTermination;
    }
    
    /**
    * Set the field termination attributes in the DwC-A meta file
    * @param string $termination
    */
    public function setFieldTermination($termination) {
        $this->_fieldTermination = $termination;
    }
    
    /**
    * Get the field termination attributes in the DwC-A meta file
    * @return string field termination
    */
    private function getFieldTermination() {
        return $this->_fieldTermination;
    }
    
    /**
    * Set the field enclosure attributes in the DwC-A meta file
    * @param string $enclosure
    */
    public function setFieldEnclosure($enclosure) {
        $this->_fieldEnclosure = $enclosure;
    }
    
    /**
    * Get the field enclosure attributes in the DwC-A meta file
    * @return string enclosure
    */
    private function getFieldEnclosure() {
        return $this->_fieldEnclosure;
    }
    
    /**
    * Set the ignore header lines attributes in the DwC-A meta file
    * @param string $ignore
    */
    public function setIgnoreHeaderLines($ignore) {
        $this->_ignoreHeaderLines = $ignore;
    }
    
    /**
    * Get the ignore header lines attributes in the DwC-A meta file
    * @return string ignore header lines
    */
    private function getIgnoreHeaderLines() {
        return $this->_ignoreHeaderLines;
    }
    
    /**
    * Set some options for the generation of the core file and extensions
    */
    public function setOption($option) {
        $this->_options[$option] = $option;
    }
    
    /**
    * Discover if an option has been set for the generation of the core file and extensions
    */
    public function getOption($option) {
        if(array_key_exists($option, $this->_options)) {
            return true;
        }
        else {
            return false;
        }
    }
}

?>