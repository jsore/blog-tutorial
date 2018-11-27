<?php
//
//
// install.php WILL ALWAYS HOLD THE MOST CURRENT, UP TO DATE VERSION OF our install.php form
// install.php will replace any other version number, with V1 being the oldest rendition and
// larger numbers becoming more recent renditions
//
//
// initiate blog setup (or wipe and refresh the blog)
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


// defines where to spit out the DB data into the specified DB file
    // $root = realpath(__DIR__);
    // $database = $root . '/data/data.sqlite';
    // $dsn = 'sqlite:' . $database;
require_once 'lib/common.php';
// get PDO DSN string
$root = getRootPath();  // accesses common.php function
$database = getDatabasePath();  // accesses common.php function

$error = '';

// keep anyone from resetting the DB file data.sqlite if it already exists
// only manual DB file removal from the host is permitted
if (is_readable($database) && filesize($database) > 0)
{
    $error = 'Please delete the existing database manually before submitting a DB refresh';
}

// create empty data.sqlite DB file using touch() 
// fire an error if file can't be created (check system privs)
if (!$error)
{
    $createdOk = @touch($database);
    
    if (!$createdOk)
    {
        $error = sprintf(
            'Could not create the DB, please allow the server to create new files in \'%s\'', 
            dirname($database)
        );
    }
}

// grab SQL commands we want to run against the DB from sql file in blog/data
// fire an error if sql command file isn't found
if (!$error)
{
    $sql = file_get_contents($root . '/data/init.sql');
    
    if ($sql === false)
    {
        $error = 'Cannot find SQL file';
    }
}

// connect to the new DB and try to run the SQL commands
// fire an error if exec class can't be run on the PDO object with our sql command file 
if (!$error)
{
    // $pdo = new PDO($dsn);
    $pdo = getPDO();    // accesses common.php function
    $result = $pdo->exec($sql);
    
    if ($result === false)
    {
        $error = 'Could not run SQL: ' . print_r($pdo->errorInfo(), true);
    }
}

// if rows were created, review how many there are
// updated below to include the counting of comments in a post as well as posts created
//
    // $count = null;
    // if (!$error)
    // {
    //     $sql = "SELECT COUNT(*) AS c FROM post";
    //     $stmt = $pdo->query($sql);
    //    
    //     if ($stmt)
    //     {
    //         $count = $stmt->fetchColumn();
    //     }
    // }
$count = array();
foreach(array('post', 'comment') as $tableName)
{
    if (!$error)
    {
        $sql = "SELECT COUNT(*) AS c FROM " . $tableName;
        $stmt = $pdo->query($sql);
        if ($stmt)
        {
            // store each count in the associative array called before this foreach function
            $count[$tableName] = $stmt->fetchColumn();
        }
    }
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
        <?php if ($error): ?>
            <!-- div to hold the error message -->
            <div class="error box">
                <?php echo $error ?>
            </div>
        <?php else: ?>
            <!-- div to hold success message -->
            <div class="success box">
                The database and demo data were created OK.
                
                <!-- this foreach replaces    php: if $count: ?> echo $count ?> new rows created    -->
                <!-- loops through each DB type arrays and print "$count[$tableName] new $tableNames were created."  -->
                <?php foreach (array('post', 'comment') as $tableName): ?>
                    <?php if (isset($count[$tableName])): ?>
                        <!-- prints the count -->
                        <?php echo $count[$tableName] ?> new
                        <!-- prints the name of the counted thing -->
                        <?php echo $tableName ?>s were created.
                    <?php endif ?>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </body>
</html>