<?php

/**************************************************************************

File: postbox.mysql.class.php

Description: This class produces the equivalent of a MySQL dump file for 
optional inclusion in a Darwin Core Archive.

Developer: David P. Shorthouse
Organization: Marine Biological Laboratory, Biodiversity Informatics Group
Email: davidpshorthouse@gmail.com

License: LGPL

**************************************************************************/

class PostBox_MySQL {
    
    public function __construct() {
        $this->structure = '';
    }

    public function createHeader() {
        $this->addComment("PostBox MySQL Generator");
        $this->structure .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\"; \n";
        $this->structure .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */; \n";
        $this->structure .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */; \n";
        $this->structure .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */; \n";
        $this->structure .= "/*!40101 SET NAMES utf8 */; \n";
    }
    
    /**
    * Create table
    *
    * @param string $table
    * @param array $fields where expected to be in form 'id' => 'varchar(255) NOT NULL'
    * @param array $keys where expected to be in form 'primary' => 'id'
    * @param string $engine
    */
    public function createTable($table, $fields = array(), $keys = array(), $engine='MyISAM') {
        $this->addComment("Table structure for table `{$table}`");
        $this->structure .= "DROP TABLE IF EXISTS `{$table}`; \n";
        $this->structure .= "CREATE TABLE IF NOT EXISTS `{$table}` (\n";
        if($fields) {
            $field_count = 1;
            foreach($fields as $key => $value) {
                $this->structure .= "`{$key}` {$value}";
                if($field_count !== count($fields)) $this->structure .= ",\n";
                $field_count++;
            }   
        }
        ($keys) ? $this->structure .= ",\nPRIMARY KEY (`{$keys['primary']}`)" : "";
        $this->structure .= "\n) ENGINE={$engine} DEFAULT CHARSET=utf8; \n";
    }
    
    /**
    * Insert data into existing table
    *
    * @param string $table
    * @param array $values where expected to be in form 'id' => 121, 'distribution' => 'North America'
    */
    public function insertData($table, $values = array()) {
        $this->structure .= "INSERT INTO `{$table}` ";
        $this->structure .= "(`" . implode('`,`', array_keys($values)) . "`) ";
        $this->structure .= "VALUES";
        $this->structure .= " ('" . implode('\',\'', $values) . "'); \n";
    }
    
    /**
    * Get the full MySQL string
    */
    public function getStructure() {
        return $this->structure;
    }
    
    /**
    * Add a comment
    *
    * @param string $comment
    */
    private function addComment($comment) {
        $this->structure .= "\n\n";
        $this->structure .= "-- ---------------------------------\n";
        $this->structure .= "-- {$comment}\n";
        $this->structure .= "-- ---------------------------------\n";
    }

}

?>