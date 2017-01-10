<?php
require_once ('../Classes/conf/conf.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
<title>Biological Survey of Canada Checklists :: PostBox API</title>
<meta name="dc:title" content="Biological Survey of Canada Checklists PostBox API"></meta>
<meta name="dc:subject" content="Biological Survey of Canada Checklists PostBox API"></meta>
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
    </div> <!-- /intro -->

    <div id="api" class="main-wrapper">
        <h2>API</h2>
        <div>Send multipart/form-data POST to <?php echo BASE_URL; ?> with the following parameters:
            <dl>
                <dt>cmd<sup>*</sup></dt>
                <dd>required variable whose value must = api</dd>

                <dt>file<sup>*</sup></dt>
                <dd>myfile.xls or myfile.xlsx (either xls or xlsx extension)</dd>

                <dt>email</dt>
                <dd>myemail@email.com (email address of user for notification when complete)</dd>

                <dt>format</dt>
                <dd>Compression format for the DwC archive, zip or gzip</dd>

                <dt>writeSynonymy</dt>
                <dd>presence of variable will write a separate synonymy file into the archive instead of into the core taxa file</dd>

                <dt>writeExcel</dt>
                <dd>presence of variable will write Excel file into the archive. WARNING: this may take up to 6X longer to produce if you do not already have a UUID</dd>

                <dt>writeMySQL</dt>
                <dd>presence of variable will write a MySQL dump file into the archive</dd>

                <dt>callback</dt>
                <dd>myfunction (optional callback parameter for JSONP response)</dd>
            </dl>
            <h4>Initial Response</h4>
    <pre>
    { "queue" : "<?php echo BASE_URL; ?>/queue/?q=&lt;&lt;integer&gt;&gt;" }
    </pre>
            <h4>Queue Responses<span class="subtitle">By visiting queue URL provided in initial response</span></h4>
            <h5>queued for parsing</h5>
    <pre>
    { 
      "status" : "queued",
      "message" : "Your file is in the queue.",
      "created" : "1284587556",
      "changed" : "1284587556",
      "jobqueue ": { ...details about the job queue... }
    }
    </pre>
            <h5>error in parsing</h5>
    <pre>
    { 
      "status" : "error",
      "message" : "The 'Title' cell title is missing in the Metadata sheet&lt;br&gt;
    The 'Publication Date (MM/DD/YYYY)' cell title is missing in the Metadata sheet&lt;br&gt;
    The 'Expected Citation' cell title is missing in the Metadata sheet&lt;br&gt;
    The 'Abstract' cell title is missing in the Metadata sheet&lt;br&gt;
    The 'Resource Language' cell is missing in the Metadata sheet&lt;br&gt;
    The 'Metadata Language' cell is missing in the Metadata sheet&lt;br&gt;
    The 'Contact' cell title is missing in the Metadata sheet&lt;br&gt;
    The 'Metadata Author' cell title is missing in the Metadata sheet&lt;br&gt;
    The 'First Name' cell title is missing in the Metadata sheet&lt;br&gt;
    The 'Last Name' cell title is missing in the Metadata sheet&lt;br&gt;
    The 'Organization' cell title is missing in the Metadata sheet",
      "created" : "1284587556",
      "changed" : "1284587833",
      "jobqueue" : { ..details about the job queue... }
    }
    </pre>
            <h5>parsing successful</h5>
    <pre>
    {
      "status" : "complete",
      "message" : "Your Darwin Core Archive is now ready.",
      "created" : "1284588040",
      "changed" : "1284588054",
      "file" : "<?php echo BASE_URL; ?>/download/&lt;&lt;UUID&gt;&gt;.gzip",
      "jobqueue" : { ..details about the job queue.. }
    }
    </pre>

    </div> <!-- /api -->
    
    <div id="footer">
    </div> <!-- /footer -->
    
    </div> <!-- /wrapper -->
</body>
</html>