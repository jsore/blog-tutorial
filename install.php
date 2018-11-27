<?php
/**
 * Front end script for initiating/recreating DB data.
 *
 * Files install.php and lib/install.php will always hold the most current up 
 *  to date versions of our instal scripts. 
 * Older versions will be in the blog/v1/ directory.
 *
 * NOTE FOR FUTURE REFERENCE - 
 * This file will need permissions to execute read and write
 *      give permission to /blog/data dir from /var/www/blog dir:
 *          # chmod a+rwx data
 *      give permission to install.php
 *          # chmod a+rwx install.php
 *      https://www.washington.edu/computing/unix/permissions.html
 *
 * Further, if any changes are made to this file, data/data.sqlite will 
 *  need to be deleted and the DB will need to be initiated again.
 *
 * revisions to be made which replace installV1.php:
 * - make it easier to see the data we're creating when this form is accessed
 * - V1 uses non-optimal techniques, visiting the URL changes the DB without 
 *      taking into account URLs can receive visits from automated software. This 
 *      version will use a POST method to ensure a human is requesting the install form.
 * - V2 the installBlog() function is to be moved to lib/install.php (for some reason?)
 */
require_once 'lib/common.php';
require_once 'lib/install.php';


// defines where to spit out the DB data into the specified DB file
    // $root = realpath(__DIR__);
    // $database = $root . '/data/data.sqlite';
    // $dsn = 'sqlite:' . $database;


// store all this data into a session to "survive the redirect to self" (?)
session_start();

// force the installer to only run when form is responded to
// this will always be a POST request, not GET, since we're rebuilding the DB when this is ran
if ($_POST)
{
    // run the actual installer, listing out data for this session
        // list($_SESSION['count'], $_SESSION['error']) = installBlog();
    // ^^ this is a hardcoded DB connection attempt
    // replaced with a method to pass a connection that can be customized instead:
    $pdo = getPDO();

        // list($_SESSION['count'], $_SESSION['error']) = installBlog($pdo);
    // ^^ this was the 2nd iteration of the list function, now replaced with the following to allow user creation/logins
    list($rowCounts, $error) = installBlog($pdo);
    $password = '';
    if (!$error){
        $username = 'admin';
        list($password, $error) = createUser($pdo, $username);  // hook into createUser() from backend install script
    }
    $_SESSION['count'] = $rowCounts;
    $_SESSION['error'] = $error;
    // with the user info below, the install script will allow for creation of admin user with a new 
    // password to be created every time DB is refreshed
    $_SESSION['username'] = $username;
    $_SESSION['password'] = $password;
    // now continue trying to run the installer
    $_SESSION['try-install'] = true;
    
    
    // now redirect the POST request to a GET request and close the if() statement
    // we want the page to reload without POSTing anything new, just GETting data we just stored
        // $host = $_SERVER['HTTP_HOST'];
        // $script = $_SERVER['REQUEST_URI'];
        // header('Location: http://' . $host . $script);
        // exit();
    // ^^ moved this into a common.php function for re-use in other scripts:
    redirectAndExit('install.php');
}


// were we able to install?
$attempted = false;
    // if ($_SESSION)
// ^^ isntead of checking for an open SESSION, check for try-install session var
if (isset($_SESSION['try-install'])){
    $attempted = true;              // yup, install completed...
    $count = $_SESSION['count'];    // ...now grab the items we've installed in this session...
    $error = $_SESSION['error'];    // ...or, report our errors
    $username = $_SESSION['username'];
    $password = $_SESSION['password'];
    
    // make sure we only report the install or install failure once per session
    unset($_SESSION['count']);
    unset($_SESSION['error']);
    unset($_SESSION['username']);
    unset($_SESSION['password']);
    unset($_SESSION['try-install']);
}

?>

<!-- push the results of the above commands to the page -->
<!DOCTYPE html>
<html>
    <head>
        <title>Blog installer</title>
        <?php require 'templates/head.php'; ?>
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
                    
                    <!-- dig into each of our tables that have been converted to arrays, display them -->
                    <?php foreach (array('post', 'comment') as $tableName): ?>
                        <?php if (isset($count[$tableName])): ?>
                            <!-- print the counts of the things and the name of the things being counted in each table array -->
                            <?php echo $count[$tableName] ?> new <?php echo $tableName ?>s were created.
                        <?php endif ?>
                    <?php endforeach ?>
                    
                    <!-- now report the password for our new user (which is hardcoded to 'admin in $username') -->
                    <p>The new password for user '<?php echo htmlEscape($username) ?>' is:</p>
                        <p><span class="install-password"><?php echo htmlEscape($password) ?></span></p>
                        <p>Please save this password for the duration of this session.</p>
                        <p>(yes, this is unsafe, this would never be acceptable IRL)</p>
                </div>
                
                <!-- display some links for easy redirection to the blog or our install.php page -->
                <p>
                    <a href="index.php">View the blog</a>, or <a href="install.php">install the DB again</a>.
                </p>
            <?php endif ?>
            
        <!-- ...or display a way to create the DB -->
        <?php else: ?>
            <p>Click the install button to create or reset the database.</p>
            <p>Click <a href="index.php">here</a> if you're here by mistake.</p>
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