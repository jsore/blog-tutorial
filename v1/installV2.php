<?php
require_once 'lib/common.php';
//
//
// front end script for initiating DB data
//
//
// install.php WILL ALWAYS HOLD THE MOST CURRENT, UP TO DATE VERSION OF our install.php form
// install.php will replace any other version number, with V1 being the oldest rendition and
// larger numbers becoming more recent renditions
//
//
// initiates blog setup (or wipe and refresh the blog)
// let's us easily recreate test data upon a DB change
//
//
// NOTE - THIS FILE WILL NEED PERMISSIONS TO EXECUTE READ AND WRITE 
// give permission to /blog/data dir from /var/www/blog dir:
//      # chmod a+rwx data
// give permission to install.php
//      # chmod a+rwx install.php
// https://www.washington.edu/computing/unix/permissions.html
//
//
// REMEMBER - 
// if this file is changed, the data/data.sqlite file needs to be deleted and 
// then re-created by visiting /blog/install.php again
//
//
// revisions to be made which replace installV1.php:
//  - make it easier to see the data we're creating when this form is accessed
//  - V1 uses non-optimal techniques, visiting the URL changes the DB without 
//      taking into account URLs can receive visits from automated software. This 
//      version will use a POST method to ensure a human is requesting the install form.


// defines where to spit out the DB data into the specified DB file
    // $root = realpath(__DIR__);
    // $database = $root . '/data/data.sqlite';
    // $dsn = 'sqlite:' . $database;



function installBlog()
{
    // get the PDO DSN string data and create somewhere for error our messages to go
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
        $pdo = getPDO();                // common.php function, build a new PDO object
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


// store all this data into a session to "survive the redirect to self" (?)
session_start();

// force the installer to only run when form is responded to
// this will always be a POST request, not GET, since we're rebuilding the DB when this is ran
if ($_POST)
{
    // run the actual installer, listing out data for this session
    list($_SESSION['count'], $_SESSION['error']) = installBlog();
    
    // now redirect the POST request to a GET request and close the if() statement
        // $host = $_SERVER['HTTP_HOST'];
        // $script = $_SERVER['REQUEST_URI'];
        // header('Location: http://' . $host . $script);
        // exit();
    // moved this into a common.php function for re-use in other scripts:
    redirectAndExit('install.php');
}


// were we able to install?
$attempted = false;
if ($_SESSION)
{
    $attempted = true;              // yup, install completed...
    $count = $_SESSION['count'];    // ...now grab the items we've installed in this session...
    $error = $_SESSION['error'];    // ...or, report our errors...
    unset($_SESSION['count']);      // ...then make sure we only report the install and error once
    unset($_SESSION['error']);
}
?>

<!-- push the results of the above commands to the page -->
<!DOCTYPE html>
<html>
    <head>
        <title>Blog installer</title>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        
        <style type="text/css">
            .box {
                border: 1px dotted silver;
                border-radius: 5px;
                padding: 4px;
            }
            .error {
                background-color: #ff6666;
            }
            .success {
                background-color: #88ff88;
            }
        </style>
    </head>
    <body>
        <!-- render our items if install completed successfully... -->
        <?php if ($attempted): ?>
            
            <!-- ...print any error into a div... -->
            <?php if ($error): ?>
                <div class="error box">
                    <?php echo $error ?>
                </div>
                
            <!-- ...or print our count of items for each specified table into a div... -->    
            <?php else: ?>
                <div class="success box">
                    The database and demo data were created OK. 
                    
                    <!-- dig into each of our tables that have been converted to arrays -->
                    <?php foreach (array('post', 'comment') as $tableName): ?>
                        <?php if (isset($count[$tableName])): ?>
                            <!-- print the counts of the things and the name of the things being counted in each table array -->
                            <?php echo $count[$tableName] ?> new <?php echo $tableName ?>s were created.
                        <?php endif ?>
                    <?php endforeach ?>
                </div>
                
                <!-- display some links for easy redirection to the blog or our install.php page -->
                <p>
                    <a href="index.php">View the blog</a>, or <a href="install.php">install the DB again</a>.
                </p>
            <?php endif ?>
            
        <!-- ...or display a way to create the DB -->
        <?php else: ?>
            <p>Click the install button to create or reset the database.</p>
            <form method="post">
                <input
                    name="install"
                    type="submit"
                    value="Install DB"
                />
            </form>
        <?php endif ?>
    </body>
</html>