#About Token Auth

Token Auth is a quick project aimed to help authenticate various requests, particularly requests sent via AJAX. 
Currently token auth supports three authentication methods
-	Database
-	Cookies
-	Session

It is recommended to use database for most requests, but I wanted to include some flexibility for those requests where it didn’t make sense to include a database connection. 

##Features
-	Quick installation
-	Lightweight
-	Flexible
-	Standalone
-	Verbose Logging

##How to install class:
Simply include the class in your project with:

#####Example
```
Require ‘./classes/tokenAuth.class.php’;
$token = new tokenAuth(‘optional token’);
```

If no token is supplied, the class will automatically create a new token with the default configuration. If you would like to update the token after adjusting the configuration, it’s as easy as calling 

```
$token->createToken();
```

##Configuration:
Options can be set a couple of different ways depending on the level of needs required by the developer. The first way is easiest if you are only overwriting a one or a few of the options:

#####Example:
```
$token->setOption(‘option’,’value’);
```

The second way makes passing in several or all options a little easier:
 
#####Example:
```
$configArray = [
	‘option’ => ‘value’,
	‘option2’ => ‘value2’;
];

$token->loadConfig($configArray);
```

##Options:

###logging: Depending on your needs, you may want to log the output of both configuration errors along with when invalid tokens are attempted to be authenticated.

####Options:
-	True (Default)
-	False

#####Example:
```
$token->setOption(‘logging’,TRUE);
```

### mode: The current set mode determines whether as well as in the future which level of errors will be rendered to the screen. Currently development mode will render all errors and production will render none. Future plans are to support multiple levels based on the severity of the error.

####Options:
-	‘production’ (default)
-	‘development’
-	
#####Example:
```
$token->setOption(‘mode’,’development);
```

###logFile: Depending on various factors you may want to move the directory of where errors are logged to. Currently default is the directory where the class is stored on the server as denoted by __DIR__.

####Options:
-	Any writeable path (default is __DIR__)
-	
#####Example:
```
$token->setOption(‘logFile’,’../somedirectory’);
```

###length: You may want to change the token length, this is done simply altering the length property. You can change this property to theoretically any number supported by your authentication method. ######Note – If you are using database authentication and you use the automatic database initialization of this class, then tokens can be up to 1024 characters in length.

####Options:
-	Any integer supported by your chosen authentication method (default is 128)
-	
#####Example:
```
$token->setOption(‘length’,256);
```

###authTimeout: This option refers to how long until the authentication method will time out. Definition for this option is defined as ‘(integer)(measurement)’ So fifteen minutes would be passed in as ‘5m’, or perhaps one week as ‘1w’. ######Default for this option is ‘15m’

####Options:
-	‘s’ : seconds
-	‘m’ : minutes (default is ‘15m’)
-	‘h’ : hours
-	‘d’ : days
-	‘w’ : weeks
-	‘M’ : months
-	‘y’ : years (just in case)

#####Example:
```
$token->setOption(‘authTimeout’,’20h’);
```

###tokenFlags: tokenFlags refers to the characters you wish to include in your token. Default is all the letters of the English alphabet: [a-zA-Z], Digits: [0-9] and special characters [!@#$%^&*()]. Flags are passed in as a compiled string such as: ‘Wds’ which would include [A-Z0-9\s]. ######Note - \s denotes whitespace.

####Options:
-	‘A’ : All excluding whitespace
-	‘w’ : a-z
-	‘W’ : A-Z
-	‘d’ : 0-9
-	‘S’ : !@#$%^&*()
-	‘s’ : whitespace

#####Example:
```
$token->setOption(‘tokenFlags’,’wWds’);
```

###authType: This option refers to the method of authentication you would like to use. Right now only three are supported, with the recommended being database. ######Note – In order for database authentication to work, a PDO connection must be supplied into the initDb function, this will support MySQLi in the near future.

####Options:
-	‘database’ (default)
-	‘cookies’
-	‘session’

#####Example:
```
$token->setOption(‘authType’,’cookies’);
```

###action: Action refers to the action being authorized. This can be any string, though I recommend choosing something simple like ‘update’ or ‘removeAlert’. ######Note - This value is verified when token validation is executed and by default, if using database authentication and the auto db installer supports strings up to 45 characters. If this option is not set it will default to the string ‘request’ and can be ignored if your only concern is making sure the token was passed successfully.

####Options:
-	Any string (default is ‘request’)

#####Example:
```
$token->setOption(‘action’,’updateUsers’);
```

###tableName (Optional): This option refers to the name of the table where you would like data stored when using database authentication. This can be any string that you would like or that works with your database naming conventions

####Options:
-	Any string that follows your table naming conventions (Default is ‘token_auth’)

#####Example:
```
$token->setOption(‘tableName’,’app_tokens’);
```

##Database and Initialization:
I wanted to keep this class rather compartmentalized from other possible systems running concurrently. As such I decided the best method was to leave database management largely out of this class. Unfortunately, given that a very key role of this class requires access to database functionality, I had to include some methods for handling it.  I didn’t want this class to be too reliant on any particular other class, thus I included an automatic self-installer, given proper privileges are supplied. In order to use the automatic installer, create a pdo connection somewhere within your application.

####Example:
```
$connection = new PDO("mysql:host=localhost;dbname=database, username,password);
// skipping connection verification.
$token = new Token();
$token->initDb($connection);
```

More information on php pdo can be found at [http://php.net/manual/en/pdo.construct.php]( http://php.net/manual/en/pdo.construct.php)

 In order to better secure your application, it is recommended that you manually set up your database and then provide a connection with only: <b>select, update and insert</b> privileges.
 
The database is set up to handle a token table with the following structure:

|-----------|--------------|------|-----|---------|-------|
| Field     | Type         | Null | Key | Default | Extra |
|-----------|--------------|------|-----|---------|-------|
| id        | INT(10)      | NO   | PRI | NULL    |AutoInc|
| type      | varchar(20)  | NO   |     | NULL    |       |
| token     | varchar(1024)| NO   |     | NULL    |       |
| date      | int(64)      | NO   |     | NULL    |       |
| expiration| int(64)      | NO   |     | NULL    |       |
| status    | int(3)       | NO   |     | NULL    |       |
|-----------|--------------|------|-----|---------|-------|

##Usage:
- Coming soon -
