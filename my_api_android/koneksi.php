<?php
$hostName = "localhost";
$userName = "root";
$password = "";
$dbName = "user_database"; // Ganti sesuai nama database Anda

// Membuat koneksi ke database
$koneksi = new mysqli($hostName, $userName, $password, $dbName);

// Memeriksa apakah koneksi berhasil
if ($koneksi->connect_error) {
    die("Koneksi Gagal: " . $koneksi->connect_error);
}
?>
