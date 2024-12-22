

<?php
session_start();
require_once 'config.php';

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Fungsi untuk menghapus data
function hapusRekamKesehatan($id) {
    global $conn;
    $sql = "DELETE FROM rekam_kesehatan WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Cek apakah ada parameter id
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Mencoba menghapus data
    if (hapusRekamKesehatan($id)) {
        // Jika berhasil, set session message dan redirect
        $_SESSION['pesan_sukses'] = "Data berhasil dihapus.";
    } else {
        // Jika gagal, set session error
        $_SESSION['pesan_error'] = "Gagal menghapus data. Error: " . $conn->error;
    }
} else {
    // Jika tidak ada id, set session error
    $_SESSION['pesan_error'] = "ID tidak ditemukan.";
}

// Redirect kembali ke halaman utama
header("Location: rekamkesehatan.php");
exit();
?>