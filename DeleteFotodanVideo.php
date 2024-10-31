<?php
// delete.php
session_start();

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "user_database");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Konversi ke integer untuk keamanan
    
    try {
        // Mulai transaksi
        $conn->begin_transaction();
        
        // Ambil informasi file sebelum menghapus data
        $stmt = $conn->prepare("SELECT gambar, video_file FROM edukasi_kesehatan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Hapus file gambar jika ada
            if (!empty($row['gambar']) && file_exists($row['gambar'])) {
                if (!unlink($row['gambar'])) {
                    throw new Exception("Gagal menghapus file gambar");
                }
            }
            
            // Hapus file video jika ada
            if (!empty($row['video_file']) && file_exists($row['video_file'])) {
                if (!unlink($row['video_file'])) {
                    throw new Exception("Gagal menghapus file video");
                }
            }
            
            // Hapus data dari database
            $stmt = $conn->prepare("DELETE FROM edukasi_kesehatan WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Gagal menghapus data dari database");
            }
            
            // Commit transaksi jika semua berhasil
            $conn->commit();
            
            // Set pesan sukses
            header("Location: savefilefotodanvideo.php?message=success");
            exit();
        } else {
            throw new Exception("Data tidak ditemukan");
        }
        
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollback();
        
        // Log error (opsional)
        error_log("Error deleting data: " . $e->getMessage());
        
        // Set pesan error
        header("Location: savefilefotodanvideo.php?message=error");
        exit();
    }
} else {
    // Jika tidak ada ID yang diberikan
    header("Location: vsavefilefotodanvideoiew.php?message=error");
    exit();
}

// Tutup koneksi
$stmt->close();
$conn->close();
?>