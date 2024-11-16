<?php

include 'koneksi.php'; // Pastikan file koneksi di-include dan menggunakan $koneksi

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Pastikan $koneksi terdefinisi sebelum menggunakan
    if (isset($koneksi)) {
        // Query untuk mendapatkan data user berdasarkan user_id
        $query = "SELECT username, email FROM users WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo json_encode([
                "status" => "success",
                "data" => $user
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "User not found"
            ]);
        }

        $stmt->close();
        $koneksi->close();
    } else {
        // Jika koneksi gagal
        echo json_encode([
            "status" => "error",
            "message" => "Database connection failed"
        ]);
    }
} else {
    // Jika parameter user_id tidak ada
    echo json_encode([
        "status" => "error",
        "message" => "Invalid parameters"
    ]);
}

?>
