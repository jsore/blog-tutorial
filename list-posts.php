<?php
/** 
 * frontend script to list all posts, available only to administrative users
 */
require_once 'lib/common.php';
require_once 'lib/list-posts.php';

session_start();

// only logged in and valid users should see this page
// kick anyone else back to the homepage
if (!isLoggedIn()) {  redirectAndExit('index.php');  }

if ($_POST)
{
    $deleteResponse = $_POST['delete-post'];
    if ($deleteResponse)
    {
        // ???
        $keys = array_keys($deleteResponse);
        $deletePostId = $keys[0];
        if ($deletePostId)
        {
            deletePost(getPDO(), $deletePostId);
            redirectAndExit('list-posts.php');
        }
    }
}

// connect to the DB and run a query for our posts
$pdo = getPDO();
$posts = getAllPosts($pdo);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>All posts | A blog application</title>
        <?php require 'templates/head.php'; ?>
    </head>
    <body>
        <?php require 'templates/top-menu.php'; ?>
        
        <h1>Post list</h1>
        <p>Displaying <?php echo count($posts) ?> posts.</p>
        
        <form method="post">
            <table id="post-list">
                <!-- v1 old hard coded wire framing data --><!--
                <tbody>
                    <tr>
                        <td>Title of the first post</td>
                        <td>dd MM YYY h:mi</td>
                        <td>
                            <a href="edit-post.php?post_id=1">Edit</a>
                        </td>
                        <td>
                            <input type="submit" name="post[1]" value="Delete" />
                        </td>
                    </tr>
                    <tr>
                        <td>Title of the second post</td>
                        <td>dd MM YYY h:mi</td>
                        <td>
                            <a href="edit-post.php?post_id=2">Edit</a>
                        </td>
                        <td>
                            <input type="submit" name="post[2]" value="Delete" />
                        </td>
                    </tr>
                    <tr>
                        <td>Title of the third post</td>
                        <td>dd MM YYY h:mi</td>
                        <td>
                            <a href="edit-post.php?post_id=3">Edit</a>
                        </td>
                        <td>
                            <input type="submit" name="post[3]" value="Delete" />
                        </td>
                    </tr>
                </tbody>-->
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Creation date</th>
                        <th>Comments</th>
                        <th />
                        <th />
                    </tr>
                </thead>
                <tbody>
                
                    <!-- loop through all posts, similar to index.php but stick the posts into a table -->
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <!-- post title with link to the post's view page -->
                                <a href="view-post.php?post_id=<?php echo $post['id'] ?>">
                                    <?php echo htmlEscape($post['title']) ?>    
                                </a>
                            </td>
                            <td>
                                <?php echo convertSqlDate($post['created_at']) ?>
                            </td>
                            <td>
                                <?php echo $post['comment_count'] ?>
                            </td>
                            <td>
                                <a href="edit-post.php?post_id=<?php echo $post['id'] ?>">Edit</a>
                            </td>
                            <td>
                                <input 
                                    type="submit" 
                                    name="delete-post[<?php echo $post['id'] ?>]"
                                    value="Delete"
                                />
                            </td>
                        </tr>
                    <?php endforeach ?>    
                
                </tbody>
            </table>
        </form>
        
    </body>
</html>