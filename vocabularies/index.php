<?php
require_once('../Classes/postbox.vocabularies.class.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
<title>Biological Survey of Canada Checklists :: PostBox Controlled Vocabularies</title>
<meta name="dc:title" content="Biological Survey of Canada Checklists PostBox Controlled Vocabularies"></meta>
<meta name="dc:subject" content="Biological Survey of Canada Checklists PostBox Controlled Vocabularies"></meta>
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
    
    <div id="vocabularies">
    
    <h2>Recognized Columns and Values</h2>
    
    <div class="colmask doublepage">
        <div class="colleft">
            <div class="col1">
                <h3>Taxonomic Ranks</h3>
                  <div class="description">Spreadsheet column name: <span>Rank</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$allRanks) . "</li>\n";
                    echo "</ul>\n";
                  ?>
                
                <h3>Nomenclatural Codes</h3>
                  <div class="description">Spreadsheet column name: <span>Nomenclatural Code</span></div>
                  <?php
                    echo "<ul>\n";
                    foreach(PostBox_Vocabularies::$nomenclaturalCodes as $key => $value) {
                      echo "<li><span class=\"code\">" . $key."</span><span class=\"term\">" . $value . "</span></li>\n";
                    }
                    echo "</ul>\n";
                  ?>

                <h3>Taxonomic Status</h3>
                  <div class="description">Spreadsheet column name: <span>Taxonomic Status</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$taxonomicStatus) . "</li>\n";
                    echo "</ul>\n";
                  ?>
                
                <h3>Country Codes</h3>
                  <div class="description">Spreadsheet column name: <span>Countries</span><br>Example: CA|US|RU</div>
                  <?php
                    echo "<ul>\n";
                    foreach(PostBox_Vocabularies::$countries as $key => $value) {
                      echo "<li><span class=\"code\">" . $key."</span><span class=\"term\">" . $value . "</span></li>\n";
                    }
                    echo "</ul>\n";
                  ?>

                <h3>Provinces, Territories and States</h3>
                  <div class="description">Spreadsheet column name: <span>State Provinces</span><br>Example: CA-BC|CA-AB|CA-SK|US-WY</div>
                  <?php
                    echo "<ul>\n";
                    foreach(PostBox_Vocabularies::$stateProvinces as $key => $value) {
                      echo "<li><span class=\"code\">" . $key."</span><span class=\"term\">" . $value . "</span></li>\n";
                    }
                    echo "</ul>\n";
                  ?>
                
                <h3>Occurrence Status</h3>
                  <div class="description">Spreadsheet column name: <span>Occurrence Status</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$occurrenceStatus) . "</li>\n";
                    echo "</ul>\n";
                  ?>

                <h3>Establishment Means</h3>
                  <div class="description">Spreadsheet column name: <span>Establishment Means</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$establishmentMeans) . "</li>\n";
                    echo "</ul>\n";
                  ?>

                <h3>Threat Status</h3>
                  <div class="description">Spreadsheet column name: <span>Threat Status</span><br>Example: DD</div>
                  <?php
                    echo "<ul>\n";
                    foreach(PostBox_Vocabularies::$threatStatus as $key => $value) {
                      echo "<li><span class=\"code\">" . $key."</span><span class=\"term\">" . $value . "</span></li>\n";
                    }
                    echo "</ul>\n";
                  ?>
                
            </div>
            <div class="col2">
                <h3>Terrestrial Ecozones</h3>
                  <div class="description">Spreadsheet column name: <span>Terrestrial Ecozone</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$terrestrialEcozones) . "</li>\n";
                    echo "</ul>\n";
                  ?>
                
                <h3>Canadian Ecozones</h3>
                  <div class="description">Spreadsheet column name: <span>Canadian Ecozone</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$canadianEcozones) . "</li>\n";
                    echo "</ul>\n";
                  ?>
                
                <h3>Canadian Ecoprovinces</h3>
                  <div class="description">Spreadsheet column name: <span>Canadian Ecoprovince</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$canadianEcoprovinces) . "</li>\n";
                    echo "</ul>\n";
                  ?>

                <h3>Canadian Ecoregions</h3>
                  <div class="description">Spreadsheet column name: <span>Canadian Ecoregion</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$canadianEcoregions) . "</li>\n";
                    echo "</ul>\n";
                  ?>
                
                <h3>Canadian Ecodistricts</h3>
                  <div class="description">Spreadsheet column name: <span>Canadian Ecodistrict</span></div>
                  <?php
                    echo "<ul>\n";
                    echo "<li>" . implode("</li>\n<li>", PostBox_Vocabularies::$canadianEcodistricts) . "</li>\n";
                    echo "</ul>\n";
                  ?>
            </div>
        </div>
    </div>
    
    </div>
    
    <div style="clear:both"></div>
    
    <div id="footer">
    </div> <!-- /footer -->
    
    </div> <!-- /wrapper -->
</body>
</html>