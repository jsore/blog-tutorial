<?php
/**
 * This is a blog-like application.
 * 
 * Summary of details about this project:
 *  - DB connections are managed using a PDO with SQLite, not dedicated MySQL DB
 *  - It's not going to look very pretty, this is more of a POC for functional concepts
 *  - MVC framework will be followed as close as possible
 *  - Directories containing sensitive info or scripts are not publiclly accessible
 *  - An installer script is used to initiate some mock DB data to make it esaier to re-establish the DB for testing
 *  - Core web app features
 *      ..A home page with summaries of article posts
 *      ..Each post gets a dedicated page with synopsis data of the post itself
 *      ..Each dedicated post gets a comment section with a comment submit form for new comments
 *      ..Users don't need to be logged in to comment
 *      ..All submittable text forms and inputs get validated and sanitized for script injection and visual formatting
 *      ..A login system to authenticate users 
 *      ..User passwords get properly hashed in the DB
 *      ..If logged in, a user is able to add new posts, edit/delete their posts/comments or view all their posts
 *      ..An admin user is able to easily view all posts site-wide and delete/edit from one portal, manage all comments
 *      ..If not logged in or properly authed, appropriate functions are stripped and non-accessible
 *      ..Proper redirects are embedded across all pages for easier navigation. 
 */
require_once 'lib/common.php';  // custom function lib for this app

// init a session
session_start();

// connect to the DB with a new PDO
// common.php
$pdo = getPDO();

// go ahead and grab all the posts we have so far
$posts = getAllPosts($pdo);

// begin building a redirect and error a post is navigated to but not found in the DB
$notFound = isset($_GET['not-found']);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>A blog application</title>
        
        <!-- bring in the page header -->
        <?php require 'templates/head.php'; ?>
    </head>
    <body>
        <!-- bring in the homepage title -->
        <?php require 'templates/title.php'; ?>
        
        <!-- here's our redirect and error for posts the DB can't find -->
        <?php if ($notFound): ?>
            <div class="error box">Error: cannot find the requested blog post.</div>
        <?php endif ?>
        
        <!-- main div wrapper -->
        <div class="post-list">
            
            <!-- begin looping through our blog data -->
            <?php foreach ($posts as $post): ?>
                
                <div class="post-synopsis">
                    <!-- bring in each $post title and escape the text -->
                    <h2><?php echo htmlEscape($post['title']) ?></h2>
                    
                    <div class="meta">
                        <?php echo convertSqlDate($post['created_at']) ?>
                        (<?php echo $post['comment_count'] ?> comments)
                    </div>
                    
                    <p><?php echo htmlEscape($post['body']) ?></p>
                    
                    <div class="post-controls">
                        <!-- dynamically assign the proper post id to the link bringing you to the specific post -->
                        <a href="view-post.php?post_id=<?php echo $post['id'] ?>">Read more</a>
                        
                        <!-- and if a user is logged in, give them the option to edit posts -->
                        <?php if (isLoggedIn()): ?>
                            || <a href="edit-post.php?post_id=<?php echo $post['id'] ?>">Edit post</a>
                        <?php endif ?>
                    </div>
                    
                </div>
            <?php endforeach ?>
        </div>
    </body>
</html>