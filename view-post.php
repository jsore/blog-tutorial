<?php
/**
 * frontend script to view a single post
 */
require_once 'lib/common.php';      // grab common functions script
require_once 'lib/view-post.php';   // grab logic holding our DB connection and query

// start a new session
session_start();

// grab the post's ID if our _GET request was processed or if no ID is found supply one
if (isset($_GET['post_id']))
{
    $postId = $_GET['post_id'];
}
else
{
    $postId = 0;
}


// connect to our DB through common.php functions, run single query for single post data and its comment count
$pdo = getPDO();
$row = getPostRow($pdo, $postId);
$commentCount = $row['comment_count'];

// if the post doesn't exist, deal with the redirect
// scenario will likely not happen, but let's make sure it's handled just in case
if (!$row)
{
    redirectAndExit('index.php?not-found=1');
}


// begin handling comment-form data
// reset the errors array
$errors = null;

// detect if page is being rendered with a form submit or if it's being rendered normally
if ($_POST)
{
    # pick up our comment input fields rendered by comment-form
    # then send the comment to our webserver with a POST request, to be picked up by $sql in addCommentToPost()
    #
    # $commentData = array(
    #     'name' => $_POST['comment-name'],
    #     'website' => $_POST['comment-website'],
    #     'text' => $_POST['comment-text'],
    # );
    # $errors = handleAddComment($pdo, $postId, $commentData);
    #
    # Updated as switch/case statement below to allow for user auth checking before allowing the optoin to delete a comment:
    switch ($_GET['action'])
    {
        // if the add comment form submit was actioned, run essentially the same codeblock commented out above:
        case 'add-comment':
            $commentData = array(
                'name' => $_POST['comment-name'],
                'website' => $_POST['comment-website'],
                'text' => $_POST['comment-text'],
            );
            $errors = handleAddComment($pdo, $postId, $commentData);
            break;
        
        // if the delete comment form submit was actioned, init handler in backend lib/view-post.php:
        case 'delete-comment':
            $deleteResponse = $_POST['delete-comment'];
            handleDeleteComment($pdo, $postId, $deleteResponse);
            break;
    }
}
else    // if the page is loaded via GET be sure inputs are set to an empty value
{
    $commentData = array(
        'name' => '',
        'website' => '',
        'text' => '',
    );
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>
            A blog application | <?php echo htmlEscape($row['title']) ?>     <!-- common.php function htmlEscape() -->
        </title>
        
        <?php require 'templates/head.php'; ?>
    </head>
    <body>
        <!-- bring in our title template -->
        <?php require 'templates/title.php'; ?>
        
        <div class="post">
            <h2><?php echo htmlEscape($row['title']) ?></h2>
            
            <div class="date">
                <?php echo convertSqlDate($row['created_at']) ?>
            </div>
            
            <!-- insert the blog post, escaped and sanitized -->
            <?php echo convertNewlinesToParagraphs($row['body']) ?>
        </div>
        
        <!-- comment-list div, served elsewhere for modularity -->
        <?php require 'templates/list-comments.php'; ?>
        
        <!-- load our add comment form to the bottom of the individual post being viewed -->
        <!-- allow use of $commentData -->
        <?php require 'templates/comment-form.php'; ?>
    </body>
</html>