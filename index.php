<?php

/**************************************************************************

File: index.php

Description: A front-end to the Biological Survey of Canada PostBox service
that accepts Excel templates and parses them into Darwin Core Archives
using a high performance PHP-based Resque/Redis queuing system.

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

Please examine Readme.txt for system set-up

**************************************************************************/

require_once (dirname(__FILE__) . '/Classes/postbox.init.class.php');

$postbox = new PostBox_Init;
$postbox->init(); // true = worker spawned with upload

$messages = $postbox->getMessages();
$queueID = $postbox->getQueueID();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
<meta name="dc:title" content="Biological Survey of Canada Checklists PostBox"></meta>
<meta name="dc:subject" content="Biological Survey of Canada Checklists PostBox"></meta>
<meta name="dc.format" scheme="IMT" content="text/html"></meta>
<meta name="dc.type.documentType" content="Web Page"></meta>
<title>Biological Survey of Canada Checklists :: PostBox</title>
<link type="text/css" rel="stylesheet" media="all" href="/css/screen.css"></link>
<link type="text/css" rel="stylesheet" href="/css/smoothness/jquery-ui-1.8.10.custom.css"></link>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"></link>
<script type="text/javascript" src="/js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="/js/jquery-ui-1.8.10.custom.min.js"></script>
<script type="text/javascript" src="/js/jquery.color.js"></script>
<script type="text/javascript" src="/js/jquery.ba-dotimeout.min.js"></script>
<script type="text/javascript" src="/js/postbox.js"></script>
<script type="text/javascript">
$(function(){
<?php print $postbox->getResponseData(); ?>
});
</script>
<script type="text/javascript">
<!--//--><![CDATA[//><!--
jQuery.extend(PostBox.settings, { "baseUrl": "http://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '/'; ?>", "queueID" : <?php if($messages && isset($messages['queue'])): echo json_encode($messages['queue']); elseif($queueID): echo "[\"$queueID\"]"; else: echo "\"\""; endif; ?> });
//--><!]]>
</script>
</head>

<body>

<div id="wrapper">

<div id="header">
<img src="images/bscgreenlogo.jpg" id="logo">
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

<div id="intro">Make a Darwin Core Archive file for your checklist manuscript submission.</div>

<div id="main">
    
<div id="content-wrapper">

<div id="content" class="section">
    <h2 class="header">Upload Template</h2>
    <?php if(!$postbox->resque): ?>
      <p>Sorry, the queue server is currently offline. A system administrator has been alerted to the problem. Please try again later.</p>
    <?php else: ?>
      <?php if($messages || $queueID): ?>
        <div id="results" class="section">
          <?php if($messages && isset($messages['queue'])): ?>
            <?php for($i=0; $i<count($messages['queue']); $i++): ?>
              <div id="postbox-queue-<?php echo $i; ?>" class="postbox-queue">
                <div class="postbox-throbber"></div>
                <div class="postbox-results"></div>
              </div>
            <?php endfor; ?>
          <?php else: ?>
            <div id="postbox-queue-0" class="postbox-queue">
              <div class="postbox-throbber"></div>
                <div class="postbox-results"></div>
              </div>
          <?php endif; ?>
          <?php unset($messages['queue']); ?>

          <?php foreach($messages as $type => $message): ?>
            <?php if(is_array($message)): ?>
              <?php foreach($message as $content): ?>
                <?php if($content): ?>
                  <p class="<?php echo $type; ?>"><?php echo strtoupper($type) . ": " . $content; ?></p>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              <?php if($message): ?>
                <p class="<?php echo $type; ?>"><?php echo strtoupper($type) . ": " . $message; ?></p>
              <?php endif; ?>
            <?php endif; ?>
          <?php endforeach; ?>
        </div> <!-- /results -->
      <?php endif; ?>

<form action="" method="post" enctype="multipart/form-data" id="postbox">
    <ol>
        <li>
            <label for="file" class="excel-label">Spreadsheet template:</label>
            <input type="file" name="file" id="file" class="form-item"></input>
            <span class="description">File extensions xls, xlsx, or ods</span>
        </li>
        <li>
            <label for="email" class="email-label">Email address<span class="required">*</span>:</label>
            <input type="text" name="email" id="email" class="form-item createinput" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ""; ?>"></input>
            <span id="email_validation" class="real-time-validation"> </span>
            <span class="description">Required. Receive the result as an attachment.</span>
        </li>
<!--
        <li>
            <?php $format_gzip_checked = (isset($_POST['format']) && $_POST['format'] == "gzip") ? " checked=\"checked\"" : ""; ?>
            <?php $format_zip_checked = (!isset($_POST['format']) || $_POST['format'] == "zip") ? " checked=\"checked\"" : ""; ?>
            <label class="zip-label">Output format:</label>
            <input id="zipOutput" type="radio" name="format" value="zip" class="form-item"<?php echo $format_zip_checked; ?>></input>
            <span>zip</span>
            <input id="gzipOutput" type="radio" name="format" value="gzip" class="form-item"<?php echo $format_gzip_checked; ?>></input>
            <span>gzip</span>
        </li>
        <li>
            <?php $synonymy_checked = (isset($_POST['writeSynonymy'])) ? " checked=\"checked\"" : ""; ?>
            <label for="writeSynonymy" class="synonymy-label">Synonymy:</label>
            <input type="checkbox" name="writeSynonymy" id="writeSynonymy" class="form-item"<?php echo $synonymy_checked; ?>></input>
            <span class="description">Put synonyms in a separate file within the archive. When unchecked, synonyms will be placed in the core file (recommended).</span>
        </li>
        <li>
            <?php $excel_checked = (isset($_POST['writeExcel'])) ? " checked=\"checked\"" : ""; ?>
            <label for="writeExcel" class="excel-label">Include Excel File:</label>
            <input type="checkbox" name="writeExcel" id="writeExcel" class="form-item"<?php echo $excel_checked; ?>></input>
            <span class="description">Include your Excel file in the download.</span>
        </li>
        <li>
            <?php $mysql_checked = (isset($_POST['writeMySQL'])) ? " checked=\"checked\"" : ""; ?>
            <label for="writeMySQL" class="mysql-label">Include MySQL file:</label>
            <input type="checkbox" name="writeMySQL" id="writeMySQL" class="form-item"<?php echo $mysql_checked; ?>></input>
            <span class="description">Include a MySQL dump file in the download.</span>
        </li>
-->
        <li>
            <div class="submit">
                <input type="submit" name="submit" id="submit" value="Upload" class="form-item button"></input>
            </div>
        </li>
        <li>
            <input name="format" type="hidden" id="zipOutput" value="zip"></input>
            <input name="cmd" type="hidden" id="cmd" value="formSubmitted"></input>
        </li>
    </ol>
</form>
   <?php endif; ?>

</div> <!-- /content -->

</div> <!-- /content-wrapper -->

<div id="sidebar">

<div id="download" class="section">
  <h2 class="header">Download Template</h2>
    <div class="section-content">
    <p class="instructions">Samples and instructions are found in each download.</p>
    <img src="images/excel-icon.png" alt="Excel">
    <ul>
        <li><span class="type">Full Hierarchy</span> (ranks as headers)
            <span class="file">
            <a href="example-files/BSC_Template_FullHierarchy_Rank_0.1.xls">Excel 97-2004</a><span class="required">*</span>, 
            <a href="example-files/BSC_Template_FullHierarchy_Rank_0.1.xlsx">Excel 2007</a>
            </span>
        </li>
        <li><span class="type">Full Hierarchy</span> (ranks optional)
            <span class="file">
            <a href="example-files/BSC_Template_FullHierarchy_Taxon_0.1.xls">Excel 97-2004</a><span class="required">*</span>, 
            <a href="example-files/BSC_Template_FullHierarchy_Taxon_0.1.xlsx">Excel 2007</a>
            </span>
        </li>
        <li><span class="type">Parent-Child</span> 
            <span class="file">
            <a href="example-files/BSC_Template_ParentChild_0.1.xls">Excel 97-2004</a><span class="required">*</span>, 
            <a href="example-files/BSC_Template_ParentChild_0.1.xlsx">Excel 2007</a>
            </span>
        </li>
    </ul>

    <div style="clear:both"></div>

    <p class="caution">*Caution: Excel 97-2004 has a 65,536 row limit</p>
    
    </div>
</div> <!-- /download -->

<div class="section">
<h2>FAQs</h2>
<ul class="section-list">
<li><a href="faq/#q1">How do I get help with the spreadsheet template?</a></li>
<li><a href="faq/#q2">What is a Darwin Core Archive?</a></li>
</ul>
</div>

<div class="section">
<h2>Extras</h2>
<ul class="section-list">
<li><a href="vocabularies/">Recognized columns and values</a></li>
<li><a href="api/">API</a></li>
</ul>
</div>

</div> <!-- /sidebar -->

</div> <!-- /main -->

<div style="clear:both"></div>

<div id="footer">
</div> <!-- /footer -->

</div> <!-- /wrapper -->

</body>

</html>
