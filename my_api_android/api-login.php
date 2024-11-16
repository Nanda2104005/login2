<?php
session_start(); // Memulai sesi

header('Content-Type: application/json'); // Pastikan header JSON

include 'koneksi.php';

if (isset($_GET['username']) && isset($_GET['password'])) {
    $username = $_GET['username'];
    $password = $_GET['password'];

    // Query untuk memeriksa login
    $query = "SELECT id FROM users WHERE username = ? AND password = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Simpan user_id ke dalam sesi
        $_SESSION['user_id'] = $user['id'];

        // Kirimkan session_id ke aplikasi Android
        echo json_encode([
            "status" => "success",
            "message" => "Selamat datang",
            "user_id" => $user['id'],
            "session_id" => session_id() // Mengirimkan session ID
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Username atau password salah"
        ]);
    }

    $stmt->close();
    $koneksi->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid parameters"
    ]);
}
?>
