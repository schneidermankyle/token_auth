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


class tokenAuth 
{
    // Configuration variables, can either be set inside app or set with array
    // using the importConfig($array) command;
    public $length = 128;
    public $authTimeout = '5m';
    public $hash = FALSE;
    public $hashType = 'sha512';
    public $tokenFlags = 'A';
    public $authType = 'database';
    private $currentToken = '';
	
	function __construct($token = null)
	{
        $this->currentToken = ($token) ? $token : '';
	}

    // Private member Functions //
    private function handleCookie($token) {

    }

    private function setToSession($token) {

    }

    private function setToDb($token) {

    }

    private function verifySetting($setting, $value) {
        // This function will ensure that the settings being imported with 
        // loadConfig jive with what the system expects.
        // !!!!!!!!! FIX THIS !!!!!!! //
        // For now this is just going to loop back.
        return $value;
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


    // PUBLICS //
    public function initDb($db) {
        // For setting up db first time
    }

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

    public function createRequest() {
        // In here we need to decipher what information should be held within the request
        // idealy this should be completely dev defined to define with data sorting conventions

    }

    public function setRequest($medium = null) {
        // Look to the config and determine what kind of request this should be.
        // then we will have to set the request to the auth type

        switch ($this->authType) {
            case 'database':
                break;
            case 'cooke':
                break;
            case 'session':
                break;
            default:
                break;
        }
    }

    public function debug() {
        $test = 'authType';
        echo('debuging <br/>');
        var_dump($this->$test);
    }

}


?>