<?php
/**
 * frontend script for something to allow for post creation/editing 
 */
require_once 'lib/common.php';
require_once 'lib/edit-post.php';
require_once 'lib/view-post.php';


session_start();

// only logged in and valid users should see this page
// kick anyone else back to the homepage
if (!isLoggedIn()) {  redirectAndExit('index.php');  }

// set empty defaults for this page
$title = $body = '';

// init the DB and get the handle for it
$pdo = getPDO();


// handle editing an existing post while making sure that editing a post doesn't create a new one
// if we're not submitting a new post,
// && there is an existing post primary key, 
// && that primary key's row exists, 
// grab the post's data 
// used for first display of article for editing and submitting edits for an article
// begin by unsetting any postId's then see if a new GET request can find a postId
$postId = null;
if (isset($_GET['post_id']))
{
    $post = getPostRow($pdo, $_GET['post_id']);
    if ($post)
    {
        $postId = $_GET['post_id'];
        $title = $post['title'];
        $body = $post['body'];
    }
}


// handle the post's content, making sure we're in a POST (form submit) operation first
$errors = array();
if ($_POST)
{
    // validate post's content
    $title = $_POST['post-title'];
    if (!$title) {  $errors[] = 'The post must have a title';  }
    
    $body = $_POST['post-body'];
    if (!$body) {  $errors[] = 'The post must have a body';  }
    
    // now we can start collecting data for our DB connection, using the DB handle already grabbed in $pdo
    if (!$errors)
    {
        $pdo = getPDO();
        
        // are we editing an existing post or creating a new one?
        // if an existing post ID is found, assume we're editing
        if ($postId)
        {
            editPost($pdo, $title, $body, $postId);
        }
        // if we can't find an existing post ID, assume we're creating
        else
        {
            // find our user ID for current connection
            $userId = getAuthUserId($pdo);
            
            // sets the new post ID while passing DB data for insertion
            $postId = addPost($pdo, $title, $body, $userId);
            
            if ($postId === false) {  $errors[] = 'Post operation failed';  }
        }
    }
    
    // and now redirect to the new or edited post after pushing to DB, after grabbing the new post data 
    // from our backend DB checker lib/view-post.php
    if (!$errors) {  redirectAndExit('edit-post.php?post_id=' . $postId);  }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <!-- <title>New post | A blog application</title> -->
        <?php require 'templates/head.php'; ?>
    </head>
    <body>
        <!-- custom page title options, based on if we're editing or creating a post -->
        <?php require 'templates/top-menu.php'; ?>
        <?php if (isset($_GET['post_id'])): ?>
            <title>Edit post | A blog application</title>
            <h1>Edit post</h1>
        <?php else: ?>
            <title>New post | A blog application</title>
            <h1>New post</h1>
        <?php endif ?>
        
        <!-- if we picked up any errors trying to build the post in this script, push them here -->
        <?php if ($errors): ?>
            <div class="error box">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>
        
        <!-- new post form with inputs for user supplied data or existing data if user did something wrong -->
        <form method="post" class="post-form user-form">
            <div>
                <label for="post-title">Title: </label>
                <input id="post-title" name="post-title" type="text"
                    value="<?php echo htmlEscape($title) ?>"
                />
            </div>
            <div>
                <label for="post-body">Body: </label>
                <textarea id="post-body" name="post-body" rows="12" cols="70">
                    <?php echo htmlEscape($body) ?>
                </textarea>
            </div>
            <div>
                <input type="submit" value="Save post" />
                <!-- make it easier to abandon an edit request -->
                <a href="index.php">Cancel</a>
            </div>
        </form>
        
    </body>
</html>