<?php
/**************************************************************************

File: index.php

Description: Administrative script to monitor and manage queue for the
Biological Survey of Canada PostBox service

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/
require_once ('../Classes/conf/conf.php');
require_once ('../Classes/Resque/Resque.php');
require_once ('../Classes/Resque/Resque/Stat.php');
require_once ('../Classes/Resque/Resque/Job/Message.php');

try {
    @Resque::setBackend(RESQUE_ADDRESS.':'.RESQUE_PORT);
    $stat = new Resque_Stat;
}
catch(Exception $e) {
    exit;
}

$stats = array(
    'pending' =>  Resque::size(RESQUE_TUBE),
    'workers' =>  $stat->get('workers'),
    'processed' => $stat->get('processed'), 
    'failed' => Resque::redis()->llen('queue:'.RESQUE_TUBE_FAILED)
);

$failed = array();
for($i=0; $i<$stats['failed']; $i++) {
  $id = json_decode(Resque::redis()->lindex('queue:'.RESQUE_TUBE_FAILED, $i));
  $message = new Resque_Job_Message($id->id);
  $failed[$id->id] = array(
    'file' => $id->file,
    'data' => $message->getAll(),
  );
}

$row = 1;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
<title>Biological Survey of Canada Checklists :: PostBox Administration</title>
<meta name="dc:title" content="Biological Survey of Canada Checklists PostBox"></meta>
<meta name="dc:subject" content="Biological Survey of Canada Checklists PostBox"></meta>
<meta name="dc.format" scheme="IMT" content="text/html"></meta>
<meta name="dc.type.documentType" content="Web Page"></meta>
<link type="text/css" rel="stylesheet" media="all" href="../css/screen.css"></link>
</head>

<body>

    <div id="wrapper">
    
    <div id="header">
    <img src="../images/bscgreenlogo.jpg" id="logo">
    <div id="name">
      <h1 id="site-name">Biological Survey of Canada Checklists PostBox</h1>
      <div id="headerImage"></div>
      <div id="navbar">
        <ul class="menu">
          <li><a href="/">home</a></li>
          <li><a href="http://www.biologicalsurvey.ca">journal home</a></li>
          <li><a href="/vocabularies">vocabularies</a></li>
          <li><a href="/faq">faq</a></li>
          <li><a href="/api">api</a></li>
        </ul>
      </div>
    </div>
    </div>
    
    <div id="intro">
    </div>
    
    <div id="admin" class="main-wrapper">

    <h2>PostBox Administration</h2>

    <div id="stats">
        <ul>
        <?php foreach($stats as $key => $stat): ?>
            <li><?php echo $key . ": " . $stat; ?>
        <?php endforeach; ?>
        </ul>
    </div>
    
    <?php if($failed): ?>
    <h2>Failed Jobs</h2>
    <div id="failed">
        <table class="admin-postbox">
            <thead>
                <tr>
                    <td>Created</td>
                    <td>Messages</td>
                    <td>Download</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach($failed as $key => $fail): ?>
                    <?php $rowClass =  ($row % 2 != 0) ? 'odd' : 'even'; ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td width="20%" class="aligned"><?php echo date('c', $fail['data']['updated']); ?></td>
                        <td><?php echo (is_array($fail['data']['message'])) ? implode("<br>", $fail['data']['message']) : $fail['data']['message']; ?></td>
                        <td width="10%" class="aligned"><a href="/failed/<?php echo $fail['file']; ?>">file</a></td>
                    </tr>
                <?php $row++; endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    </div> <!-- /admin -->

    <div id="footer">
    </div> <!-- /footer -->
    
    </div> <!-- /wrapper -->
       
</body>

</html>