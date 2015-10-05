<?php
/*
    Copyright (c) 2015 Kyle Schneiderman, http://kyleschneiderman.com/

    Permission is hereby granted, free of charge, to any person obtaining
    a copy of this software and associated documentation files (the
    "Software"), to deal in the Software without restriction, including
    without limitation the rights to use, copy, modify, merge, publish,
    distribute, sublicense, and/or sell copies of the Software, and to
    permit persons to whom the Software is furnished to do so, subject to
    the following conditions:

    The above copyright notice and this permission notice shall be
    included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
    EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
    MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
    NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
    LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
    OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
    WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class Connection {
    public $conn;

    public function __construct($data) {  
    // need to pass an array into this class that includes: host, dbname, username and password.
        date_default_timezone_set('America/Los_Angeles');

        try {
            $connection = new PDO("mysql:host=" . $data['host'] . ";dbname=" . $data['dbname'], $data['username'], $data['password']);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->conn = $connection;
        } catch (Exception $e) {
            $this->conn = FALSE;
        }
        
        return $this->conn;
    }
    
    public function getTable($tableName) {
        $tableName = $this->sanitize($tableName);

        $query = $this->conn->prepare("SELECT * FROM " . $tableName);
        $query->execute();

        if ($query->rowCount() > 0) {
            return ($query->fetchAll(PDO::FETCH_ASSOC));
        } else {
            return FALSE;
        }
    }

    public function getRow($tableName, $keyName) { 
        $tableName = $this->sanitize($tableName);
        $query = $this->conn->prepare("SHOW KEYS FROM " . $tableName . " WHERE Key_name = 'PRIMARY'");
        $query->execute();

        if ($query->rowCount() > 0) {
            $primaryKey = $query->fetch()['Column_name'];

            $query = $this->conn->prepare("SELECT * FROM `" . $tableName . "` WHERE `" . $this->sanitize($primaryKey) . "` = :key");
            $query->bindValue(':key',  $this->sanitize($keyName));
            $query->execute();

            if ($query->rowCount() > 0) {
                return ($query->fetch(PDO::FETCH_ASSOC));
            } else {
                return FALSE;
            }
        }
    }

    public function getKey($query, $key, $value) {
        $query = $this->conn->prepare($query);
        $query->bindValue($key, $value);
        $query->execute();

        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return FALSE;
        }
    }
    

    // Private parts //
    private function sanitize($input){
        $output;

        if (is_array($input)) {
            foreach($input as $key=>$value) {
                $output[$key] = $this->sanitize($value);
            }
        } else {
            if (get_magic_quotes_gpc()) {
                $input = stripslashes($input);
            }
            $input = $this->cleanString($input);
            $output = $this->escapeString($input);
        }

        return $output;
    }

    private function cleanString($input){
        $search = array(
            '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@',        // Strip multi-line comments
            '/((\.)+(\/)+)+/',                  // For directory matching
            '/(\/)+/'                           // Get rid of slashes
        );

        $output = preg_replace($search, '', $input);
        return $output;
    }

    private function escapeString($input) { 
        if(is_array($input)) 
            return array_map(__METHOD__, $input); 

        if(!empty($input) && is_string($input)) { 
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $input); 
        } 

        return $input; 
    }

}