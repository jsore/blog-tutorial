<?php
/**
 * blog/lib/view-post.php
 *
 * this file holds the meat of the logic behind blog/view-post.php
 * basically the backend version of the view-post.php file that is called by the server
 */


/**
 * called to handle the comment form, redirects upon success
 *
 * @param PDO $pdo
 * @param integer $postId
 * @param array $commentData
 */
function handleAddComment(PDO $pdo, $postId, array $commentData)
{
    // see if we got any errors pushing and writing data to DB via addCommentToPost()
    // since we're not calling redirectAndExit() here, errors will render within current form
    $errors = addCommentToPost(
        $pdo,
        $postId,
        $commentData
    );
    
    // if error checks pass, redirect back to our post and redisplay with the new comment via a GET request
    if (!$errors) {  redirectAndExit('view-post.php?post_id=' . $postId);  }
    
    return $errors;
}


/**
 * gets called to handle the delete comment form, redirects after
 *
 * the $deleteREsponse array is expected to be in the form:
 *      Array ( [6] => Delete )
 *
 * which comes directly from input elements of this form:
 *      name="delete-comment[6]"
 * 
 * @param PDO $pdo
 * @param integer $postId
 * @param array $deleteResponse
 */
function handleDeleteComment(PDO $pdo, $postId, array $deleteResponse)
{
    // user auth check is first
    if (isLoggedIn())
    {
        // grab the primary ID keys picked up list-posts.php and /lib/list-posts.php
        $keys = array_keys($deleteResponse);
        $deleteCommentId = $keys[0];
        if ($deleteCommentId)
        {
            deleteComment($pdo, $postId, $deleteCommentId);     // lib/view-post.php
        }
        
        redirectAndExit('view-post.php?post_id=' . $postId);
    }
}


/**
 * deletes a specified comment within a specified post
 *
 * @param PDO $pdo
 * @param integer $postId
 * @param integer $commentId
 * @return boolean True if the command executed without errors
 * @throws Exception
 */
function deleteComment(PDO $pdo, $postId, $commentId)
{
    // we could just use comment.id but lets be safe and check for the proper post.id as well
    $sql = "DELETE FROM comment WHERE post_id = :post_id AND id = :comment_id";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt === false)
    {
        throw new Exception('There was a problem PREPARING this DELETE COMMENT query');
    }
    
    $result = $stmt->execute(array('post_id' => $postId, 'comment_id' => $commentId));
    
    return $result !== false;
}

    
/**
 * Retrieves a single post
 *
 * @param PDO $pdo
 * @param integer $postId
 * @throws Exception
 * @return specificied post $row
 */
function getPostRow(PDO $pdo, $postId)
{
    // get our query prepared
    # $stmt = $pdo->prepare('SELECT title, created_at, body FROM post WHERE id = :id');
    # 
    # in an effort to limit DB connection requests, go ahead and grab comment count as a sub-query
    # while we grab the post we're interested in:
    $stmt = $pdo->prepare(
        'SELECT title, created_at, body, (SELECT COUNT(*) FROM comment WHERE comment.post_id = post.id) comment_count
        FROM post WHERE id = :id');
    
    // throw an error if the SQL statement can't be ran against our DB
    if ($stmt === false)
    {
        throw new Exception('There was a problem PREPARING this query.');
    }
    
    // store the results from executing our prepared statement into array
    $result = $stmt->execute(array('id' => $postId, ));
    
    if ($result === false)
    {
        throw new Exception('There was a problem RUNNING this query.');
    }
    
    // get then return the row (post) we want
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row;
}


/**
 * handles add comment form
 * logic that will handle commentst captured comment data in comment-form.php
 *
 * @param PDO $pdo
 * @param integer $postId
 * @param array $commentData
 * @return array
 */
function addCommentToPost(PDO $pdo, $postId, array $commentData)
{  
    // data validation checking and start pushing some errors to our $errors array
    $errors = array();

    if (empty($commentData['name']))
    {
        $errors['name'] = 'A name is required.';
    }

    if (empty($commentData['text']))
    {
        $errors['text'] = 'A comment is required...since you are trying to submit a comment...';
    }

    
    // if error checks pass, try writing the comment to the DB or return $errors array with DB error
    if (!$errors)
    {
        // write our SQL statement and get it prepared within our PDO object
        $sql = "INSERT INTO comment (name, website, text, created_at, post_id)
                VALUES (:name, :website, :text, :created_at, :post_id)";
        
        $stmt = $pdo->prepare($sql);
        
        if ($stmt === false)
        {
            throw new Exception('Cannot prepare stmt to insert comment');
        }
        
        // slap a timestamp on the comment
            // $createdTimestamp = date('Y-m-d H:m:s');
        // moved to common.php getSqlDateForNow()
        
        // execute and push comment into our session's comment array for a specific blog post
        $result = $stmt->execute(
            // array_merge($commentData, array('post_id' => $postId, 'created_at' => $createdTimestamp, ))
            array_merge($commentData, array('post_id' => $postId, 'created_at' => getSqlDateForNow(), ))
        );
        
        if ($result === false)
        {
            // TODO - this will render a DB-level message to the user, needs to be fixed
            $errorInfo = $pdo->errorInfo();
            if ($errorInfo)
            {
                $errors[] = $errorInfo[2];
            }
        }
    }
    return $errors;    
}
?>