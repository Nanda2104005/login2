<?php

header('Content-Type: application/json');

// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "", "user_databasee");

// Periksa koneksi
if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Tambahkan log ini untuk memastikan bahwa parameter 'id' diterima
file_put_contents('debug.log', 'Received ID: ' . ($_GET['id'] ?? 'None') . "\n", FILE_APPEND);

// Periksa apakah 'id' tersedia dan tidak kosong
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(["error" => "No ID provided"]);
    exit;
}

$id = intval($_GET['id']); // Pastikan 'id' adalah integer

// Log untuk memastikan ID diterima di server
error_log("ID received: " . $id);

// Siapkan dan eksekusi query
$query = "SELECT * FROM pengingatobat WHERE id = $id";
$result = mysqli_query($conn, $query);

// Periksa hasil query
if ($data = mysqli_fetch_assoc($result)) {
    echo json_encode($data);
} else {
    echo json_encode(["error" => "Alert not found"]);
}

// Tutup koneksi
mysqli_close($conn);
?>
