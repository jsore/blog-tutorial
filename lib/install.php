<?php
/**
 * lib/install.php
 * Backend script for initiating or recreating DB
 * 
 * @return array(count array, error string)
 */
function installBlog(PDO $pdo)
{
    // grab some paths and create somewhere for error our messages to go
    $root = getRootPath();          // common.php function, realpath called on __DIR__ magic constant
    $database = getDatabasePath();  // common.php function, appends a path to getRootPath();
    $error = '';
    
    
    // keep the DB from being reset if it already exists, force the site admin to manually
    // delete the DB before install.php can run again
    if (is_readable($database) && filesize($database) > 0)
    {
        $error = 'Please delete the existing DB file manually before attempting to run this form again';
    }
    
    
    // build an empty file for the DB to reside in if no error has been thrown yet
    if (!$error)
    {
        $createdOk = @touch($database);
        
        // if file/directory permissions for apache user not set right, through an
        // error hinting at possible permission issues (most likely cause of the failure)
        if (!$createdOk)
        {
            $error = sprintf(
                'Could not create the database, please allow the server to create new files in \'%s\'', dirname($database)
            );
        }
    }
    
    
    // grab the SQL commands we want to run against the DB from our data/init.sql 
    // file if no error has been thrown yet
    if (!$error)
    {
        $sql = file_get_contents($root . '/data/init.sql');
        
        // throw error if SQL DB file isn't found or accessible
        if ($sql === false)
        {
            $error = 'Cannot find SQL file';
        }
    }
    
    
    // connect to the newly created DB and try to run the SQL commands from 
    // data/init.sql if no error has been thrown yet
    if (!$error)
    {
        // common.php function, build a new PDO object
            // $pdo = getPDO();
        // ^^ was replaced by passing the $pdo as an arg to installBlog()
        $result = $pdo->exec($sql);     // go ahead and try to initiate our SQL commands
        if ($result === false)
        {
            $error = 'Could not run SQL: ' . print_r($pdo->errorInfo(), true);
        }
    }
    
    
    // if rows were created, get how many there are for each specified table
    $count = array();
    foreach(array('post', 'comment') as $tableName)
    {
        if (!$error)
        {
            $sql = "SELECT COUNT(*) AS c FROM " . $tableName;   // build our SQL query...
            $stmt = $pdo->query($sql);                          // ...then exec the query...
            if ($stmt)                                          // ...check if we got anything from our query...
            {                                                   //
                $count[$tableName] = $stmt->fetchColumn();      // ...and push the number of each count into the $count array...
            }                                                   //
        }                                                       //
    }                                                           //
    return array($count, $error);                               // ...then return arrays to caller of installBlog() function
}


/**
 * logic behind updating OR creating a new user in the DB
 *
 * @param PDO $pdo
 * @param string $username
 * @param integer $length
 * @return array Duple of (password, error)     // Duple??
 */
function createUser(PDO $pdo, $username, $length = 10){
    
    // algorithm to create a new randomized password, to replace plaintext default PW already in the DB
    $alphabet = range(ord('A'), ord('z'));
    $alphabetLength = count($alphabet);
    $password = '';
    for($i = 0; $i < $length; $i++){
        $letterCode = $alphabet[rand(0, $alphabetLength -1)];
        $password .= chr($letterCode);
    }
    
    
    // create a place for errors in this function
    $error = '';
    
    // build a SQL statement to stick credentials into our DB...
    // NOTE: this statement CREATES a new user...
        // $sql = "INSERT INTO user (username, password, created_at)
        //         VALUES (:username, :password, :created_at)";
    // NOTE: and this one UPDATES an existing user in the DB
    $sql = "UPDATE user 
            SET password = :password, 
                created_at = :created_at,
                is_enabled = 1
            WHERE username = :username";
    
    
    // ...start prepping and test the SQL statement...
    // ...if test passes, execute the SQL command on the DB
    $stmt = $pdo->prepare($sql);
    if ($stmt === false){
        $error = 'Could not PREPARE the user UPDATE request';
    }
    
    /*************************************************************************************
    **** this stores the password in plaintext and is replaced in following codeblock **** 
    **************************************************************************************
        if (!$error){
            $result = $stmt->execute(array(
                'username' => $username,
                'password' => $password,
                'created_at' => getSqlDateForNew(),
            ));
            if ($result === false){
                $error = 'Could not EXECUTE the user creation request';
            }
        }
        if ($error){
            $password = '';
        }
        return array($password, $error);
    *************************************************************************************/
    /*************************************************************************************
    **** As of PHP 5.5, password_compat library functionality is part of vanilla PHP. ****
    **** If using an earlier version than 5.5 you need to add the library directory & ****
    **** password.php file then hook into it with the commented out require_once stmt ****
    **** For reference, I added this library and file to the path specified in /blog/ ****
    **** The require_once statement needs to be at the top of this install.php file.  ****
    **** require_once getRootPath() . '/vendor/password_compat/lib/password.php'      ****
    *************************************************************************************/
    // create a hash of the new random password
    if (!$error){
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false){
            $error = 'Failed to hash the password';
        }
    }
    // load our user details and hashed PW into the DB
    if (!$error){
        $result = $stmt->execute(array(
            'username' => $username,
            'password' => $hash,
            'created_at' => getSqlDateForNow(),
        ));
        if ($result === false){
            $error = 'Could not EXECUTE the user password UPDATE request';
        }
    }
    if ($error){  $password = '';  }
    return array($password, $error);
    /************************************************************************************/
}
?>