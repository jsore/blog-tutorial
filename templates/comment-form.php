<?php 
/** 
 * frontend script for rendering comment forms for individual posts
 * 
 * @var $errors string
 * @var $commentData array
 */
?>

<!-- use a horizontal rule for now for easy page separation -->
<!-- <hr /> -->

<!-- report errors, rendered as bullet-point UL in a standalone div -->
<?php if ($errors): ?>
    <div class="error box comment-margin">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

<h3>Add a comment about this post:</h3>

<!-- <form method="post" class="comment-form user-form"> -->
<!-- we need to clarify what we're doing with form submissions, since they're are two in this form -->
<!-- (add-comment and delete-comment) -->
<!-- add-comment form goes here, delete goes in templates/list-comments.php -->
<form
    action="view-post.php?action=add-comment&amp;post_id=<?php echo $postId ?>"
    method="post"
    class="comment-form user-form"
>

    <!-- various form input options, making sure to validate user supplied text -->
    <!-- 
        manually set the value of the input to what the user supplied to avoid the text getting 
        wiped if an error is triggered, the GET request in view-post.php also needs to be set
        to empty values since a GET means page load without form submission 
    -->
    <div>
        <label for="comment-name">Name: </label>
        <input type="text" id="comment-name" name="comment-name" 
            value="<?php echo htmlEscape($commentData['name']) ?>"
        />
    </div>
    <div>
        <label for="comment-website">Website: </label>
        <input type="text" id="comment-website" name="comment-website" 
            value="<?php echo htmlEscape($commentData['website']) ?>"
        />
    </div>
    <div>
        <label for="comment-text">Comment: </label>
        <textarea id="comment-text" name="comment-text" rows="8" cols="70">
            <?php echo htmlEscape($commentData['text']) ?>
        </textarea>
    </div>
    <div>
        <input type="submit" value="Submit Comment" />
    </div>
</form>