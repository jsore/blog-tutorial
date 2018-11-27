/** 
 * SQLite database script, SQLite uses SQL DB file instead of connecting to a SQL database (PDO)
 * Sets up DB, save data from blog, guery blog data
 *
 * if it's not evident enough:
 *  the data supplied here is just test data
 *  the tables may be used in the future for dynamic data (user-supplied, new posts, etc)
 *  but, all the INSERTs here are just used to easily re-create the fake DB data when needed
 */

/**
 * introduce and use foreign key constraints:
 * basically, specify values pushed into a column need to first exist in another column in another table
 * will keep app from crashing by not allowing a non-existant user primary key to be stored
 */
PRAGMA foreign_keys = ON;

 
DROP TABLE IF EXISTS user;

CREATE TABLE user (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    username VARCHAR NOT NULL,
    password VARCHAR NOT NULL,
    created_at VARCHAR NOT NULL,
    is_enabled BOOLEAN NOT NULL DEFAULT true
);

/**
 * This will become user=1, created to satisfy constraints
 * password that will actually be used gets properly hashed in the installer
 *
 * since this hardcoded mock user was created before the posts are, the posts won't fail the foreign key check
 */
 INSERT INTO user
    (
        username, password, created_at, is_enabled
    )
    VALUES
    (
        "admin", "unhashed-password", datetime('now', '-3 months'), 0   -- create the mock user as not enabled, updated when installer is ran
    );
 

/**
 * homepage/parent of all blog posts
 */
DROP TABLE IF EXISTS post;  -- since this will be used to init a mock DB, begin by making sure nothing exists

-- setting up the table for our posts
CREATE TABLE post (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    title VARCHAR NOT NULL,
    body VARCHAR NOT NULL,
    user_id INTEGER NOT NULL,
    created_at VARCHAR NOT NULL,
    updated_at VARCHAR,
    FOREIGN KEY (user_id) REFERENCES user(id)    -- so, new posts can't be created without there being a logged in user...
);

-- first mock post
INSERT INTO post
    (
        title, body, user_id, created_at
    )
    VALUES
    (
        "Here's our first post",
        "This is the body of the first post.
            It is split into paragraphs.",
        1,
        -- date('now', '-2 months')
        datetime('now', '-2 months', '-45 minutes', '+10 seconds')
    );

-- second mock post
INSERT INTO post
    (
        title, body, user_id, created_at
    )
    VALUES
    (
        "Now for a second article",
        "This is the body of the second post.
            This is another paragraph.",
        1,
        -- date('now', '-40 days')
        datetime('now', '-40 days', '+815 minutes', '+37 seconds')
    );

-- third mock post
INSERT INTO post
    (
        title, body, user_id, created_at
    )
    VALUES
    (
        "Here's a third post.",
        "This is the body of the third post.
            This is split into paragraphs.",
        1,
        -- date('now', '-13 days')
        datetime('now', '-13 days', '+198 minutes', '+51 seconds')
    );
    

    
/** 
 * user-comments section of the mock DB data, sets DB up to allow for the user comment functionality
 */
DROP TABLE IF EXISTS comment;  -- since this will be used to init a mock DB, begin by making sure nothing exists

-- set up our table for our comments, for both mock data and user-submitted comments
CREATE TABLE comment (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    post_id INTEGER NOT NULL,
    created_at VARCHAR NOT NULL,
    name VARCHAR NOT NULL,
    website VARCHAR,
    text VARCHAR NOT NULL,
    FOREIGN KEY (post_id) REFERENCES post(id)       -- ...and new comments can't be created with there first being a post to comment on
);

-- first mock comment
INSERT INTO comment
    (
        post_id, created_at, name, website, text
    )
    VALUES
    (
        1,
        -- date('now', '-10 days'),
        datetime('now', '-10 days', '+231 minutes', '+7 seconds'),
        'Jimmy',
        'http://example.com/',
        "This is Jimmy's contribution"
    );
    
-- ...and then our second
INSERT INTO comment
    (
        post_id, created_at, name, website, text
    )
    VALUES
    (
        1,
        -- date('now', '-8 days'),
        datetime('now', '-8 days', '+549 minutes', '+32 seconds'),
        'Johnny',
        'http://anotherexample.com/',
        "This is a coment from Johnny"
    );