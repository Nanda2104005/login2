<?php
// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "user_database");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Mulai session
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Cek apakah ID ada
if (!isset($_GET['id'])) {
    header("Location: edukasi_kesehatan.php?message=error");
    exit();
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil data file sebelum dihapus
$sql = "SELECT gambar, video_file FROM edukasikesehatan WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$file_data = $result->fetch_assoc();

// Hapus data berdasarkan ID dan user_id (kecuali untuk admin)
if ($_SESSION['role'] === 'admin') {
    $sql = "DELETE FROM edukasikesehatan WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
} else {
    $sql = "DELETE FROM edukasikesehatan WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $user_id);
}

if ($stmt->execute()) {
    // Hapus file fisik jika ada
    if ($file_data['gambar'] && file_exists($file_data['gambar'])) {
        unlink($file_data['gambar']);
    }
    if ($file_data['video_file'] && file_exists($file_data['video_file'])) {
        unlink($file_data['video_file']);
    }
    header("Location: edukasikesehatan.php?message=success");
} else {
    header("Location: edukasikesehatan.php?message=error");
}

$stmt->close();
$conn->close();
?>