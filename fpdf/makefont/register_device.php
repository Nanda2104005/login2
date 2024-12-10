<?php
header('Content-Type: application/json');
session_start();

// Koneksi database
$conn = mysqli_connect("localhost", "root", "", "user_database");
if (!$conn) {
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal']));
}

// Terima data JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

try {
    // Validasi data yang diperlukan
    if (!isset($data['device_token']) || !isset($data['nis'])) {
        throw new Exception('Data tidak lengkap');
    }

    // Cek apakah token sudah ada
    $stmt = mysqli_prepare($conn, "SELECT id FROM penghubung WHERE patient_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $data['nis']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Update token yang sudah ada
        $stmt = mysqli_prepare($conn, "UPDATE penghubung SET device_token = ? WHERE patient_id = ?");
        mysqli_stmt_bind_param($stmt, "ss", $data['device_token'], $data['nis']);
    } else {
        // Insert token baru
        $stmt = mysqli_prepare($conn, "INSERT INTO penghubung (patient_id, device_token) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $data['nis'], $data['device_token']);
    }

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Token berhasil didaftarkan']);
    } else {
        throw new Exception('Gagal menyimpan token');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>