<?php
// Simpan sebagai check_access.php
function checkAccess($allowed_roles) {
    session_start();
    
    // Cek apakah user sudah login
    if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        header("Location: ../login.php");
        exit();
    }
    
    // Cek apakah role user diizinkan
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../unauthorized.php");
        exit();
    }
}
?>