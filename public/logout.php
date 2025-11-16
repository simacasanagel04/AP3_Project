<?php
// public/logout.php

session_start();

// Destroy session
session_unset();
session_destroy();

// Clear all cookies
if (isset($_COOKIE['loggedIn'])) {
    setcookie("loggedIn", "", time() - 3600, "/");
}
if (isset($_COOKIE['userType'])) {
    setcookie("userType", "", time() - 3600, "/");
}
if (isset($_COOKIE['userId'])) {
    setcookie("userId", "", time() - 3600, "/");
}
if (isset($_COOKIE['userName'])) {
    setcookie("userName", "", time() - 3600, "/");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
</head>
<body>
<script>
    // Clear all localStorage related to user
    localStorage.removeItem('aksyon_user_data');
    localStorage.removeItem('user_name');
    localStorage.removeItem('is_logged_in');
    localStorage.removeItem('pat_id');
    localStorage.removeItem('doc_id');
    localStorage.removeItem('staff_id');
    localStorage.removeItem('user_type');
    localStorage.removeItem('user_id');
    localStorage.removeItem('email');
    localStorage.removeItem('password');
    localStorage.removeItem('logged_in');
    localStorage.removeItem('dashboard_link');
    
    // Clear all localStorage items completely
    localStorage.clear();
    
    // Clear sessionStorage as well
    sessionStorage.clear();
    
    console.log('All user data cleared. Redirecting to homepage...');
    
    // Redirect to index
    window.location.href = '../index.php';
</script>
</body>
</html>