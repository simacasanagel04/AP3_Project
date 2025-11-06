<?php
// public/logout.php

session_start();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
</head>
<body>
    <script>
        // Clear localStorage
        localStorage.removeItem('user_name');
        localStorage.removeItem('is_logged_in');
        
        // Redirect to home
        window.location.href = '../index.php';
    </script>
</body>
</html>
