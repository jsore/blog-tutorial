<!-- top header/menu items -->
<!-- kept separate from title.php to allow for custom titles in post edit/create pages -->

<div class="top-menu">
    <!-- <p>test</p> -->
    <div class="menu-options">
        
        <!-- check if we're logged in and provide a way to log out... -->
        <?php if (isLoggedIn()): ?>
            Hello <?php echo htmlEscape(getAuthUser()) ?> || 
            <a href="index.php">Home</a> || <!-- adds link to homepage for pages that require user to be authed  -->
            <a href="list-posts.php">All posts</a> || 
            <a href="edit-post.php">New post</a> || 
            <a href="install.php">Installer</a> || 
            <a href="logout.php">Log out</a>
        
        <!-- ...or provide a way to log in -->    
        <?php else: ?>
            <a href="login.php">Log in</a>
        
        <?php endif ?>
    </div>
</div>