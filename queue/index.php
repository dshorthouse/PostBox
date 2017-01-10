<?php

//conf
require_once ('../Classes/conf/conf.php');

//Resque class
require_once ('../Classes/Resque/Resque.php');
require_once ('../Classes/Resque/Resque/Job/Status.php');
require_once ('../Classes/Resque/Resque/Job/Message.php');

$q = (isset($_GET['q'])) ? $_GET['q'] : '';
$callback = (isset($_GET['callback'])) ? $_GET['callback'] : '';

$result = array();

if($q) {
    try {
        @Resque::setBackend(RESQUE_ADDRESS.':'.RESQUE_PORT);
        $redis_server = Resque::$redis;
    }
    catch(Exception $e) {
        //throw something here
        exit;
    }
    
    if($redis_server) {
        $status = new Resque_Job_Status($q);
        $status_val = $status->get();
        
        $message = new Resque_Job_Message($q);
        $message_body = $message->get();
        
        if(is_array($message_body)) $message_body = implode("<br>", $message_body);
        
        switch($status_val) {
            case Resque_Job_Status::STATUS_WAITING:
                $result['status'] = "queued";
                $result['jobqueue'] = Resque::size(RESQUE_TUBE);
                $result['message'] = $message_body;
            break;
            
            case Resque_Job_Status::STATUS_RUNNING:
                $result['status'] = "working";
                $result['jobqueue'] = Resque::size(RESQUE_TUBE);
                $result['message'] = $message_body;
            break;
            
            case Resque_Job_Status::STATUS_FAILED:
                $result['status'] = "failed";
                $result['jobqueue'] = Resque::size(RESQUE_TUBE);
                $result['message'] = $message_body;
            break;
            
            case Resque_Job_Status::STATUS_COMPLETE:
                $result['status'] = "complete";
                $result['message'] = $message_body;
            break;
        }
        
        if(!$status_val) {
            $result['status'] = "error";
            $result['message'] = "That job no longer exists";
        }
    }
}

$result = json_encode($result);

// Now produce the response
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if($callback) {
    header('Content-type: text/javascript');
    $result = $callback . '(' . $result . ');';
}
else {
    header('Content-type: application/json');
}

echo $result;

?>