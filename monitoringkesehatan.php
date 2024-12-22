<?php
session_start();
require_once 'config.php';

// Fungsi untuk cek status login
function checkLoginStatus() {
    if (!isset($_SESSION['username'])) {
        session_unset();
        session_destroy();
        if (isset($_COOKIE['username'])) {
            setcookie('username', '', time() - 3600, '/');
        }
        header("Location: login.php?message=Silakan login untuk mengakses halaman ini");
        exit();
    }
}

checkLoginStatus();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fungsi untuk mengecek apakah obat expired
function isObatExpired($conn, $nama_obat) {
    $stmt = mysqli_prepare($conn, "SELECT tanggal_kadaluarsa FROM stok_obat WHERE nama = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $nama_obat);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $expiry_date = new DateTime($row['tanggal_kadaluarsa']);
        $today = new DateTime();
        return $expiry_date < $today;
    }
    return false;
}

// Fungsi untuk mendapatkan user_id berdasarkan NIS
function getUserIdByNIS($conn, $nis) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE nis = ?");
    $stmt->bind_param("s", $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['id'];
    }
    return null;
}

// Function untuk mendapatkan data siswa
function getStudentData($conn, $search, $type) {
    try {
        if ($type === 'nis') {
            $stmt = $conn->prepare("SELECT nama_lengkap as nama, nis FROM users WHERE nis = ?");
            $stmt->bind_param("s", $search);
        } else {
            $search = "%$search%";
            $stmt = $conn->prepare("SELECT nama_lengkap as nama, nis FROM users WHERE nama_lengkap LIKE ?");
            $stmt->bind_param("s", $search);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode($data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(null);
        }
        $stmt->close();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'getStudent') {
    if (isset($_GET['nis'])) {
        getStudentData($conn, $_GET['nis'], 'nis');
        exit;
    } elseif (isset($_GET['nama'])) {
        getStudentData($conn, $_GET['nama'], 'nama');
        exit;
    }
}

// Mendapatkan daftar obat expired
$obat_expired = [];
try {
    $today = date('Y-m-d');
    $stmt = mysqli_prepare($conn, "SELECT nama FROM stok_obat WHERE tanggal_kadaluarsa < ?");
    mysqli_stmt_bind_param($stmt, "s", $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)) {
        $obat_expired[] = $row['nama'];
    }
} catch (Exception $e) {
    // Handle error silently
}

$pesanSukses = "";
$pesanError = "";

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Validate and sanitize input
        $nama = isset($_POST['nama']) ? htmlspecialchars(trim($_POST['nama'])) : '';
        $nis = isset($_POST['nis']) ? htmlspecialchars(trim($_POST['nis'])) : '';
        $kelas = isset($_POST['kelas']) ? htmlspecialchars(trim($_POST['kelas'])) : '';
        $suhu = isset($_POST['suhu']) ? floatval($_POST['suhu']) : 0.0;
        $status = isset($_POST['status']) ? htmlspecialchars(trim($_POST['status'])) : '';
        $keluhan = isset($_POST['keluhan']) ? htmlspecialchars(trim($_POST['keluhan'])) : '';
        $diagnosis = isset($_POST['diagnosis']) ? htmlspecialchars(trim($_POST['diagnosis'])) : '';
        $pertolongan = isset($_POST['pertolongan']) ? htmlspecialchars(trim($_POST['pertolongan'])) : '';
        $nama_obat = isset($_POST['nama_obat']) ? htmlspecialchars(trim($_POST['nama_obat'])) : '';
        $jumlah_obat = isset($_POST['jumlah_obat']) ? intval($_POST['jumlah_obat']) : 0;

        // Get user_id
        $student_user_id = getUserIdByNIS($conn, $nis);
        if (!$student_user_id) {
            throw new Exception("Siswa dengan NIS tersebut tidak ditemukan.");
        }

        // Validate required fields
        if (empty($nama) || empty($nis) || empty($kelas) || empty($suhu) || empty($status)) {
            throw new Exception("Mohon lengkapi semua data yang diperlukan.");
        }

        // Check if medicine is needed and validate
        if ($status === 'Sakit' && !empty($nama_obat) && $jumlah_obat > 0) {
            if (isObatExpired($conn, $nama_obat)) {
                throw new Exception("Obat '$nama_obat' sudah expired! Silakan pilih obat lain.");
            }

            // Check medicine stock
            $stmt = mysqli_prepare($conn, "SELECT jumlah FROM stok_obat WHERE nama = ? FOR UPDATE");
            mysqli_stmt_bind_param($stmt, "s", $nama_obat);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stok_current = $row['jumlah'];
                if ($stok_current < $jumlah_obat) {
                    throw new Exception("Stok obat tidak mencukupi! Stok tersedia: " . $stok_current);
                }

                // Update medicine stock
                $stmt = mysqli_prepare($conn, "UPDATE stok_obat SET jumlah = jumlah - ? WHERE nama = ?");
                mysqli_stmt_bind_param($stmt, "is", $jumlah_obat, $nama_obat);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Gagal memperbarui stok obat");
                }
            } else {
                throw new Exception("Obat '$nama_obat' tidak ditemukan dalam stok!");
            }
        }

        // Insert into pengingatobat
        $stmt = mysqli_prepare($conn, "INSERT INTO pengingatobat 
            (patient_id, condition_name, severity, user_id, nama_obat, jumlah) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        $patient_id = $nis;
        $condition_name = $status == 'Sakit' ? $diagnosis : 'Sehat';
        $severity = $status == 'Sakit' ? 'Medium' : 'Low';
        
        mysqli_stmt_bind_param($stmt, "sssisi", 
            $patient_id, $condition_name, $severity, $student_user_id, $nama_obat, $jumlah_obat
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error executing pengingat statement: " . mysqli_stmt_error($stmt));
        }
        
        $pengingat_id = mysqli_insert_id($conn);

        // Insert into monitoringkesehatan
        $stmt = mysqli_prepare($conn, "INSERT INTO monitoringkesehatan 
            (nama, nis, keluhan, diagnosis, kelas, suhu, status, pertolongan_pertama, pengingat_id, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param($stmt, "sssssdssii", 
            $nama, $nis, $keluhan, $diagnosis, $kelas, $suhu, $status, $pertolongan, $pengingat_id, $student_user_id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            // Check for duplicate entry
            if (mysqli_errno($conn) === 1062) {
                throw new Exception("Data untuk NIS ini sudah ada dalam sistem.");
            }
            throw new Exception("Error executing monitoring statement: " . mysqli_stmt_error($stmt));
        }

        // Insert into rekam_kesehatan
        $stmt = mysqli_prepare($conn, "INSERT INTO rekam_kesehatan 
            (nama, nis, keluhan, diagnosis, Pertolongan_Pertama, user_id) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        mysqli_stmt_bind_param($stmt, "sssssi", 
            $nama, $nis, $keluhan, $diagnosis, $pertolongan, $student_user_id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error executing rekam statement: " . mysqli_stmt_error($stmt));
        }

        // Commit transaction
        mysqli_commit($conn);
        $pesanSukses = "Data berhasil ditambahkan!";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $pesanError = $e->getMessage();
    }
}

// Query untuk mendapatkan data monitoring dengan pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$daftarRekamKesehatan = array();
$searchResults = array();

try {
    if (!empty($search)) {
        $query = "SELECT m.*, p.nama_obat, p.jumlah as jumlah_obat 
                 FROM monitoringkesehatan m 
                 LEFT JOIN pengingatobat p ON m.pengingat_id = p.id
                 WHERE m.nama LIKE ? OR 
                       m.nis LIKE ? OR 
                       m.kelas LIKE ? OR 
                       m.status LIKE ? OR
                       m.diagnosis LIKE ? OR
                       m.keluhan LIKE ?
                 ORDER BY m.tanggal DESC";
                 
        $search_term = "%{$search}%";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssss", 
            $search_term, $search_term, $search_term, $search_term, $search_term, $search_term
        );
    } else {
        $query = "SELECT m.*, p.nama_obat, p.jumlah as jumlah_obat 
                 FROM monitoringkesehatan m 
                 LEFT JOIN pengingatobat p ON m.pengingat_id = p.id
                 ORDER BY m.tanggal DESC";
        $stmt = mysqli_prepare($conn, $query);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)) {
        $daftarRekamKesehatan[] = $row;
        if (!empty($search)) {
            $searchResults[] = $row['id'];
        }
    }
} catch (Exception $e) {
    $pesanError = "Error mengambil data: " . $e->getMessage();
}

// Get list of available medicines
$obat_list = [];
try {
    $stmt = mysqli_prepare($conn, "SELECT nama, tanggal_kadaluarsa FROM stok_obat WHERE jumlah > 0");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)) {
        $obat_list[] = $row;
    }
} catch (Exception $e) {
    $pesanError = "Error mengambil data obat: " . $e->getMessage();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Kesehatan Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
    --primary-color: #1ca883;
    --primary-dark: #159f7f;
    --primary-light: #e8f5f1;
    --secondary-color: #f0f9f6;
    --accent-color: #ff6b6b;
    --text-color: #2c3e50;
    --text-light: #6c757d;
    --white: #ffffff;
    --error: #dc3545;
    --success: #28a745;
    --warning: #ffc107;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: linear-gradient(135deg, #f8fafb 0%, var(--primary-light) 100%);
    color: var(--text-color);
    line-height: 1.6;
    min-height: 100vh;
}

.container {
    width: 95%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.5rem;
}

/* Header Styles */
header {
    grid-column: span 12;
    background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    padding: 1rem;
    border-radius: 12px;
    box-shadow: var(--box-shadow);
    margin-bottom: 1rem;
}

header h1 {
    color: var(--white);
    font-size: 1.8rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

/* Search Container */
.search-container {
    grid-column: span 12;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: var(--box-shadow);
    margin-bottom: 2rem;
}

.search-form {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.search-input-container {
    position: relative;
    flex: 1;
}

.search-input {
    width: 100%;
    padding: 0.8rem 1rem;
    padding-right: 2.5rem;
    border: 2px solid var(--primary-light);
    border-radius: 8px;
    font-family: inherit;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
    outline: none;
}

.clear-search {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-light);
    cursor: pointer;
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.3s ease;
}

.search-buttons {
    display: flex;
    gap: 0.5rem;
}

.search-button,
.clear-button {
    padding: 0.8rem 1.5rem;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.search-button {
    background: linear-gradient(45deg, var(--primary-color), var(--primary-dark));
}

.clear-button {
    background: var(--text-light);
    text-decoration: none;
}

/* Form Container */
.form-container {
    grid-column: span 4;
    background: var(--white);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
    position: sticky;
    top: 1rem;
    height: fit-content;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-color);
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid var(--primary-light);
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
    outline: none;
}

/* Table Styles */
.table-container {
    grid-column: span 8;
    background: white;
    border-radius: 12px;
    box-shadow: var(--box-shadow);
    max-height: 70vh; /* Tinggi maksimum menggunakan viewport height */
    overflow: auto;
    position: relative;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 3px solid var(--primary-color);
    min-width: 1200px; /* Minimal lebar tabel */
}

thead {
    position: sticky;
    top: 0;
    z-index: 1;
}

th {
    background: var(--primary-color);
    color: var(--white);
    padding: 1rem;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 600;
    white-space: nowrap;
    border-bottom: 2px solid var(--primary-dark);
}

td {
    padding: 1rem;
    border-bottom: 2px solid #e0e0e0;
    font-size: 0.9rem;
    vertical-align: middle;
    background: var(--white);
}

tr:nth-child(even) td {
    background-color: var(--primary-light);
}

/* Highlight untuk hasil pencarian */
tr.highlight td {
    background-color: rgba(28, 168, 131, 0.2) !important;
}

/* Hover tetap mempertahankan highlight */
tr.highlight:hover td {
    background-color: rgba(28, 168, 131, 0.3) !important;
}

/* Hover untuk baris normal */
tr:not(.highlight):hover td {
    background-color: rgba(28, 168, 131, 0.1);
}

/* Alert Styles */
.alert {
    grid-column: span 12;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: slideIn 0.3s ease;
}

.alert-success {
    background-color: var(--success);
    color: white;
}

.alert-danger {
    background-color: var(--error);
    color: white;
}

/* Button Styles */
.btn-submit {
    width: 100%;
    padding: 0.8rem;
    background: linear-gradient(45deg, var(--primary-color), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(28, 168, 131, 0.2);
}

.btn-back {
    position: fixed;
    top: 20px;
    left: 20px;
    background: var(--primary-color);
    color: white;
    padding: 0.8rem 1.2rem;
    border-radius: 25px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    z-index: 1000;
}

.btn-back:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

/* Status Badge Styles */
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.sakit {
    background-color: var(--error);
    color: white;
}

.status-badge.sehat {
    background-color: var(--success);
    color: white;
}

/* Animations */
@keyframes slideIn {
    from {
        transform: translateY(-10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--primary-light);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .form-container {
        grid-column: span 5;
    }
    
    .table-container {
        grid-column: span 7;
    }
}

@media (max-width: 992px) {
    .search-form {
        flex-direction: column;
    }

    .search-buttons {
        width: 100%;
        justify-content: stretch;
    }

    .search-button,
    .clear-button {
        flex: 1;
        justify-content: center;
    }

    .form-container,
    .table-container {
        grid-column: span 12;
    }
    
    .form-container {
        position: static;
    }

    .btn-back {
        position: static;
        margin-bottom: 1rem;
    }
}

@media (max-width: 768px) {
    header h1 {
        font-size: 1.5rem;
    }

    .table-container {
        max-height: 60vh;
    }
    
    td, th {
        padding: 0.75rem;
        font-size: 0.8rem;
    }
}
    </style>
</head>
<body>
    
    <a href="dashboard.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
    </a>
    
    <div class="container">
        <header>
            <h1><i class="fas fa-heartbeat"></i> Monitoring Kesehatan Siswa</h1>
        </header>

        <?php if ($pesanSukses): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($pesanSukses); ?>
            </div>
        <?php endif; ?>

        <?php if ($pesanError): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($pesanError); ?>
            </div>
        <?php endif; ?>

        <div class="search-container">
    <form method="GET" action="" class="search-form">
        <div class="search-input-container">
            <input type="text" name="search" class="search-input" 
                   placeholder="Cari berdasarkan nama, NIS, kelas, atau diagnosis..."
                   value="<?php echo htmlspecialchars($search); ?>">
            <?php if (!empty($search)): ?>
            <button type="button" class="clear-search" onclick="clearSearch()">
                <i class="fas fa-times"></i>
            </button>
            <?php endif; ?>
        </div>
        <div class="search-buttons">
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> Cari
            </button>
            <a href="?" class="clear-button">
                <i class="fas fa-sync-alt"></i> Clear
            </a>
        </div>
    </form>
</div>

        <div class="form-container">
            <h2>Tambah Data Siswa</h2>
            <form method="POST" action="" id="monitoringForm">
                <div class="form-group">
                    <label for="nis">NIS:</label>
                    <input type="text" name="nis" id="nis" required>
                </div>
                <div class="form-group">
                    <label for="nama">Nama:</label>
                    <input type="text" name="nama" id="nama" required>
                </div>
                <div class="form-group">
                    <label for="kelas">Kelas:</label>
                    <input type="text" name="kelas" id="kelas" required>
                </div>
                <div class="form-group">
                    <label for="suhu">Suhu (°C):</label>
                    <input type="number" step="0.1" name="suhu" id="suhu" required>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" required>
                        <option value="Sehat">Sehat</option>
                        <option value="Sakit">Sakit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="keluhan">Keluhan:</label>
                    <textarea name="keluhan" id="keluhan" required></textarea>
                </div>
                <div class="form-group">
                    <label for="diagnosis">Diagnosis:</label>
                    <textarea name="diagnosis" id="diagnosis" required></textarea>
                </div>
                <div class="form-group">
                    <label for="pertolongan">Tindakan:</label>
                    <textarea name="pertolongan" id="pertolongan" rows="3"></textarea>
                </div>

                <div class="form-group obat-fields" style="display: none;">
                    <label for="nama_obat">Nama Obat:</label>
                    <select name="nama_obat" id="nama_obat">
                        <option value="">Pilih Obat</option>
                        <?php foreach($obat_list as $obat): 
                            $is_expired = (new DateTime($obat['tanggal_kadaluarsa'])) < (new DateTime());
                        ?>
                            <option value="<?php echo htmlspecialchars($obat['nama']); ?>"
                                    <?php echo $is_expired ? 'disabled' : ''; ?>
                                    class="<?php echo $is_expired ? 'expired-option' : ''; ?>">
                                <?php echo htmlspecialchars($obat['nama']) . 
                                      ($is_expired ? ' (Expired)' : ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group obat-fields" style="display: none;">
                    <label for="jumlah_obat">Jumlah Obat:</label>
                    <input type="number" name="jumlah_obat" id="jumlah_obat" min="1">
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus-circle"></i>
                    Tambah Data
                </button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Suhu (°C)</th>
                        <th>Status</th>
                        <th>Keluhan</th>
                        <th>Diagnosis</th>
                        <th>Tindakan</th>
                        <th>Obat</th>
                        <th>Jumlah Obat</th>
                    </tr>
                </thead>
                <tbody id="siswaTableBody">
                    <?php foreach ($daftarRekamKesehatan as $rekam): ?>
                    <tr class="<?php echo (!empty($search) && in_array($rekam['id'], $searchResults)) ? 'highlight' : ''; ?>">
                        <td><?php echo htmlspecialchars($rekam['id']); ?></td>
                        <td><?php echo htmlspecialchars($rekam['nama']); ?></td>
                        <td><?php echo htmlspecialchars($rekam['nis']); ?></td>
                        <td><?php echo htmlspecialchars($rekam['kelas']); ?></td>
                        <td><?php echo htmlspecialchars($rekam['suhu']); ?>°C</td>
                        <td>
                            <span class="status-badge <?php echo strtolower($rekam['status']); ?>">
                                <?php echo htmlspecialchars($rekam['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($rekam['keluhan']); ?></td>
                        <td><?php echo htmlspecialchars($rekam['diagnosis']); ?></td>
                        <td><?php echo htmlspecialchars($rekam['pertolongan_pertama']); ?></td>
                        <td>
                            <?php 
                            if ($rekam['status'] === 'Sakit') {
                                echo htmlspecialchars($rekam['nama_obat'] ?: '-');
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($rekam['status'] === 'Sakit' && $rekam['nama_obat']) {
                                echo htmlspecialchars($rekam['jumlah_obat'] ?: '0') . ' pcs';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script id="obatExpiredData" type="application/json">
    <?php echo json_encode($obat_expired); ?>
</script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi elemen-elemen form
    const nisInput = document.getElementById('nis');
    const namaInput = document.getElementById('nama');
    const statusSelect = document.getElementById('status');
    const obatFields = document.querySelectorAll('.obat-fields');
    const namaObatSelect = document.getElementById('nama_obat');
    const monitoringForm = document.getElementById('monitoringForm');
    const siswaTableBody = document.getElementById('siswaTableBody');
    let isUpdating = false;
    let initialScrollPosition = 0;

    // Data obat expired dari PHP
    const obatExpired = JSON.parse(document.getElementById('obatExpiredData').textContent);

    // Fungsi untuk toggle fields obat
    function toggleObatFields(status) {
        obatFields.forEach(field => {
            field.style.display = status === 'Sakit' ? 'block' : 'none';
        });
        
        if (status !== 'Sakit') {
            namaObatSelect.value = '';
            document.getElementById('jumlah_obat').value = '';
        }
    }

    // Event listener untuk status change
    statusSelect.addEventListener('change', function() {
        toggleObatFields(this.value);
    });

    // Fungsi untuk escape HTML
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Fungsi untuk menambah baris baru ke tabel
    function addNewRowToTable(data) {
        const newRow = document.createElement('tr');
        
        // Cari ID terbesar
        const rows = siswaTableBody.getElementsByTagName('tr');
        let maxId = 0;
        for (let row of rows) {
            const id = parseInt(row.cells[0].textContent);
            if (id > maxId) maxId = id;
        }

        // Template baris baru
        newRow.innerHTML = `
            <td>${maxId + 1}</td>
            <td>${escapeHtml(data.get('nama'))}</td>
            <td>${escapeHtml(data.get('nis'))}</td>
            <td>${escapeHtml(data.get('kelas'))}</td>
            <td>${escapeHtml(data.get('suhu'))}°C</td>
            <td>
                <span class="status-badge ${data.get('status').toLowerCase()}">
                    ${escapeHtml(data.get('status'))}
                </span>
            </td>
            <td>${escapeHtml(data.get('keluhan'))}</td>
            <td>${escapeHtml(data.get('diagnosis'))}</td>
            <td>${escapeHtml(data.get('pertolongan'))}</td>
            <td>${data.get('status') === 'Sakit' ? escapeHtml(data.get('nama_obat') || '-') : '-'}</td>
            <td>${data.get('status') === 'Sakit' && data.get('nama_obat') ? 
                escapeHtml(data.get('jumlah_obat')) + ' pcs' : '-'}</td>
        `;
        
        // Insert baris baru di awal tabel
        if (siswaTableBody.firstChild) {
            siswaTableBody.insertBefore(newRow, siswaTableBody.firstChild);
        } else {
            siswaTableBody.appendChild(newRow);
        }
    }

    // Fungsi untuk menampilkan alert
    function showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
        
        const container = document.querySelector('.container');
        const existingAlert = container.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s ease-out';
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    }

    // Fungsi untuk validasi form
    function validateForm(formData) {
        const suhu = parseFloat(formData.get('suhu'));
        if (suhu < 35 || suhu > 42) {
            throw new Error('Suhu harus berada dalam rentang 35°C - 42°C');
        }

        const nis = formData.get('nis');
        if (!/^\d+$/.test(nis)) {
            throw new Error('NIS harus berupa angka');
        }

        const kelas = formData.get('kelas');
        if (kelas.length < 2) {
            throw new Error('Kelas harus diisi dengan benar (minimal 2 karakter)');
        }

        if (formData.get('status') === 'Sakit') {
            const selectedObat = formData.get('nama_obat');
            const jumlahObat = formData.get('jumlah_obat');
            
            if (!selectedObat) {
                throw new Error('Pilih obat yang akan diberikan');
            }

            if (obatExpired.includes(selectedObat)) {
                throw new Error(`Obat "${selectedObat}" sudah expired! Silakan pilih obat lain.`);
            }

            if (!jumlahObat || jumlahObat < 1) {
                throw new Error('Masukkan jumlah obat yang valid');
            }
        }
    }

    // Handler form submission dengan perbaikan scroll
    monitoringForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Simpan posisi scroll awal
        initialScrollPosition = window.scrollY;
        
        const formData = new FormData(this);
        
        try {
            validateForm(formData);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                const jsonResponse = await response.json();
                if (jsonResponse.error) {
                    throw new Error(jsonResponse.error);
                }
            }
            
            addNewRowToTable(formData);
            this.reset();
            toggleObatFields('Sehat');
            
            // Scroll ke posisi awal dengan smooth behavior
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            
            showAlert('Data berhasil ditambahkan!', 'success');
            
        } catch (error) {
            showAlert(error.message, 'danger');
            // Kembalikan ke posisi scroll sebelumnya jika terjadi error
            window.scrollTo({
                top: initialScrollPosition,
                behavior: 'smooth'
            });
        }
    });

    // Fungsi pencarian siswa
    async function searchStudent(searchTerm, searchType) {
        if (isUpdating || !searchTerm) return;
        
        try {
            isUpdating = true;
            const response = await fetch(`?action=getStudent&${searchType}=${encodeURIComponent(searchTerm)}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            
            if (data && (data.nama || data.nis)) {
                if (searchType === 'nis') {
                    namaInput.value = data.nama || '';
                } else if (searchType === 'nama') {
                    nisInput.value = data.nis || '';
                }
            }
        } catch (error) {
            console.error('Search error:', error);
        } finally {
            isUpdating = false;
        }
    }

    // Fungsi debounce
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    const debouncedSearch = debounce(searchStudent, 300);

    // Event listeners untuk pencarian siswa
    if (nisInput) {
        nisInput.addEventListener('input', function(e) {
            const value = e.target.value.trim();
            if (value) debouncedSearch(value, 'nis');
        });
    }

    if (namaInput) {
        namaInput.addEventListener('input', function(e) {
            const value = e.target.value.trim();
            if (value.length >= 3) debouncedSearch(value, 'nama');
        });
    }

    // Fungsi untuk clear search
    function clearSearch() {
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.value = '';
            const highlightedRows = document.querySelectorAll('.highlight');
            highlightedRows.forEach(row => row.classList.remove('highlight'));
        }
    }

    // Event listener untuk form pencarian
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = document.querySelector('.search-input');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                clearSearch();
            }
        });
    }

    // Event listener untuk tombol clear search
    const clearSearchBtn = document.querySelector('.clear-search');
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearSearch();
            window.location.href = window.location.pathname;
        });
    }

    // Event listener untuk clear button
    const clearButton = document.querySelector('.clear-button');
    if (clearButton) {
        clearButton.addEventListener('click', function(e) {
            clearSearch();
        });
    }

    // Event listener untuk search input
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const clearButton = document.querySelector('.clear-search');
            if (clearButton) {
                clearButton.style.display = this.value ? 'flex' : 'none';
            }
        });
    }

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Init status fields
    toggleObatFields(statusSelect.value);
});
</script>
</body>
</html>