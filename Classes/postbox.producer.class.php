<?php

/**************************************************************************

File: postbox.producer.class.php

Description: This class is a worker that grabs a job and produces all the 
necessary files in a Darwin Core Archive and zips it up for download and/or 
POSTing to the Global Names Archictecture Classification and List Repository

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/

//configuration
require_once (dirname(__FILE__) . '/conf/conf.php');

//Resque classes
require_once (dirname(__FILE__) . '/Resque/Resque.php');
require_once (dirname(__FILE__) . '/Resque/Resque/Worker.php');
require_once (dirname(__FILE__) . '/Resque/Resque/Stat.php');
require_once (dirname(__FILE__) . '/Resque/Resque/Job/Status.php');
require_once (dirname(__FILE__) . '/Resque/Resque/Job/Message.php');

//load the PHPExcel IOFactory
require_once (dirname(__FILE__) . '/PHPExcel/Classes/PHPExcel/IOFactory.php');

//load the metadata parsing class that extends the eml class
require_once (dirname(__FILE__) . '/postbox.Excel.metadata.class.php');

//load the classification parsing class
require_once (dirname(__FILE__) . '/postbox.Excel.classification.class.php');

//load the UUID class
require_once (dirname(__FILE__) . '/uuid.class.php');

//load the zip archive to generate the DwC archive
require_once (dirname(__FILE__) . '/zip/archive.php');

//mailer class
require_once (dirname(__FILE__) . '/mail.php');

class PostBox_Producer {
    
    /* PHPExcel object */
    private $_objPHPExcel;
    
    /* PHPExcel writer object */
    private $_objPHPExcelWriter;
    
    /* PHPExcel metadata worksheet object */
    private $_wkMetaData;
    
    /* PHPExcel classification worksheet */
    private $_wkClassification;
    
    /* zip object */
    private $_zip;
    
    /* Allowed zip extensions/types */
    public static $_allowedCompressionTypes = array(
        'zip',
        'gzip',
    );
    
    /* Set paths for shuttling of files and the log directory */
    private static $_uploadPath = UPLOAD_PATH;
    private static $_downloadPath = DOWNLOAD_PATH;
    private static $_failedPath = FAILED_PATH;
    private static $_logPath = LOG_PATH;
    
    /* Error holder */
    public static $_errors = array();
    
    /**
    * Constructor
    * @param array $payload php-resque payload 
    * @param true/false $resque
    */
    function __construct($payload = array(), $resque = false) {

        if(!$payload) return;
        
        $this->payload = $payload;

        $this->file = '';
        $this->workingFile = '';
        $this->options = array();
        $this->tmpPath = '';
        $this->UUID = '';
        $this->emlFileName = 'eml.xml';
        $this->fieldTermination = "\t";
        $this->fieldEnclosure = '"';
        $this->metaFileName = 'meta.xml';
        $this->MySQLFileName = 'mysql.sql';
        $this->metadata = '';
        $this->classification = '';
        $this->compressionType = 'gzip';
        $this->resque = $resque;
        $this->redis_server = false;
        $this->id = '';
        $this->status = '';
        $this->message = '';
        
        if($this->resque) {
            try {
                @Resque::setBackend(RESQUE_ADDRESS.':'.RESQUE_PORT);
                $this->redis_server = Resque::$redis;
            }
            catch(Exception $e) {
                //throw something here when the resque server is belly-up
                return;
            }
        }

    }
    
    function __destruct() {
        unset($this->file);
        unset($this->workingFile);
        unset($this->redis_server);
        unset($this->options);
        unset($this->worker);
        unset($this->status);
        unset($this->metadata);
        unset($this->classification);
    }
    
    public function run() {
        
        if($this->redis_server) {
            if(memory_get_usage(true) > 1048576*POSTBOX_MEMORY) exit;
        }
        
        $startTime = time();
        
        if($this->resque) {

            $this->id = $this->payload->job->payload['id'];
                
            $this->message = new Resque_Job_Message($this->id);
            $this->message->update("The job is now underway.");
                
            $this->file = $this->getUploadPath() . '/' . $this->payload->args['file'];
            $this->options = $this->payload->args['options'];
            $this->log("job: " . $this->id);
        }

        if(is_file($this->file) && $this->options) {

            $this->log("started: " . time());
            
            $this->createTmpPath();
            $this->loadFile();
            $this->setCache();
                
            if($this->parseMetadata()) $this->parseClassification();
                
            if(self::getErrors() || PostBox_ExcelMetadata::getErrors() || PostBox_ExcelClassification::getErrors()) {
                
                $errors = array_merge(self::getErrors(), PostBox_ExcelMetadata::getErrors(), PostBox_ExcelClassification::getErrors());

                $this->message->update($errors);
                    
                if($this->options['email'] && POSTBOX_ENV == 'production') $this->emailFile(false);
                    
                //move the file to the failed directory
                rename($this->workingFile, $this->getFailedPath() . '/' . basename($this->workingFile));
                   
                $this->log("errors: " . implode("; ", $errors));
                $this->log("file: " . basename($this->workingFile));
                    
            }
            else {
                
                $this->createEml();
                
                $this->createCore();
                
                if($this->options['synonymy']) $this->createSynonymy();
                
                $this->createVernaculars();
                
                $this->createDistribution();
                
                $this->createMeta();
                
                if($this->options['mysql']) $this->createMySQLDump();
                $this->setCompressionType($this->options['format']);
                $this->zipArchive();

                if($this->options['gnaclr']) $this->postGNACLR();
                if($this->options['email'] && POSTBOX_ENV == 'production') $this->emailFile(true);

                $file = BASE_URL . '/download/' . $this->getUUID() . '.' . $this->getCompressionType();
                $download = "<p><a href=" . $file . '>' . $file . '</a></p>';
                $this->message->update("Your Darwin Core Archive is now ready" . $download);
                    
            }
                
            @unlink($this->file); //remove the file from the queue directory
            
            $memory = memory_get_peak_usage();
            $unit = array('b','kb','mb','gb','tb','pb');
            $peak_memory_usage = @round($memory/pow(1024,($i=floor(log($memory,1024)))),2).' '.$unit[$i];
            $this->log('peak memory usage: ' . $peak_memory_usage);

            $endTime = time();

            $this->log('finished: ' . $endTime);
            $duration = $endTime - $startTime;
            $this->log('duration: ' . $duration . ' sec');
            $this->log('*******************');

            $this->cleanUp();

            // Throw an exception if there are errors such that Resque Worker will set status as failed
            if(self::getErrors() || PostBox_ExcelMetadata::getErrors() || PostBox_ExcelClassification::getErrors()) {
              Resque::push(RESQUE_TUBE_FAILED, array('id' => $this->id, 'file' => basename($this->workingFile)));
              throw new Exception("There was an error in the  structure of the file.");
            }

        }

    }
    
    /**
    * Set the upload path where files will be found
    * @param string $path
    */
    public function setUploadPath($path) {
        self::$_uploadPath = $path;
    }
    
    /**
    * Get the system upload path where Excel files are worked on
    * @return string system upload path
    */
    private function getUploadPath() {
        return self::$_uploadPath;
    }
    
    /**
    * Set a system failed path where files that are not successfully parsed are placed
    * @param string $path
    */
    public function setFailedPath($path) {
        self::$_failedPath = $path;
    }
    
    /**
    * Get the system failed path where files that are not successfully parsed are placed
    * @return string failed path
    */
    private function getFailedPath() {
        return self::$_failedPath;
    }
    
    /**
    * Set a system download path where zipped Darwin Core Archive files will be accessible
    * @param string $path
    */
    public function setDownloadPath($path) {
        self::$_downloadPath = $path;
    }
    
    /**
    * Get the system download path where zipped Darwin Core Archive files are accessible
    * @return string download path
    */
    private function getDownloadPath() {
        return self::$_downloadPath;
    }
    
    /**
    * Load an uploaded file (full system path) into PHPExcel
    * @param string $file
    */
    public function loadFile() {
        $this->message->update("Loading your file. This may take some time.");
        
        //create reader
        $this->_objPHPExcel = PHPExcel_IOFactory::load($this->workingFile);
    
        if(!is_object($this->_objPHPExcel)) {
            $this->setError("File is not recognized");
        }
    }
    
    /**
    * Set caching of cells to reduce memory footprint
    * @param true/false $choice
    */
    public function setCache() {
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
    }
    
    /**
    * Set the zipped compression type, dependent allowed types
    * @param string $type
    */
    public function setCompressionType($type) {
        if(!in_array($type, self::$_allowedCompressionTypes)) {
            $this->setError("That compression type is not accepted. Only types " . implode(", ", self::$_allowedCompressionTypes) . " are permitted");
        }
        $this->compressionType = $type;
    }
    
    /**
    * Get the DwC-A zip type
    * @return string compression type
    */
    private function getCompressionType() {
        return $this->compressionType;
    }

    /**
    * Set the DwC-A meta file name (will be meta.xml by default)
    * @param string $file
    */
    public function setMetaFileName($file) {
        $this->classification->setMetaFileName($file);
    }
    
    /**
    * Get the DwC-A meta file name
    * @return string meta file name
    */
    private function getMetaFileName() {
        return $this->metaFileName;
    }
    
    /**
    * Set the DwC-A eml file name (will be eml.xml by default)
    * @param string $file
    */
    public function setEmlFileName($file) {
        $this->emlFileName = $file;
    }
    
    /**
    * Get the DwC-A eml file name
    * @return string eml file name
    */
    private function getEmlFileName() {
        return $this->emlFileName;
    }

    /**
    * Set the DwC-A core "star" file name (will be taxa.txt by default)
    * @param string $file
    */
    public function setCoreFileName($file) {
        $this->classification->setCoreFileName($file);
    }
    
    /**
    * Get the DwC-A core "star" file name
    * @return string core file name
    */
    private function getCoreFileName() {
        return $this->classification->getCoreFileName();
    }

    /**
    * Set the DwC-A synonymy file name (will be synonymy.txt by default)
    * @param string $file
    */
    public function setSynonymyFileName($file) {
        $this->classification->setSynonymyFileName($file);
    }
    
    /**
    * Get the DwC-A synonymy file name
    * @return string synonymy file name
    */
    private function getSynonymyFileName() {
        return $this->classification->getSynonymyFileName();
    }
    
    /**
    * Set the DwC-A vernaculars file name (will be vernaculars.txt by default)
    * @param string $file
    */
    public function setVernacularsFileName($file) {
        $this->classification->setVernacularsFileName($file);
    }
    
    /**
    * Get the DwC-A vernaculars file name
    * @return string vernacular file name
    */
    private function getVernacularsFileName() {
        return $this->classification->getVernacularsFileName();
    }
    
    /**
    * Set the DwC-A distribution file name (will be distribution.txt by default)
    * @param string $file
    */
    public function setDistributionFileName($file) {
        $this->classification->setDistributionFileName($file);
    }
    
    /**
    * Get the DwC-A distribution file name
    * @return string distribution file name
    */
    private function getDistributionFileName() {
        return $this->classification->getDistributionFileName();
    }
    
    /**
    * Set a MySQL dump file name
    * @param string $file
    */
    public function setMySQLFileName($file) {
        $this->MySQLFileName = $file;
    }
    
    /**
    * Get the MySQL dump file name
    * @return string MySQL file name
    */
    public function getMySQLFileName() {
        return $this->MySQLFileName;
    }
    
    /**
    * Create a temporary directory for the file to be worked on
    */
    private function createTmpPath() {
        $this->message->update("Creating a working directory.");

        $dir = md5(mktime());
        mkdir($this->getUploadPath() . '/' . $dir);
        $this->tmpPath = $this->getUploadPath() . '/' . $dir;
        copy($this->file, $this->tmpPath . '/' . basename($this->file));
        $this->workingFile = $this->tmpPath . '/' . basename($this->file);
    }
    
    /**
    * Get a file's extension
    * @param string $file
    * @return string extension
    */  
    private function getFileExtension($file) {
        return end(explode(".", strtolower($file)));
    }
    
    /**
    * Create the metadata object from the Excel 'Metadata' sheet
    * Write a UUID into a new Excel file if the UUID is not present, then overwrite the metadata object
    * Parse the metadata object
    */
    public function parseMetadata() {
        $parse_success = false;
        $this->_wkMetaData = $this->_objPHPExcel->getSheetByName('Metadata');
        if($this->_wkMetaData) {
            $this->message->update("Validating the metadata sheet.");
            
            $this->metadata = new PostBox_ExcelMetadata($this->_wkMetaData);
            
            $file_needed = false;
            
            if(!$this->metadata->getUUID()) {
                $this->metadata->createUUID();
                $this->setUUID($this->metadata->getUUID());
                $file_needed = true;
            }
            else {
                $this->setUUID($this->metadata->getUUID());
            }
            
            $this->message->update("Validating your metadata file.");
            
            //Now we can parse the file
            $parse_success = $this->metadata->parse();
            
            if($file_needed && $parse_success && $this->options['excel']) {
                $this->message->update("Saving your file with a new UUID in the metadata sheet. This may take some time.");
                
                //create writer if we do not have a UUID, then overwrite file
                switch($this->getFileExtension($this->file)) {
                    case 'xls':
                        $this->_objPHPExcelWriter = PHPExcel_IOFactory::createWriter($this->_objPHPExcel, 'Excel5');
                    break;

                    case 'xlsx':
                        $this->_objPHPExcelWriter = PHPExcel_IOFactory::createWriter($this->_objPHPExcel, 'Excel2007');
                        $this->_objPHPExcelWriter->setOffice2003Compatibility(true);
                    break;

                    default:
                        $this->_objPHPExcelWriter = PHPExcel_IOFactory::createWriter($this->_objPHPExcel, 'Excel5');
                }
                $this->_objPHPExcelWriter->save($this->tmpPath . '/tmp');
                copy($this->tmpPath . '/tmp', $this->workingFile);
                @unlink($this->tmpPath . '/tmp');
            }
        }
        else {
            $this->setError("A sheet called 'Metadata' was missing from the file");
        }
        
        return $parse_success;
        
    }

    /**
    * Create the checklist object from the 'Checklist' Excel sheet
    * Parse the checklist object
    */
    public function parseClassification() {
        $this->message->update("Parsing your checklist. This may take some time.");

        $this->_wkClassification = $this->_objPHPExcel->getSheetByName('Checklist');
        if($this->_wkClassification) {
            $this->classification = new PostBox_ExcelClassification($this->_wkClassification);
            if($this->options['synonymy']) $this->classification->setOption('make-synonymy-file');
            $this->classification->parse();
        }
        else {
            $this->setError("A sheet called 'Checklist' was missing from the file");
        }
    }
    
    /**
    * Create the DwC-A meta file from the classification object
    */
    public function createMeta() {
        $this->message->update("Creating the meta.xml file.");

        file_put_contents($this->tmpPath . '/' . $this->getMetaFileName(), $this->classification->getRawMeta());
    }

    /**
    * Create the DwC-A eml file from the parsed metadata object
    */
    public function createEml() {
        $this->message->update("Creating the eml.xml file.");

        file_put_contents($this->tmpPath . '/' . $this->getEmlFileName(), $this->metadata->getRawEml());
    }

    /**
    * Create the DwC-A core "star" file from the parsed classification object
    */
    public function createCore() {
        $this->message->update("Creating the core checklist file.");

        $fp = fopen($this->tmpPath . '/' . $this->getCoreFileName(), 'w');
        fputcsv($fp, $this->classification->getCoreHeaders(), $this->fieldTermination, $this->fieldEnclosure);
        foreach($this->classification->getCore() as $row) {
            fputcsv($fp, array_values($row), $this->fieldTermination, $this->fieldEnclosure);
        }
        fclose($fp);
    }
    
    /**
    * Create DwC-A extension file for synonymy
    */
    public function createSynonymy() {
        $synonymy = $this->classification->getSynonymy();
        
        if($synonymy) {
            $this->message->update("Creating the synonymy file.");

            $fp = fopen($this->tmpPath . '/' . $this->getSynonymyFileName(), 'w');
            fputcsv($fp, array_keys(current($synonymy)), $this->fieldTermination, $this->fieldEnclosure);
            foreach($this->classification->getSynonymy() as $value) {
                fputcsv($fp, $value, $this->fieldTermination, $this->fieldEnclosure);
            }
            fclose($fp);
        }
    }
    
    /**
    * Create DwC-A extension file for vernaculars
    */
    public function createVernaculars() {
        $vernaculars = $this->classification->getVernaculars();
        
        if($vernaculars) {
            $this->message->update("Creating the vernaculars file.");

            $fp = fopen($this->tmpPath . '/' . $this->getVernacularsFileName(), 'w');
            fputcsv($fp, array_keys(current($vernaculars)), $this->fieldTermination, $this->fieldEnclosure);
            foreach($this->classification->getVernaculars() as $value) {
                fputcsv($fp, $value, $this->fieldTermination, $this->fieldEnclosure);
            }
            fclose($fp);
        }
    }
    
    /**
    * Create DwC-A extension file for distribution
    */
    public function createDistribution() {
        $distribution = $this->classification->getDistribution();
        
        if($distribution) {
            $this->message->update("Creating the distribution file.");

            $fp = fopen($this->tmpPath . '/' . $this->getDistributionFileName(), 'w');
            fputcsv($fp, array_keys(current($distribution)), $this->fieldTermination, $this->fieldEnclosure);
            foreach($this->classification->getDistribution() as $value) {
                fputcsv($fp, $value, $this->fieldTermination, $this->fieldEnclosure);
            }
            fclose($fp);
        }
    }
    
    /**
    * Create a MySQL dump
    */
    public function createMySQLDump() {
        file_put_contents($this->tmpPath . '/' . $this->getMySQLFileName(), $this->classification->getMySQL());
    }
    
    /**
    * Zip the contents of the working download directory
    */
    public function zipArchive() {
        $this->message->update("Creating the Darwin Core Archive file.");

        $zipFileName = $this->tmpPath . '.' . $this->getCompressionType();
        switch($this->getCompressionType()) {
            case 'gzip':
                $this->_zip = new gzip_file($zipFileName);
            break;
            
            case 'bzip':
                $this->_zip = new bzip_file($zipFileName);
            break;
            
            case 'zip':
                $this->_zip = new zip_file($zipFileName);
            break;
        }
        $this->_zip->set_options(array('type' => $this->getCompressionType(), 'inmemory' => 0, 'recurse' => 0, 'storepaths' => 0, 'name' => $zipFileName));
        
        if(!$this->options['excel']) @unlink($this->workingFile);
        
        $rawfiles = scandir($this->tmpPath);
        $files = array();
        foreach($rawfiles as $rawfile) {
            if($rawfile !== '.' && $rawfile !== '..') $files[] = $this->tmpPath . '/' . $rawfile;
        }
        $this->_zip->add_files($files);
        $this->_zip->create_archive();
        $this->_zip->save_file();
        @unlink($zipFileName . '.tmp'); //remove tmp file that might be left behind
        foreach($files as $filename) {
            @unlink($filename);
        }
        @rmdir($this->tmpPath);
        
        //move the file to the download directory
        rename($zipFileName, $this->getDownloadPath() . '/' . $this->getUUID() . '.' . $this->getCompressionType());
    }
    
    /**
    * POST the DwC-A file to the Global Names Architecture Classification and List Repository
    * @param string $url
    */
    public function postGNACLR() {
        $this->message->update("Posting to the Global Names Architecture Classification and List Repository.");

        $post_data = array(
            'uuid' => $this->getUUID(),
            'file' => '@'.$this->getDownloadPath() . '/'. $this->getUUID() . '.' . $this->getCompressionType(),
            'submit' => 'Upload',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, GNACLR_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    /**
    * Set a UUID, if one not passed as @param, a new one is created using the UUID class
    * UUID is also validated
    * @param string $UUID
    */
    private function setUUID($UUID = '') {
        if(!$UUID) {
            $UUID = UUID::v4();
        }
        $this->UUID = $UUID;
    }

    /**
    * Get the UUID (and set one if there isn't a metadata object of if the UUID hasn't already been set)
    * @return string UUID
    */
    private function getUUID() {
        if(!$this->metadata && !$this->UUID) {
            $this->setUUID();
        }
        return $this->UUID;
    }

    /**
    * Check to see if an email address is validly formed
    * @param string $email
    * @return true/false
    */
    public function checkEmailAddress($email) {
        if(preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/',$email)) return true;
        return false;
    }

    /**
    * Send the DwC-A result as attachment to an email address by first setting the SMTP host address
    * @param string $smtp_host
    * @param string $email;
    */
    public function emailFile($success = true) {
        if($this->checkEmailAddress($this->options['email'])) {
            $mail = new mimemail();

            $mail->addrecipient($this->options['email'], "To");
            $mail->setsender(SYSTEM_EMAIL, SYSTEM_NAME);
            $mail->setreturn(SYSTEM_EMAIL);
            $mail->setsubject(SYSTEM_SUBJECT . " results");

            if($success) {
                $mail->setplain(SUCCESS_MESSAGE);

                $file_data = file_get_contents($this->getDownloadPath() . '/'. $this->getUUID() . '.' . $this->getCompressionType());
                $mail->addattachment($this->getUUID() . "." . $this->getCompressionType(), $file_data);
            }
            else {
                $errors = array_merge(self::getErrors(), PostBox_ExcelMetadata::getErrors(), PostBox_ExcelClassification::getErrors());
                $message  = FAILED_MESSAGE;
                $message .= "Errors found:\n\n";
                $message .= implode("\n", $errors);
                
                $mail->setplain($message);

                $file_data = file_get_contents($this->workingFile);
                $mail->addattachment(basename($this->workingFile), $file_data);
            }
            
            
            $mail->compilemail();
            
            $mailserver = new smtpmail(SMTP_HOST);
            $mailserver->openconnection();
            if($mailserver->geterrors()) {
                unset($mailserver);
                $mailserver = new smtpmail(SMTP_HOST_ALT);
                $mailserver->openconnection();
            }
            $mailserver->sendmail($mail->headers, $mail->message);
            $mailserver->closeconnection();
        }
        else {
            $this->setError("The email address was not validly formed.");
        }
    }
    
    /**
    * Clean up method to remove temporary directory and its files
    */
    public function cleanUp() {
        if(is_dir($this->tmpPath)) {
            foreach(scandir($this->tmpPath) as $file) {
                if($file != '.' && $file != '..') @unlink($this->tmpPath . '/' . $file);
            }
            @rmdir($this->tmpPath);
        }
    }
    
    /**
    * Log errors in parsing into a text file for later examination
    * @param string $txt
    */
    private function log($txt) {
        file_put_contents(self::$_logPath . '/log.txt', $txt . "\n", FILE_APPEND);
    }
    
    /**
    * Set an error message
    * @param string $message
    */
    private function setError($message) {
        self::$_errors[] = $message; 
    }

    /**
    * Get error messages
    * @return string errors
    */
    public static function getErrors() {
        return self::$_errors;
    }
}

?>