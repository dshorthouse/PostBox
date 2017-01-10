<?php

/**************************************************************************

File: postbox.loader.class.php

Description: This class loads a file object from a multipart form submission 
into a working directory then inserts data into both a PHP-Resque (Redis) 
message queue.

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/

//conf
require_once (dirname(__FILE__) . '/conf/conf.php');

//mailer class
require_once (dirname(__FILE__) . '/mail.php');

class PostBox_Loader {
    
    /* Allowed Excel file extensions */
    public static $_allowedExtensions = array(
        'xls',
        'xlsx',
        'ods'
    );
    
    /* Allowed zip extensions/types */
    public static $_allowedCompressionTypes = array(
        'zip',
        'gzip',
    );
    
    /* Error holder */
    public $_errors = array();
    
    /**
    * Constructor to set options and create php-resque instance
    *
    * @param string $format
    * @param string $email
    * @param true/false $gnaclr
    */
    function __construct(
                    $format = 'gzip', 
                    $email = '', 
                    $gnaclr = false, 
                    $synonymy = false, 
                    $excel = false, 
                    $mysql = false, 
                    $resque = false) {
                        
        $this->file = '';
        $this->extensions = array();
        $this->uploadPath = $_SERVER['DOCUMENT_ROOT'] . '/upload_queue';
        $this->jobId = '';
        $this->options = array(
            'format'    => $format, 
            'email'     => $email, 
            'gnaclr'    => $gnaclr,
            'synonymy'  => $synonymy,
            'excel'     => $excel,
            'mysql'     => $mysql,
        );
        $this->resque = $resque;
        $this->redis_server = false;
        
        if($this->resque) {
            //resque classes
            require_once (dirname(__FILE__) . '/Resque/Resque.php');
            
            try {
                @Resque::setBackend(RESQUE_ADDRESS.':'.RESQUE_PORT);
                $this->redis_server = Resque::$redis;
            }
            catch(Exception $e) {
                //throw something here
            }
        }
    }
    
    /**
    * Destructor to unset php-resque and clear memory
    */
    function __destruct() {
        unset($this->redis_server);
    }

    /**
    * Handle an uploaded file array & move it to the upload directory
    * @param array $file
    */
    public function uploadFile($file = array()) {
        $this->file = md5(time()) . '-' . $file['name'];
        $uploadPath = $this->getUploadPath() . '/' . $this->file;
        
        if(!in_array($this->getFileExtension($file['name']), self::$_allowedExtensions)) {
            $this->setError("Only files with extensions " . implode(",", self::$_allowedExtensions) . " are permitted");
        }
        elseif(move_uploaded_file($file['tmp_name'], $uploadPath)) {

            $data = array(
                'file' => $this->file,
                'options' => $this->options,
            );
            $message = "The file has been queued.";

            if($this->redis_server) {
                //queue the job
                $this->jobId = Resque::enqueue(RESQUE_TUBE, 'Postbox_Job', $data, true, $message);
                return true;
            }
            else {
                $this->setError("There was an error putting the file into the queue.");
            }
        }
        else {
            $this->setError("There was an error with file upload.");
        }
        
        return false;
    }

    /**
    * Set a system upload (working) path
    * @param string $path
    */
    public function setUploadPath($path) {
        $this->uploadPath = $path;
    }
    
    /**
    * Get the system upload path where Excel files are worked on
    * @return string system upload path
    */
    private function getUploadPath() {
        return $this->uploadPath;
    }
    
    /**
    * Get the queue id
    * @return int queue id
    */
    public function getQueueID() {
        return $this->jobId;
    }

    /**
    * Get a file's extension
    * @param string $file
    * @return string extension
    */  
    private function getFileExtension($file) {
        $file_string_arr = explode(".", strtolower($file));
        return end($file_string_arr);
    }
    
    /**
    * Check to see if an email address is validly formed
    * @param string $email
    * @return true/false
    */
    public static function checkEmailAddress($email) {
        if(preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/',$email)) return true;
        return false;
    }

    /**
    * Send the DwC-A result as attachment to an email address by first setting the SMTP host address
    * @param string $smtp_host
    * @param string $email
    */
    public function emailMessage() {
        if(self::checkEmailAddress($this->options['email'])) {
            $mail = new mimemail();

            $mail->addrecipient($this->options['email'], "To");
            $mail->setsender(SYSTEM_EMAIL, SYSTEM_NAME);
            $mail->setreturn(SYSTEM_EMAIL);
            $mail->setsubject(SYSTEM_SUBJECT);

            $message  = QUEUE_MESSAGE;
            $message .= "Check status at: " . BASE_URL . "/?q=" . $this->jobId;
            $mail->setplain($message);
            
            $mail->compilemail();
            
            $mailserver = new smtpmail(SMTP_HOST);
            $mailserver->openconnection();
            if($mailserver->geterrors()) {
                unset($mailserver);
                $mailserver = new smtpmail(SMTP_HOST_ALT);
                $mailserver->openconnection();
            }
            if(POSTBOX_ENV == 'production') $mailserver->sendmail($mail->headers, $mail->message);
            $mailserver->closeconnection();
        }
        else {
            $this->setError("The email address was not validly formed.");
        }
    }

    /**
    * Set an error message
    * @param string $message
    */
    private function setError($message) {
        $this->_errors[] = $message; 
    }

    /**
    * Get error messages
    * @return string errors
    */
    public function getErrors() {
        return $this->_errors;
    }
    
}

?>