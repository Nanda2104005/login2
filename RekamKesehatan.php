<?php
session_start();

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "user_database");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Logika Penghapusan Data
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        // 1. Dapatkan informasi dari rekam_kesehatan
        $query = "SELECT nis FROM rekam_kesehatan WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);
        
        if ($data) {
            $nis = $data['nis'];
            
            // 2. Cari data di monitoringkesehatan
            $queryMonitoring = "SELECT id, pengingat_id FROM monitoringkesehatan WHERE nis = ?";
            $stmtMonitoring = mysqli_prepare($conn, $queryMonitoring);
            mysqli_stmt_bind_param($stmtMonitoring, "s", $nis);
            mysqli_stmt_execute($stmtMonitoring);
            $resultMonitoring = mysqli_stmt_get_result($stmtMonitoring);
            $dataMonitoring = mysqli_fetch_assoc($resultMonitoring);
            
            if ($dataMonitoring) {
                $monitoring_id = $dataMonitoring['id'];
                $pengingat_id = $dataMonitoring['pengingat_id'];
                
                // 3. Hapus dari stok_obat
                if ($pengingat_id) {
                    $deleteStok = "DELETE FROM stok_obat WHERE id_pengingat = ?";
                    $stmtStok = mysqli_prepare($conn, $deleteStok);
                    mysqli_stmt_bind_param($stmtStok, "i", $pengingat_id);
                    mysqli_stmt_execute($stmtStok);
                }
                
                // 4. Hapus dari pengingatobat
                if ($pengingat_id) {
                    $deletePengingat = "DELETE FROM pengingatobat WHERE id = ?";
                    $stmtPengingat = mysqli_prepare($conn, $deletePengingat);
                    mysqli_stmt_bind_param($stmtPengingat, "i", $pengingat_id);
                    mysqli_stmt_execute($stmtPengingat);
                }
                
                // 5. Hapus dari monitoringkesehatan
                $deleteMonitoring = "DELETE FROM monitoringkesehatan WHERE id = ?";
                $stmtMonitoring = mysqli_prepare($conn, $deleteMonitoring);
                mysqli_stmt_bind_param($stmtMonitoring, "i", $monitoring_id);
                mysqli_stmt_execute($stmtMonitoring);
            }
            
            // 6. Terakhir, hapus dari rekam_kesehatan
            $deleteRekam = "DELETE FROM rekam_kesehatan WHERE id = ?";
            $stmtRekam = mysqli_prepare($conn, $deleteRekam);
            mysqli_stmt_bind_param($stmtRekam, "i", $id);
            mysqli_stmt_execute($stmtRekam);
            
            // Commit transaksi
            mysqli_commit($conn);
            
            $_SESSION['message'] = "Data berhasil dihapus dari semua tabel terkait";
        } else {
            $_SESSION['error'] = "Data tidak ditemukan";
        }
    } catch (Exception $e) {
        // Rollback jika terjadi error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: rekamkesehatan.php");
    exit();
}

// Logika Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $nis = $_POST['nis'];
    $keluhan = $_POST['keluhan'];
    $diagnosis = $_POST['diagnosis'];
    $pertolongan = $_POST['Pertolongan_Pertama'];
    
    try {
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        // Update rekam_kesehatan
        $stmt = $conn->prepare("UPDATE rekam_kesehatan 
                              SET nama = ?, 
                                  nis = ?, 
                                  keluhan = ?, 
                                  diagnosis = ?, 
                                  Pertolongan_Pertama = ?,
                                  tanggal = CURRENT_TIMESTAMP
                              WHERE id = ?");
        
        $stmt->bind_param("sssssi", 
            $nama, 
            $nis, 
            $keluhan, 
            $diagnosis, 
            $pertolongan,
            $id
        );
        
        if ($stmt->execute()) {
            // Update monitoringkesehatan jika ada
            $stmtMonitoring = $conn->prepare("UPDATE monitoringkesehatan 
                                            SET nama = ?,
                                                keluhan = ?,
                                                diagnosis = ?
                                            WHERE nis = ?");
            
            $stmtMonitoring->bind_param("ssss",
                $nama,
                $keluhan,
                $diagnosis,
                $nis
            );
            
            $stmtMonitoring->execute();
            
            mysqli_commit($conn);
            $_SESSION['message'] = "Data berhasil diupdate";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            throw new Exception("Gagal mengupdate data: " . $stmt->error);
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $pesanError = "Error: " . $e->getMessage();
        $_SESSION['error'] = $pesanError;
    }
}

// Fungsi untuk mengambil semua rekam kesehatan
function ambilSemuaRekamKesehatan() {
    global $conn;
    $result = $conn->query("SELECT *, 
                           DATE_FORMAT(tanggal, '%d/%m/%Y %H:%i') as tanggal_format
                           FROM rekam_kesehatan 
                           ORDER BY tanggal DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Logika Pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchResults = array();

if (!empty($search)) {
    $sql = "SELECT *, 
            DATE_FORMAT(tanggal, '%d/%m/%Y %H:%i') as tanggal_format,
            CASE 
                WHEN nama LIKE ? THEN 1
                WHEN nis LIKE ? THEN 2
                WHEN keluhan LIKE ? THEN 3
                WHEN diagnosis LIKE ? THEN 4
                WHEN Pertolongan_Pertama LIKE ? THEN 5
                ELSE 6
            END as search_relevance
            FROM rekam_kesehatan 
            WHERE nama LIKE ? OR 
                  nis LIKE ? OR 
                  keluhan LIKE ? OR 
                  diagnosis LIKE ? OR 
                  Pertolongan_Pertama LIKE ?
            ORDER BY search_relevance ASC, tanggal DESC";
    
    $search_term = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssss", 
        $search_term, $search_term, $search_term, $search_term, $search_term,
        $search_term, $search_term, $search_term, $search_term, $search_term
    );
    $stmt->execute();
    $daftarRekamKesehatan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($daftarRekamKesehatan as $rekam) {
        $searchResults[] = $rekam['id'];
    }
} else {
    $daftarRekamKesehatan = ambilSemuaRekamKesehatan();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Rekam Kesehatan Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
         :root {
            --primary-color: #1ca883;
            --secondary-color: #f0f9f6;
            --accent-color: #ff6b6b;
            --text-color: #2c3e50;
            --background-color: #ecf0f1;
            --card-hover: #e8f5f1;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--secondary-color);
            color: var(--text-color);
            min-height: 100vh;
            padding-bottom: 60px;
        }

        .container {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 2rem;
            overflow-x: auto;
            position: relative;
        }

        .dashboard-header {
            position: relative;
            color: white;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .content-table {
    width: 100%;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
    border-collapse: collapse;
    border: 3px solid var(--primary-color); /* Perbesar ketebalan border utama */
}

.content-table th {
    background-color: var(--primary-color);
    color: white;
    padding: 1.2rem;
    text-align: left;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9rem;
    position: sticky;
    top: 0;
    z-index: 10;
    border: 2px solid #159f7f; /* Perbesar ketebalan border header */
}

.content-table td {
    padding: 1rem;
    border: 2px solid #e0e0e0; /* Perbesar ketebalan dan ubah warna border sel */
    vertical-align: middle;
    word-wrap: break-word;
}

.content-table tr {
    border-bottom: 2px solid #e0e0e0; /* Perbesar ketebalan border baris */
}

.content-table tr:hover {
    background-color: var(--card-hover);
}

/* Tambahkan border vertikal yang lebih jelas */
.content-table th:not(:last-child),
.content-table td:not(:last-child) {
    border-right: 2px solid #e0e0e0;
}

/* Memperkuat border bawah header */
.content-table thead tr {
    border-bottom: 3px solid var(--primary-color);
}
        .btn-back {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
        }

        .search-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .search-form {
            display: flex;
            gap: 15px;
            margin: 0;
            padding: 0;
            background: none;
            box-shadow: none;
            width: 100%;
            justify-content: center;
        }

        .search-form input[type="text"] {
            width: 300px;
            margin: 0;
            padding: 12px;
            border: 1px solid var(--secondary-color);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-search {
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
        }

        /* Icon button styles */
        .icon-button {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
            color: #666;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
        }

        .icon-button:hover {
            transform: translateY(-2px);
        }

        .icon-button.edit {
            color: var(--primary-color);
        }

        .icon-button.delete {
            color: var(--danger-color);
        }

        .icon-button i {
            font-size: 1.2rem;
        }

        .content-table td:last-child {
            white-space: nowrap;
            width: 100px;
        }

        /* Modifikasi style untuk highlight hasil pencarian */
        .highlight {
            background-color: rgba(28, 168, 131, 0.2) !important;
            animation: none;
        }

        @media screen and (max-width: 1024px) {
            .container {
                padding: 0 1rem;
            }

            .content-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media screen and (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }

            .dashboard-header {
                padding: 0.8rem;
            }

            .content-table th,
            .content-table td {
                padding: 0.8rem;
            }

            .btn-back,
            .btn-search {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }

            .search-form {
                flex-direction: column;
            }

            .search-form input[type="text"] {
                width: 100%;
            }

            .icon-button {
                padding: 6px;
            }

            .icon-button i {
                font-size: 1rem;
            }
        }
        /* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow: auto;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        transform: translate(-50%, -60%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

.modal-content {
    background-color: white;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 2rem;
    border-radius: 20px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    animation: slideIn 0.3s ease;
}

.modal-header {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    margin: -2rem -2rem 2rem -2rem;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
    color: white;
    position: relative;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.close {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    color: white;
    cursor: pointer;
    opacity: 0.8;
    transition: all 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
}

.close:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-50%) rotate(90deg);
}

.form-group {
    margin-bottom: 1.5rem;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-family: inherit;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    background: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
    line-height: 1.5;
}

.form-group input:hover,
.form-group textarea:hover {
    border-color: #ccc;
    background: white;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.btn-modal {
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 500;
    border: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.btn-save {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    color: white;
    min-width: 120px;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
}

.btn-cancel {
    background: #f8f9fa;
    color: #666;
    border: 1px solid #ddd;
}

.btn-cancel:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 10px;
        padding: 1.5rem;
    }

    .modal-header {
        padding: 1rem 1.5rem;
        margin: -1.5rem -1.5rem 1.5rem -1.5rem;
    }

    .btn-modal {
        padding: 0.7rem 1.2rem;
        font-size: 0.9rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }
}

.rekap-button-container {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
}

.btn-rekap {
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, #2c3e50, #3498db);
    color: white;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-rekap:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(44, 62, 80, 0.3);
}

.form-control {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-family: inherit;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
    box-sizing: border-box;
    margin-bottom: 1rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    background: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
}
/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow: auto;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        transform: translate(-50%, -60%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

.modal-content {
    background-color: white;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 2rem;
    border-radius: 20px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    animation: slideIn 0.3s ease;
}

.modal-header {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    margin: -2rem -2rem 2rem -2rem;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
    color: white;
    position: sticky;
    top: -2rem;
    z-index: 1;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.close {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    color: white;
    cursor: pointer;
    opacity: 0.8;
    transition: all 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
}

.close:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-50%) rotate(90deg);
}

/* Form styling */
.modal form {
    padding: 0 1rem;
    box-sizing: border-box;
}

.form-group {
    margin-bottom: 1.5rem;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-family: inherit;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
    box-sizing: border-box;
}

.form-group textarea {
    min-height: 100px;
    max-height: 200px;
    resize: vertical;
    line-height: 1.5;
    overflow-y: auto;
    width: 100% !important; /* Ensure textarea doesn't exceed container */
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    background: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
}

.form-group input:hover,
.form-group textarea:hover {
    border-color: #ccc;
    background: white;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding: 1.5rem 1rem;
    border-top: 1px solid #eee;
    position: sticky;
    bottom: -2rem;
    background: white;
    margin: 2rem -1rem -2rem -1rem;
}

.btn-modal {
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 500;
    border: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-save {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    color: white;
    min-width: 120px;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
}

.btn-cancel {
    background: #f8f9fa;
    color: #666;
    border: 1px solid #ddd;
}

.btn-cancel:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

/* Scrollbar styling */
.modal-content::-webkit-scrollbar {
    width: 8px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #bbb;
}

/* Custom scrollbar for textareas */
.form-group textarea::-webkit-scrollbar {
    width: 6px;
}

.form-group textarea::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.form-group textarea::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.form-group textarea::-webkit-scrollbar-thumb:hover {
    background: #bbb;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .modal-content {
        width: 95%;
        padding: 1.5rem;
    }

    .modal-header {
        padding: 1rem 1.5rem;
        margin: -1.5rem -1.5rem 1.5rem -1.5rem;
    }

    .modal form {
        padding: 0 0.5rem;
    }

    .form-group input,
    .form-group textarea {
        padding: 0.7rem;
        font-size: 0.95rem;
    }

    .btn-modal {
        padding: 0.7rem 1.2rem;
        font-size: 0.9rem;
    }

    .modal-footer {
        padding: 1rem 0.5rem;
        margin: 1.5rem -0.5rem -1.5rem -0.5rem;
    }
}
        .btn-save {
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
        }

        .btn-cancel {
            background-color: #e0e0e0;
            color: var(--text-color);
        }

        .btn-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">Kembali ke Dashboard</a>
        
        <h1 class="dashboard-header">Sistem Rekam Kesehatan Digital</h1>
        
        <form method="GET" action="" class="search-form">
        <input type="text" name="search" placeholder="Cari siswa..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-search">Cari</button>
        <button type="button" onclick="openRekapModal()" class="btn-rekap">
            <i class="fas fa-file-pdf"></i> Cetak PDF
        </button>
    </form>
        </button>
    </div>
</div>

<!-- Modal Rekap -->
<div id="rekapModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Rekap Data Rekam Kesehatan</h2>
            <span class="close" onclick="closeRekapModal()">&times;</span>
        </div>
        <form method="POST" action="generate_pdf.php" target="_blank">
            <div class="form-group">
                <label>Pilih Periode</label>
                <select name="periode" id="periode" class="form-control" onchange="toggleTanggal()">
                    <option value="semua">Semua Data</option>
                    <option value="hari">Per Hari</option>
                    <option value="minggu">Per Minggu</option>
                    <option value="bulan">Per Bulan</option>
                    <option value="tahun">Per Tahun</option>
                    <option value="custom">Periode Tertentu</option>
                </select>
            </div>

            <div id="tanggalContainer" style="display:none;">
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control">
                </div>
                <div class="form-group">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeRekapModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-modal btn-save">
                    <i class="fas fa-file-pdf"></i> Generate PDF
                </button>
            </div>
        </form>
    </div>
</div>

<table class="content-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>NIS</th>
                    <th>Keluhan</th>
                    <th>Diagnosis</th>
                    <th>Tindakan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftarRekamKesehatan as $rekam): ?>
                <tr class="<?php echo (!empty($search) && in_array($rekam['id'], $searchResults)) ? 'highlight' : ''; ?>">
                    <td><?php echo $rekam['tanggal_format']; ?></td>
                    <td><?php echo htmlspecialchars($rekam['nama']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['nis']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['keluhan']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['diagnosis']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['Pertolongan_Pertama']); ?></td>
                    <td>
                        <a href="#" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($rekam)); ?>)" class="icon-button edit" title="Edit">
                            <i class="fas fa-pen"></i>
                        </a>
                        <a href="rekamkesehatan.php?action=delete&id=<?php echo $rekam['id']; ?>" class="icon-button delete" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus data ini?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Edit -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Rekam Kesehatan</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" id="edit_id" name="id">
            <input type="hidden" name="update" value="true">
            
            <div class="form-group">
                <label for="edit_nama">Nama Siswa</label>
                <input type="text" id="edit_nama" name="nama" required 
                       placeholder="Masukkan nama siswa">
            </div>

            <div class="form-group">
                <label for="edit_nis">NIS</label>
                <input type="number" id="edit_nis" name="nis" required
                       placeholder="Masukkan NIS">
            </div>

            <div class="form-group">
                <label for="edit_keluhan">Keluhan</label>
                <textarea id="edit_keluhan" name="keluhan" required
                          placeholder="Deskripsikan keluhan siswa"></textarea>
            </div>

            <div class="form-group">
                <label for="edit_diagnosis">Diagnosis</label>
                <textarea id="edit_diagnosis" name="diagnosis" required
                          placeholder="Masukkan diagnosis"></textarea>
            </div>

            <div class="form-group">
                <label for="edit_pertolongan">Pertolongan Pertama</label>
                <textarea id="edit_pertolongan" name="Pertolongan_Pertama" required
                          placeholder="Deskripsikan pertolongan pertama yang diberikan"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-modal btn-save">
                    <i class="fas fa-check"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
    <script>
        // Fungsi untuk membuka modal edit
        function openEditModal(data) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama;
            document.getElementById('edit_nis').value = data.nis;
            document.getElementById('edit_keluhan').value = data.keluhan;
            document.getElementById('edit_diagnosis').value = data.diagnosis;
            document.getElementById('edit_pertolongan').value = data.Pertolongan_Pertama;
        }

        // Fungsi untuk menutup modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Menutup modal ketika mengklik di luar modal
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }

        function openRekapModal() {
    document.getElementById('rekapModal').style.display = 'block';
}

function closeRekapModal() {
    document.getElementById('rekapModal').style.display = 'none';
}

function toggleTanggal() {
    const periode = document.getElementById('periode').value;
    const tanggalContainer = document.getElementById('tanggalContainer');
    
    if (periode === 'custom') {
        tanggalContainer.style.display = 'block';
    } else {
        tanggalContainer.style.display = 'none';
    }
}

window.onclick = function(event) {
    const rekapModal = document.getElementById('rekapModal');
    const editModal = document.getElementById('editModal');
    
    if (event.target == rekapModal) {
        closeRekapModal();
    }
    if (event.target == editModal) {
        closeEditModal();
    }
}
    </script>
</body>
</html>