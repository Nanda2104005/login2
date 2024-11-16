<?php
session_start(); // Memulai sesi untuk memastikan user_id tersedia

header('Content-Type: application/json'); // Pastikan header JSON

include 'koneksi.php'; // Pastikan ini mengarah ke koneksi database user_database

// Memastikan user_id tersedia dalam sesi
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "User not authenticated."
    ]);
    exit;
}

$user_id = $_SESSION['user_id']; // Mendapatkan user_id dari sesi
$tanggal = date('Y-m-d H:i:s'); // Mengisi tanggal otomatis dengan format YYYY-MM-DD HH:MM:SS

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = isset($_POST['nama']) ? $_POST['nama'] : null;
    $nis = isset($_POST['nis']) ? $_POST['nis'] : null;
    $kelas = isset($_POST['kelas']) ? $_POST['kelas'] : null;
    $keluhan = isset($_POST['keluhan']) ? $_POST['keluhan'] : null;
    $diagnosis = isset($_POST['diagnosis']) ? $_POST['diagnosis'] : null;
    $pertolongan_pertama = isset($_POST['pertolongan_pertama']) ? $_POST['pertolongan_pertama'] : null;
    $suhu = isset($_POST['suhu']) ? $_POST['suhu'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : null;

    // Menentukan severity berdasarkan suhu
    $severity = "Low"; // Default severity
    if ($suhu >= 38) {
        $severity = "High";
    } elseif ($suhu >= 37.5) {
        $severity = "Medium";
    } elseif ($suhu < 36.0) {
        $severity = "Low";
    } else {
        $severity = "Normal";
    }

    // Masukkan data ke tabel pengingatobat dan dapatkan pengingat_id
    $condition_name = $diagnosis; // Menggunakan diagnosis sebagai condition_name
    $patient_id = $nis; // Misalnya menggunakan NIS sebagai patient_id

    $insertPengingatQuery = "INSERT INTO pengingatobat (patient_id, condition_name, severity, timestamp, user_id) 
                             VALUES ('$patient_id', '$condition_name', '$severity', '$tanggal', '$user_id')";

    if ($koneksi->query($insertPengingatQuery) === TRUE) {
        $pengingat_id = $koneksi->insert_id; // Mendapatkan pengingat_id dari entri terbaru
    } else {
        error_log("Database error in pengingatobat: " . $koneksi->error);
        echo json_encode(["status" => "error", "message" => "Database error in pengingatobat: " . $koneksi->error]);
        exit;
    }

    // Log untuk debugging
    error_log("Auto-assigned pengingat_id: " . $pengingat_id);
    error_log("Auto-assigned tanggal: " . $tanggal);
    error_log("Auto-assigned user_id: " . $user_id);

    // Masukkan data ke tabel rekam_kesehatan tanpa pengingat_id
    $insertRekamKesehatanQuery = "INSERT INTO rekam_kesehatan (tanggal, nama, nis, keluhan, diagnosis, pertolongan_pertama, user_id) 
                                  VALUES ('$tanggal', '$nama', '$nis', '$keluhan', '$diagnosis', '$pertolongan_pertama', '$user_id')";

    if ($koneksi->query($insertRekamKesehatanQuery) !== TRUE) {
        error_log("Database error in rekam_kesehatan: " . $koneksi->error);
        echo json_encode(["status" => "error", "message" => "Database error in rekam_kesehatan: " . $koneksi->error]);
        exit;
    }

    // Masukkan data ke tabel monitoringkesehatan dengan pengingat_id
    $insertMonitoringKesehatanQuery = "INSERT INTO monitoringkesehatan (tanggal, nama, nis, kelas, keluhan, diagnosis, pertolongan_pertama, suhu, status, pengingat_id, user_id) 
                                       VALUES ('$tanggal', '$nama', '$nis', '$kelas', '$keluhan', '$diagnosis', '$pertolongan_pertama', '$suhu', '$status', '$pengingat_id', '$user_id')";

    if ($koneksi->query($insertMonitoringKesehatanQuery) === TRUE) {
        $monitoring_id = $koneksi->insert_id; // Mendapatkan monitoring_id setelah insert

        // Update pengingatobat dengan monitoring_id yang baru
        $updatePengingatQuery = "UPDATE pengingatobat SET monitoring_id = '$monitoring_id' WHERE id = '$pengingat_id'";

        if ($koneksi->query($updatePengingatQuery) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Record created successfully in all three tables, and monitoring_id updated in pengingatobat"]);
        } else {
            error_log("Database error in updating pengingatobat: " . $koneksi->error);
            echo json_encode(["status" => "error", "message" => "Error updating monitoring_id in pengingatobat"]);
        }
    } else {
        error_log("Database error in monitoringkesehatan: " . $koneksi->error);
        echo json_encode(["status" => "error", "message" => "Database error in monitoringkesehatan"]);
    }
} else {
    error_log("Invalid request method");
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
