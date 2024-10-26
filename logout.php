<?php
session_start();

// Hapus semua session
session_unset();
session_destroy();

// Hapus cookie jika ada
if (isset($_COOKIE['username'])) {
    setcookie('username', '', time() - 3600, '/');
}

// Redirect ke login page
header("Location: login.php");
exit();
?>