<?php

/**************************************************************************

File: postbox.init.class.php

Description: This is the initialization class to execute setup

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/

require_once (dirname(__FILE__) . '/conf/conf.php');
require_once (dirname(__FILE__) . '/Resque/Resque.php');
require_once (dirname(__FILE__) . '/Resque/Resque/Worker.php');
require_once (dirname(__FILE__) . '/mail.php'); 
require_once (dirname(__FILE__) . '/postbox.loader.class.php');

//worker class for PHP-Resque
class PostBox_Job {
    public function perform() {
        require_once (dirname(__FILE__) . '/postbox.producer.class.php');
        $processor = new PostBox_Producer($this, true);
        $processor->run();
    }
}

class PostBox_Init {

  public $resque = true;

  private $queueCheck;
  private $queueID;
  private $responseData;
  private $messages = array();

  function __construct() {
  }

  public function init() {
    
    $this->queueID = (isset($_GET['q'])) ? $_GET['q'] : false;

    //see if Resque/Redis is alive
    try {
        Resque::setBackend(RESQUE_ADDRESS.':'.RESQUE_PORT);
    }
    catch(Exception $e) {
        $this->resque = false;
        $mail = new mimemail();

        $mail->addrecipient(ADMIN_EMAIL, "To");
        $mail->setsender(ADMIN_EMAIL, "PostBox");
        $mail->setreturn(ADMIN_EMAIL);
        $mail->setsubject("PostBox Resque Offline");

        $message  = "The PostBox Redis server is offline for ".BASE_URL.". Submissions are not currently being processed.";
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

    if(isset($_POST['cmd'])) {
      switch($_POST['cmd']) {
        case 'formSubmitted':
            if (is_array($_FILES)) {
                foreach ($_FILES as $file) {
                    $format = (isset($_POST['format'])) ? $_POST['format'] : 'gzip';
                    $email = (isset($_POST['email'])) ? @strip_tags(trim($_POST['email'])) : '';
                    $writeSynonymy = (isset($_POST['writeSynonymy'])) ? true : false;
                    $writeExcel = (isset($_POST['writeExcel'])) ? true : false;
                    $writeMySQL = (isset($_POST['writeMySQL'])) ? true : false;
                    $gnaclr = (isset($_POST['gnaclr'])) ? true : false;

                    if($email != "" && PostBox_Loader::checkEmailAddress($email)) {
                      $loader = new PostBox_Loader($format, $email, $gnaclr, $writeSynonymy, $writeExcel, $writeMySQL, $this->resque);
                      $loaded = $loader->uploadFile($file);
                      if($email && $loaded && POSTBOX_ENV == 'production') $loader->emailMessage();

                      if($loader->getErrors()) {
                        $this->messages['error'][] = implode('<br>', $loader->getErrors());
                      }
                      elseif($loaded) {
                        $this->messages['queue'][] = $loader->getQueueID();
                      }
                    }
                    else {
                      $this->responseData .= '$("#email").css({\'background-color\':\'#FFB6C1\'});';
                    }

                }
            }
        break;

        case 'api':
            if (is_array($_FILES)) {
                foreach ($_FILES as $file) {
                    $format = (isset($_POST['format'])) ? $_POST['format'] : 'gzip';
                    $email = (isset($_POST['email'])) ? @strip_tags(trim($_POST['email'])) : '';
                    $writeSynonymy = (isset($_POST['writeSynonymy'])) ? true : false;
                    $writeExcel = (isset($_POST['writeExcel'])) ? true : false;
                    $writeMySQL = (isset($_POST['writeMySQL'])) ? true : false;
                    $gnaclr = (isset($_POST['gnaclr'])) ? true : false;

                    if($email != "" && PostBox_Loader::checkEmailAddress($email)) {
                      $loader = new PostBox_Loader($format, $email, $gnaclr, $writeSynonymy, $writeExcel, $writeMySQL, $this->resque);
                      $loaded = $loader->uploadFile($file);
                      if($email && $loaded && POSTBOX_ENV == 'production') $loader->emailMessage();

                      if($loader->getErrors()) {
                        $this->messages['error'][] = implode('<br>', $loader->getErrors());
                      }
                      elseif($loaded) {
                        $this->messages['queue'][] = BASE_URL . '/queue/?q=' . $loader->getQueueID();
                      }
                    }

                }
            }

            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Content-type: application/json');
            echo json_encode($this->messages);
            exit;
        break;

        }
    }

  }

  public function getQueueID() {
    return $this->queueID;
  }

  public function getMessages() {
    return $this->messages;
  }

  public function getResponseData() {
    return $this->responseData;
  }

}

?>