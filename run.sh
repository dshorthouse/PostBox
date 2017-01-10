#!/opt/local/bin/php
<?php

require_once (dirname(__FILE__) . '/Classes/conf/conf.php');
require_once (dirname(__FILE__) . '/Classes/Resque/Resque.php');
require_once (dirname(__FILE__) . '/Classes/Resque/Resque/Worker.php');

//worker class for PHP-Resque
class PostBox_Job {
    public function setUp() {}
    public function perform() {
        require_once (dirname(__FILE__) . '/Classes/postbox.producer.class.php');
        $processor = new PostBox_Producer($this, true);
        $processor->run();
    }
    public function tearDown() {}
}

try {
      @Resque::setBackend(RESQUE_ADDRESS.':'.RESQUE_PORT);
      $worker = new Resque_Worker(RESQUE_TUBE);
      $worker->work();
}
catch(Exception $e) {
    return;
}

?>