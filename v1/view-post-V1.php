<?php
//
//
// BUSINESS LOGIC HAS BEEN MOVED FROM THIS FILE TO lib/view-post.php FOR MAINTAINABILITY
// THE ORIGINAL FILE HAS BEEN BACKED UP IN THE CURRENT DIRECTORY UNDER view-post-V1.php
// 
//
// for viewing individual posts


// provide a path to the DB to allow SQLite/PDO to connect
// $root = __DIR__;
// $database = $root . '/data/data.sqlite';
// $dsn = 'sqlite:' . $database;
require_once 'lib/common.php';

// get the appropriate post ID to display
if (isset($_GET['post_id']))
{
    $postId = $_GET['post_id'];
}
else
{
    // allows a post ID var to always be defined ('oops, couldn't find it' page)
    $postId = 0;
}

// connect to the DB
// $pdo = new PDO($dsn);
$pdo = getPDO();        // accesses common.php function
// make a query against the DB, get your PDO object ready to issue the query
// takes the value specified by colon in the WHERE statement, to be used as an arg in ->execute method ("parameterisation")
// parameterisation provides a secure way to inject user supplied inputs
$stmt = $pdo->prepare('SELECT title, created_at, body FROM post WHERE id = :id');
// handle errors
if ($stmt === false)
{
    throw new Exception('There was a problem preparing this query');
}

// run the prepared query, 
// providing a static id to look for as an arg to our ->prepare method, to be updated to allow for user input injection
// $result = $stmt->execute(array('id' => 1,));
$result = $stmt->execute(array('id' => $postId,));      // replaces static value for 'id'
// handle errors
if ($result === false)
{
    throw new Exception('There was a problem running this query');
}

// get the returned row
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// make carriage returns = paragraph breaks instead of echoing $row['body']
$bodyText = htmlEscape($row['body']);       // go ahead and escape out shitty html breaking chars using common.php htmlEscape function
$paraText = str_replace("\n", "</p><p>", $bodyText);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>
            A blog application | <?php echo htmlEscape($row['title']) ?>     <!-- common.php function htmlEscape() -->
        </title>
        
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    </head>
    <body>
        <!-- bring in our title template -->
        <?php require 'templates/title.php'; ?>
        
        <h2>
            <?php echo htmlEscape($row['title']) ?>     <!-- common.php function htmlEscape() -->
        </h2>
        <div>
            <?php 
                // echo $row['created_at']      // converted to a better read-able date format like in index.php:
                echo convertSqlDate($row['created_at'])
            ?>
        </div>
        <p>
                <!-- echo htmlEscape($row['body'])  // common.php function htmlEscape() -->
            <!-- instead of just echoing, add logic to interpret two carriage-returns in a post as paragraph breaks -->
            <!-- this is already escaped in $bodyText, no need to do it again -->
            <?php echo $paraText ?>
        </p>
        
        <!-- insert count of comments per post -->
        <h3><?php echo countCommentsForPost($postId) ?> comments.</h3>
        
        <!-- get the comments' contents per each post, push them into their own divs -->
        <?php foreach (getCommentsForPost($postId) as $comment): ?>
            <!-- temporary line (horizontal rule) to make each comment look distinct -->
            <hr />
            <div class="comment">
                <div class="comment-meta">
                    Comment from <?php echo " " . htmlEscape($comment['name']) . " " ?>
                    on <?php echo " " . convertSqlDate($comment['created_at']) ?>
                </div>
                <div class="comment-body">
                    <?php echo htmlEscape($comment['text']) ?>
                </div>
            </div>
        <?php endforeach ?>
    </body>
</html>