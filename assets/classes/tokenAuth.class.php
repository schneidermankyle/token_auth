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
    WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS I NTHE SOFTWARE.
*/


class tokenAuth 
{
    // Configuration variables, can either be set inside app or set with array
    // using the importConfig($array) command;
    private $logging = TRUE;
    private $mode = 'development';
    private $logFile = __DIR__;
    private $length = 128;
    private $authTimeout = '5m';
    private $hash = FALSE;
    private $hashType = 'sha512';
    private $tokenFlags = 'A';
    private $authType = 'database';
    private $tableName = 'gs_requests';
    private $action = 'request';
    private $db;
    private $currentToken = '';
    private $errors = array(
        100 => 'Error, an invalid token was made available for authentication',
        101 => 'Error, request not found',
        102 => 'Error, you are not authorized to do that action',
        200 => 'There was an error creating the db, if this problem continues to exist, please create manually.',
        201 => 'There was an error writing request to database.',
        202 => 'Error, could not find database to connect to',
        203 => 'There was an error parsing request',
        204 => 'There was an error removing the request',
        205 => 'Auth Type not defined'
    );
	
    function __construct($token = null)
    {
        if ($this->logging === TRUE) {
            $this->prepareLogs();
        }

        $this->currentToken = ($token) ? $token : $this->createToken();
    }

    ////////////////////////////////
    // Logging and error handling //
    ////////////////////////////////

    private function prepareLogs() {
        // First we need to determine if we should log
        if ($this->logging) {
            // Next we need to know if the directory already exists
            if (!is_dir($this->logFile.'/logs')) {
                // We must create directory
                if(!mkdir($this->logFile.'/logs') ) {
                    return $this->processError($this->error[202]);
                };
            }

            if (!is_writeable($this->logFile.'/logs/token.log')) {
                // We must create a file
                $errorLog = fopen($this->logFile.'/logs/token.log', 'w');
                if (!$errorLog) {
                    return $this->processError($this->error[202]);
                }
                fwrite($errorLog, 'Log created on '.date('l jS \of F Y h:i:s A').PHP_EOL);
                fclose($errorLog);
            }
        }

        return TRUE;
    }

    private function processError($error = NULL, $dump = NULL) {
        // This function's sole purpose to figure out how to handle errors.
        // In the future,  I would like to expand on this and perhaps include more options like json
        $error = $this->errors[$error];
        if (isset($error)) {
            // Echo or dump error directly to screen
            if (!$this->mode === 'development') {
                // Then we need to senser ourselves.
                return FALSE;
            }
            // Otherwise continue as usual and output error
            echo ($error);
            if (isset($dump)) {
                var_dump($dump);
            }
        }

        // If logs are to be created, let's handle it now
        if (isset($error) && ($this->logging) ) {
            // Are our logs available?
            if (is_readable($this->logFile.'/logs/token.log')) {
                // Create the error string
                $errorString = 'ERROR: The following was encountered on ' . date('l jS \of F Y h:i:s A'). ' Output: ' . $error . PHP_EOL . ' Trace: ';
                $i = 0;

                foreach (debug_backtrace() as $trace) {
                    $errorString .= ' (error #' . $i . ') file: ' .  $trace['file'] . ' line: ' . $trace['line'] . ' calling function: ' . $trace['function'];
                    $i++;
                }
                $errorString .= ' ;' . PHP_EOL;
                if ($dump) {
                  $errorString .= ' - External Dump: ' . (string)$dump . PHP_EOL;  
                }
                
                $errorLog = fopen($this->logFile.'/logs/token.log', 'a+');
                if (!$errorLog) {
                    // If the file for some reason failed to open, go ahead and process that error as well.
                    return $this->processError($this->error[202]);
                }
                fwrite($errorLog, $errorString);
                fclose($errorLog);
            }
        } 
        
        return FALSE;
    }

    //////////////////////////////
    ///////// Databasing /////////
    //////////////////////////////
    
    // Try to actually create the db //
    private function createDb($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `".$this->tableName."` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `type` VARCHAR(45) NOT NULL,
                `title` VARCHAR(128) NOT NULL,
                `token` VARCHAR(1024) NOT NULL,
                `date` INT(64) NOT NULL,
                `expiration` int(64) NOT NULL,
                `status` INT(3) NOT NULL,
                PRIMARY KEY (`id`));"
            );

            return TRUE;
        } catch (Exception $e) {
            return $this->processError(200, $e);
        }
    }

    //////////////////////////////
    ////// Request handling //////
    //////////////////////////////

    // Need to make cookies and sessions able to handle multiple requests
    // Write request to cookie
    private function writeToCookie($token = null) {
        if ($token) {
            // This will rely on the page determining if cookies are enabled.
            // For now we will just send an error identifieng that cookies are not enabled when request fails
            setcookie('authentication', $this->currentToken, time()+$this->convertToSeconds($this->authTimeout));
        }
    }

    // FIX THIS! //
    // Need to move along for now, come back to this and work on it... // 
    private function writeToSession($token) {
        if ($token) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                // we need to add the ability to test whether sessions are able to be started.
                session_start();
            } else {
                // Set the reequest into the active session
                $_SESSION[] = array(
                    'type' => 'authentication',
                    'token' => $token,
                    'date' => time(),
                    'expiration' => time()+$this->convertToSeconds($this->authTimeout),
                    'status' => 1
                );
            }
        } 
    }

    // Since the current option says to use database authentication, we must write to db
    private function writeToDb($token = null) {
        // Make sure that the connection is set true.
        if ($this->db && $token) {
            // Would like to make this support various classes, but for now this will do.
            $query = $this->db->prepare('INSERT INTO `'.$this->tableName.'` (
                type, title, token, date, expiration, status) Values(:type, :title, :token, :date, :expiration, :status);');
            $query->execute(array('type' => $this->action, 'title' => 'Request', 'token' => $token, 'date' => time(), 'expiration' => (time() + $this->convertToSeconds($this->authTimeout)), 'status' => 1));
            
            if ($query->rowCount() > 0) {
                return TRUE;
            } return $this->processError(201);

        } return $this->processError(100);
    }

    // Quickly update the status of 
    private function removeFromDb($token) {
        if ($this->db && $token) {
            $query = $this->db->prepare('UPDATE `'.$this->tableName.'` SET status = 0 WHERE `token` = :token');
            $query->bindValue('token', $token);
            $query->execute();

            if ($query->rowCount() === 1) {
                return TRUE;
            }   return $this->processError(204);
        } $this->processError(204);
    }

    // Remove cookie authentication
    private function removeCookie() {
        if (isset($token) && isset($_COOKIE['authentication'])) {
            setcookie('authentication', '', time()-3600);
            unset($_COOKIE['authentication']);
            return TRUE;
        } return $this->processError(204);
    }

    private function removeFromSession() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        } else {
            if ($_SESSION['type']) {
                unset($_SESSION['type']);
                unset($_SESSION['token']);
                unset($_SESSION['date']);
                unset($_SESSION['expiration']);
                unset($_SESSION['status']);
                return TRUE;
            } return $this->processError(204);
        }
    }


    private function completeRequest($token, $authType) {
        if (isset($token) && isset($authType)) {
            switch ($authType) {
                case 'database':
                    return $this->removeFromDb($token);
                    break;
                case 'cookie':
                    return $this->removeCookie();
                    break;
                case 'session':
                    return $this->removeFromSession();
                    break;
                default:
                    return $this->processError(205);
                    break;
            }
        }
    }

    // Grab our passed in token from the database
    private function getFromDb($token) {
        // If there is an active connection and a valid token
        if ($this->db && $token) {
            $query = $this->db->prepare('SELECT * FROM `'.$this->tableName.'` WHERE `token` = :token');
            $query->bindValue('token', $token);
            $query->execute();
            if ($query->rowCount() === 1) {
                // We found a token, let's go ahead and return the request for processing
                return $query->fetch(PDO::FETCH_ASSOC);
                // Uh oh, no token found, return an error
            } return $this->processError(101);
        }
    }

    private function getFromCookie($token) {
        if ($this->$token) {
            echo ('setting found');
        }
    }

    // This will solely be called from validateRequest
    // This function is simply to sort through a given array and determine if the request is valid
    private function processRequest($request, $token, $authType) {
        // Then it must sanitize passed in data
        // It must then verify to make sure that the token matches in every way
        if (isset($request) && is_array($request)) {
            // Let's ensure that these match and that the request has not expired
            if ($token === $this->sanitizeToken($request['token']) && time() < intval($request['expiration']) && intval($request['status']) === 1) {
                // Lastly, does the stored action match the requested action?
                if ($request['type'] === $this->action) {
                    // Update status and return true.
                    $this->completeRequest($token, $authType);
                    return TRUE;
                    // If anything went wront, process the errors
                } return $this->processError(102);
            } return $this->processError(100);
        } return $this->processError(203);
    }

    private function verifySetting($setting, $value) {
        // This function will ensure that the settings being imported with 
        // loadConfig jive with what the system expects.
        // !!!!!!!!! FIX THIS !!!!!!! //
        // For now this is just going to loop back.
        return $value;
    }

    ///////////////////////////
    //////// Utilities ////////
    ///////////////////////////

    // Converts a string such as 5m into seconds.
    private function convertToSeconds($time) {
        $int = intval($time);
        $multiplyer = substr($time, -1);

        switch ($multiplyer) {
            case 'd':
                $multiplyer = 86400;
                break;
            case 'h':
                $multiplyer = 3600;
                break;
            case 'm':
                $multiplyer = 60;
                break;
            case 's':
                $multiplyer = 1;
                break;
            default:
                break;
        }

        return ($int * $multiplyer);
    }

    private function randomNumber($min, $max) {
        // Set our range
        $difference = $max - $min;
        // If range is not negative
        if ($difference > 0 ) {
            $bytes = (int) (log($difference, 2) / 8 ) + 1;
            $bits = (int) (log($difference, 2)) + 1;
            $filter = (int) (1 << $bits) - 1;
            do {
                // Generate Random number
                $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes, $s)));
                $rnd = $rnd & $filter;
            } while ($rnd >= $difference);
            // Return our random number
            return $min + $rnd;
        } else {
            // Otherwise, return just the minimum number
            return $min;
        }
    }

    private function sanitizeToken($token) {
        // need to verify that the token is the length defined in config
        // Also should make sure token is good to write to db.
        if (isset($token) && $token && (strlen($token) === $this->length) && preg_match("([A-Za-z\d\!\@\#\$\%\^\&\*\(\)]+)", $token, $outputArray)) {
            return $outputArray[0];
        }

        return FALSE;
    }

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

    private function returnString() {
        $string = '';
        $flags = str_split($this->tokenFlags);

        // Sort through our flags and figure out what the dev wants to include for strings
        foreach ($flags as $flag) {
            switch ($flag) {
                case 'W':
                    $string .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'w':
                    $string .= 'abcdefghijklmnopqrstuvwxyz';
                    break;
                case 'd':
                    $string .= '0123456789';
                    break;
                case 'S':
                    $string .= '!@#$%^&*()';
                    break;
                case 's':
                    $string .= ' ';
                    break;
                case 'A':
                    $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
                    break;
                default:
                    break;                
            } 
        }

        return $string;
    }


    /////////////////////////////////////
    ////////////// PUBLICS //////////////
    /////////////////////////////////////

    public function initDb($db) {
        // For setting up db first time
        // Querry to see if table exists, if not set one up.
        try {
            $query = $db->prepare('SELECT `id` FROM `'.$this->tableName.'`');
            $query->execute();

            $this->db = $db;
            return TRUE;
        } catch (Exception $e) {
            if ( $e->getCode() === '42S02') {
                return $this->createDb($db);
            }
        }
    }

    // creates token per configuration and stores token to member variable
    // Will accept override to prevent storing token to object.
    // Instead this is allow class to work as simple token generator.
    public function createToken($override = FALSE) {
        $token = '';
        // Set our current list of acceptable characters for the token
        $string = $this->returnString();
        for ($i = 0; $i < $this->length; $i++) {
            $token .= $string[$this->randomNumber(0, strlen($string))];
        }

        // If overrride has not been set, then set token.
        // Then return the token in case dev wants to do post processing on it.
        if (!$override) {
            $this->currentToken = $token;
        }
        return $token;
    }

    // Quick getter for current token //
    public function getToken() {
        return $this->currentToken;
    }

    // In case defaults are not preferred, this allows dev to import configs in one array
    public function loadConfig($config = null) {
        // Future idea, perhaps make this an external file feature ?
        if (!$config) {
            return FALSE;
        } else if (is_array($config)) {
            foreach ($config as $key => $value) {
                if (isset($this->$key)) {
                    // Ensure that the setting is compatable with verifySetting
                    $this->$key = $this->verifySetting($key, $value);
                }
            }
        }

        return FALSE;
    }

    // Santize the option, even though we will re-sanitize strings before uploading
    public function setOption($member, $key = FALSE) {
        if ($member && $this->$member) {
            // Is this section really needed? //
            if ($key) {
                $this->$member = $this->verifySetting($member, $this->sanitize($key));
                return TRUE;
            } 
            // Either the actual value of false or null was passed in, or some sort of issue
            $this->$member = $this->verifySetting($this->$member,FALSE);
            return FALSE;

        } return FALSE;
    }


    // Create an authentication request per authType member variable
    // depending on what kind of authentication is requested, class will handle setting the request
    public function createRequest($authType = null, $token = null) {
        // Look to the config and determine what kind of request this should be.
        // then we will have to set the request to the auth type
        $authType = ($authType) ? $authType : $this->authType;
        $token = ($token) ? $this->sanitizeToken($token) : $this->currentToken;

        switch ($authType) {
            case 'database':
                if ($this->db) {
                    // Than let's go ahead and store to the database.
                    return $this->writeToDb($token);
                }
                return $this->processError(202);
                break;
            case 'cookie':
                $this->writeToCookie($token);
                break;
            case 'session':
                $this->writeToSession($token);
                break;
            default:
                return $this->processError(205);
                break;
        }
    }



    // Given the current authType and token, validate the request.
    public function validateRequest($authType = null, $token = null) {
        // validateRequest must do a few things here
        // First it must grab the token from the authType
        // Then pass it off to be processed.
        $authType = ($authType) ? $authType : $this->authType;
        $token = ($token) ? $this->sanitizeToken($token) : $this->currentToken;

        switch ($authType) {
            case 'database':
                if ($this->db) {
                    // Than let's go ahead and store to the database.
                    return $this->processRequest($this->getFromDb($token), $token, 'database');
                    
                }
                return $this->processError(202);
                break;
            case 'cookie':
                $this->writeToCookie($token);
                break;
            case 'session':
                $this->writeToSession($token);
                break;
            default:
                return $this->processError(205);
                break;
        }
    }

    /////////////////////////////////////
    /////////////// Other ///////////////
    /////////////////////////////////////

    public function debug() {
        $test = 'authType';
        echo('debuging <br/>');
        var_dump($this->$test);
    }

}


?>