<?php
/**
 * lib/edit-post.php
 *
 * backend script for post editing or creation
 */


// handle inserting new post data
function addPost(PDO $pdo, $title, $body, $userId)
{
    // build query statement and prepare it or throw an error
    $sql = "INSERT INTO post (title, body, user_id, created_at)
            VALUES (:title, :body, :user_id, :created_at)";
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt === false)
    {
        throw new Exception('Could not PREPARE post ADDITION query');
    }
    
    // run our query with provided parameters or throw an error
    $result = $stmt->execute(array(
        'title' => $title,
        'body' => $body,
        'user_id' => $userId,
        'created_at' => getSqlDateForNow(),
    ));
    
    if ($result === false)
    {
        throw new Exception('Could not RUN post ADDITION query');
    }
    
    // return the ID of the newest created row
    return $pdo->lastInsertId();
}


// handle inserting updates to existing post data
function editPost(PDO $pdo, $title, $body, $postId)
{
    // build edit row query statement and prepare it or throw an error
    $sql = "UPDATE post 
            SET title = :title, body = :body 
            WHERE id = :post_id";
            
    $stmt = $pdo->prepare($sql);
    
    if ($stmt === false)
    {
        throw new Exception('Could not PREPARE post UPDATE query');
    }
    
    // then run that query with provided parameters or throw an error
    $result = $stmt->execute(array(
        'title' => $title,
        'body' => $body,
        'post_id' => $postId,
    ));
    
    if ($result === false)
    {
        throw new Exception('Could not RUN post UPDATE query');
    }
    
    // send an Ack to calling function to confirm that we're done here
    return true;
}