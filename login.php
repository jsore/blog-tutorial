<?php 
/**
 * business logic behind frontend form
 *
 * checks for form submit, 
 * turns on our session, 
 * creates a hash of the submitted password,
 * compares the hash with the has stored in the DB by install form,
 * if matched call login() to sign in the user then redirectAndExit() to go back home
 */
require_once 'lib/common.php';
// require_once 'vendor/password_compat/lib/password.php';

// PHP version check
if (version_compare(PHP_VERSION, '5.3.7') < 0)
{
    throw new Exception('This system needs PHP 5.3.7 or newer, login denied');
}


// start a new session to handle the login process
session_start();

// if we're already logged in, there's no point in rendering this page so go back home
if (isLoggedIn()) {  redirectAndExit('index.php');  }
    
    
// handle form submission for login page
$username = '';
if ($_POST)
{
    // init our DB
    $pdo = getPDO();
    
    // if the password is correct, handle login and redirect to home page
    $username = $_POST['username'];
    $ok = tryLogin($pdo, $username, $_POST['password']);    // common.php
    if ($ok)
    {
        login($username);
        redirectAndExit('index.php');
    }
}
?>


<!-- begin front end portion of the login page -->
<!DOCTYPE html>
<html>
    <head>
        <title>Login | A blog application</title>
        <?php require 'templates/head.php'; ?>
    </head>
    <body>
        <?php require 'templates/title.php'; ?>
        
        <!-- "if we have a username, then the user got something wrong, so throw error" -->
        <?php if ($username): ?>
            <div class="error box">The username or password is incorrect, try again.</div>
        <?php endif ?>
        
        <p>Login here:</p>
        <form method="post" class="user-form">
            <div>
                <label for="username">Username: </label>
                <input type="text" id="username" name="username" value="<?php echo htmlEscape($username) ?>" />
            </div>
            <div>
                <label for="password">Password: </label>
                <input type="password" id="password" name="password" />
            </div>
            <input type="submit" name="submit" value="Login" />
        </form>
    </body>
</html>