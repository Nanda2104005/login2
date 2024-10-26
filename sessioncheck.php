<?php
session_start();

// Fungsi untuk mengecek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['username']);
}

// Redirect ke login.php jika belum login
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?>