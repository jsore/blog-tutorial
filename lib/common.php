<?php
/**
 * Start this lib off with an easier way to pull in a DB connection
 * 
 * first establish our projects root directory,
 * then establish where we want our SQLite DB file to go,
 * establish our Data Source Name and where PHP/SQL can find it,
 * finally start up a new PDO using this DB information and return a pointer to this DB connection
 *
 * if I were to do this over again, I'd have this DB connection set up in dedicated conn.php file elsewhere
 *
 * @return string
 * @return string
 * @return string
 * @return PDO $pdo
 */
function getRootPath() 
{
    return realpath(__DIR__ . '/..');
}

function getDatabasePath()
{
    return getRootPath() . '/data/data.sqlite';
}

function getDsn()
{
    return 'sqlite:' . getDatabasePath();
}

function getPDO()
{
    /* return new PDO(getDsn()); */
        // ^^^ updated to allow for foreign key constraints:
    $pdo = new PDO(getDsn());
    
    // foreign key constraints need to be enabled manually in SQLite:
    $result = $pdo->query('PRAGMA foreign_keys = ON');
    
    if ($result === false)
    {
        throw new Exception('Could not turn on foreign key constraints');
    }
    
    // now we can throw the DB connection back to whatever is requesting it
    return $pdo;
}


/**
 * escapes HTML for safe output
 * 
 * @param string $html
 * @return string
 */
function htmlEscape($html)
{
    return htmlspecialchars($html, ENT_HTML5, 'UTF-8');
}


/**
 * improves human readability of blog post dates
 *
 * @var $date DateTime
 */
function convertSqlDate($sqlDate)
{
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $sqlDate);
    return $date->format('d M Y, H:i');
}


/**
 * create timestamps that are more suitable for the DB server
 */
function getSqlDateForNow()
{
    return date('Y-m-d H:i:s');
}


/**
 * gets a list of all posts in reverse order
 *
 * @param PDO $pdo
 * @return array
 */
function getAllPosts(PDO $pdo)
{
     
    # $stmt = $pdo->query('SELECT id, title, created_at, body 
    #     FROM post ORDER BY created_at DESC'
    #     );
    #
    # added sub-query to grab comment counts, which we'll explicitly name 'comment_count'
    # sub-query is making a comparison to the outer query's table (post) then counting the
    # rows in the comment table based on the post it belongs to
    $stmt = $pdo->query(
        'SELECT id, title, created_at, body, (SELECT COUNT(*) FROM comment WHERE comment.post_id = post.id) comment_count
        FROM post ORDER BY created_at DESC'
    );

    if  ($stmt === false)
    {
        throw new Exception('There was a problem running this query');
    }
    
    // send back everything we found to calling function
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * clean user supplied text, with properly formatted HTML paragraphs for 
 * each carriage return
 * 
 * @param string $text
 * @return string
 */
function convertNewlinesToParagraphs($text)
{
    $escaped = htmlEscape($text);
    return '<p>' . str_replace("\n", "</p><p>", $escaped) . '</p>';
}


/**
 * browser redirect if DB row not found
 *
 * reads current domain and pushes a Location HTTP header to the browser to request the specified address,
 * then forcibly exits the PHP function so that we're not waiting for PHP to detect a browser disconnect
 *
 * @param file $script
 */
function redirectAndExit($script)
{
    // get the domain-relative URL (ex: /blog/lib/view-post.php) and work out
    // what directory the file is actually in, then store the path
    $relativeUrl = $_SERVER['PHP_SELF'];
    $urlFolder = substr($relativeUrl, 0, strrpos($relativeUrl, '/') + 1);
    
    // and then redirect the browser to full URL (http://apachetestserver.com:8080/blog/lib/view-post.php)
    $host = $_SERVER['HTTP_HOST'];
    $fullUrl = 'http://' . $host . $urlFolder . $script;
    // $port = ':8080';
    // $fullUrl = 'http://' . $host . $port . $urlFolder . $script;
    header('Location: ' . $fullUrl);
    exit();
}


# /**
#  * returns the number of comments for a specified post using SQL COUNT() command 
#  * to count the rows in the specified table
#  *
#  * @param PDO $pdo
#  * @param integer $postId
#  * @return integer
#  */
# function countCommentsforPost(PDO $pdo, $postId)
# {
#     $sql = "SELECT COUNT(*) c FROM comment WHERE post_id = :post_id";
#     
#     $stmt = $pdo->prepare($sql);
#     $stmt->execute(array('post_id' => $postId, ));
#     
#     return (int) $stmt->fetchColumn();
# }
# 
# This entire function was replcaed by SQL sub-query for 'comment_count' in the 
# querey for getAllPosts() to limit DB connection attempts


/**
 * returns all the comments for the specified post
 * 
 * @param PDO $pdo
 * @param integer $postId
 * @return array $stmt Each row is pushed into a separate array, return the rows as an array
 */
function getCommentsForPost(PDO $pdo, $postId)
{   
    $sql = "SELECT id, name, text, created_at, website FROM comment WHERE post_id = :post_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('post_id' => $postId, ));
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/** 
 * handles user login/logout/validation, generates a new cookie for the user for the session
 * and provides methods to grab some user data
 *
 * @param string $username
 */
function tryLogin(PDO $pdo, $username, $password)
{    
    // buiild and prepare our query
    $sql = "SELECT password FROM user WHERE username = :username AND is_enabled = 1";
    $stmt = $pdo->prepare($sql);
    
    // tell the execute function to use supplied $username as :username
    $stmt->execute(array('username' => $username, ));
    
    // grab this row's hash then check it against the hashing library
    $hash = $stmt->fetchColumn();
    $success = password_verify($password, $hash);
    
    return $success;
}

// now that the credentials have been vetted, generate a new cookie for our new session
function login($username)
{
    session_regenerate_id();
    $_SESSION['logged_in_username'] = $username;
}

// provide method for logging someone out
function logout()
{
    unset($_SESSION['logged_in_username']);
}

// checks if there's someone logged in and replies null or grabs that user's username
function getAuthUser()
{
    return isLoggedIn() ? $_SESSION['logged_in_username'] : null;
}
function isLoggedIn()
{
    return isset($_SESSION['logged_in_username']);
}

// grabs logged in username's ID or replies null if not logged in 
function getAuthUserId(PDO $pdo)
{
    if (!isLoggedIn()) {  return null;  }
    
    $sql = "SELECT id FROM user WHERE username = :username AND is_enabled = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('username' => getAuthUser()));
    
    return $stmt->fetchColumn();
}
?>