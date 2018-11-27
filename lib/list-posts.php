<?php
/**
 * lib/list-posts.php
 * 
 * backend script for deleting a specific post
 * 
 * To make sure we don't throw a foreign key constraint violation by trying to 
 * delete a row that our foreign key depends on (ie: trying to delete a post 
 * to which comments are still attached).
 * 
 * We'll need to first delete the attached comments and THEN the post itself.
 * 
 * @param PDO $pdo
 * @param integer $postId
 * @return boolean Returns true on successful deletion
 * @throws Exception
 */
function deletePost(PDO $pdo, $postId)
{
    // two queries, first to delete comments for the key objection then the post
    $sqls = array(
        "DELETE FROM comment WHERE post_id = :id",
        "DELETE FROM post WHERE id = :id",
        );
    
    // prepare and execute both queries in our $sqls array
    foreach ($sqls as $sql)
    {
        $stmt = $pdo->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception('There was a problem preparing this query');
        }
        
        $result = $stmt->execute(array('id' => $postId, ));
        
        // if something went wrong, don't continue running our queries
        if ($result === false) {  break;  }
    }
    
    // confirm both queries were completed
    return $result !== false;
}
?>