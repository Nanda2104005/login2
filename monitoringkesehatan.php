<?php
session_start();

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

// Konfigurasi Koneksi Database
$server = 'localhost';
$username = 'root';
$password = '';
$database = 'user_database';
$conn = mysqli_connect($server, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Function untuk mendapatkan user_id
function getUserId($conn, $username) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
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

$pesanSukses = "";
$pesanError = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = isset($_POST['nama']) ? htmlspecialchars(trim($_POST['nama'])) : '';
    $nis = isset($_POST['nis']) ? htmlspecialchars(trim($_POST['nis'])) : '';
    $kelas = isset($_POST['kelas']) ? htmlspecialchars(trim($_POST['kelas'])) : '';
    $suhu = isset($_POST['suhu']) ? floatval($_POST['suhu']) : 0.0;
    $status = isset($_POST['status']) ? htmlspecialchars(trim($_POST['status'])) : '';
    $keluhan = isset($_POST['keluhan']) ? htmlspecialchars(trim($_POST['keluhan'])) : '';
    $diagnosis = isset($_POST['diagnosis']) ? htmlspecialchars(trim($_POST['diagnosis'])) : '';
    $pertolongan = isset($_POST['pertolongan']) ? htmlspecialchars(trim($_POST['pertolongan'])) : '';

    try {
        $user_id = getUserId($conn, $_SESSION['username']);
        if (!$user_id) {
            throw new Exception("User ID tidak ditemukan.");
        }

        if (empty($nama) || empty($nis) || empty($kelas) || empty($suhu) || empty($status)) {
            throw new Exception("Mohon lengkapi semua data yang diperlukan.");
        }

        // Begin transaction
        $conn->begin_transaction();

        // 1. Insert ke pengingatobat terlebih dahulu
        $stmtPengingat = $conn->prepare("INSERT INTO pengingatobat 
            (patient_id, condition_name, severity, user_id) 
            VALUES (?, ?, ?, ?)");
        
        $patient_id = $nis;
        $condition_name = $status == 'Sakit' ? $diagnosis : 'Sehat';
        $severity = $status == 'Sakit' ? 'Medium' : 'Low';
        
        $stmtPengingat->bind_param("sssi", 
            $patient_id, $condition_name, $severity, $user_id
        );
        
        if (!$stmtPengingat->execute()) {
            throw new Exception("Error executing pengingat statement: " . $stmtPengingat->error);
        }
        
        $pengingat_id = $stmtPengingat->insert_id;
        $stmtPengingat->close();

        // Di bagian insert ke monitoringkesehatan, ubah query-nya menjadi:
$stmt = $conn->prepare("INSERT INTO monitoringkesehatan 
(nama, nis, keluhan, diagnosis, kelas, suhu, status, pertolongan_pertama, pengingat_id, user_id) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
throw new Exception("Error preparing monitoring statement: " . $conn->error);
}

$stmt->bind_param("sssssdssii", 
$nama, $nis, $keluhan, $diagnosis, $kelas, $suhu, $status, $pertolongan, $pengingat_id, $user_id
);
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing monitoring statement: " . $stmt->error);
        }
        
        $monitoring_id = $stmt->insert_id;
        $stmt->close();

        // 3. Update pengingatobat dengan monitoring_id
        $stmtUpdatePengingat = $conn->prepare("UPDATE pengingatobat SET monitoring_id = ? WHERE id = ?");
        $stmtUpdatePengingat->bind_param("ii", $monitoring_id, $pengingat_id);
        
        if (!$stmtUpdatePengingat->execute()) {
            throw new Exception("Error updating pengingat with monitoring_id: " . $stmtUpdatePengingat->error);
        }
        $stmtUpdatePengingat->close();

        // 4. Insert ke rekam_kesehatan
        $stmtRekam = $conn->prepare("INSERT INTO rekam_kesehatan 
            (nama, nis, keluhan, diagnosis, Pertolongan_Pertama, user_id) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        if (!$stmtRekam) {
            throw new Exception("Error preparing rekam statement: " . $conn->error);
        }

        $stmtRekam->bind_param("sssssi", 
            $nama, $nis, $keluhan, $diagnosis, $pertolongan, $user_id
        );
        
        if (!$stmtRekam->execute()) {
            throw new Exception("Error executing rekam statement: " . $stmtRekam->error);
        }
        $stmtRekam->close();

        // Commit transaction
        $conn->commit();
        $pesanSukses = "Data berhasil ditambahkan!";

    } catch (Exception $e) {
        if ($conn && $conn->connect_error === null) {
            $conn->rollback();
        }
        $pesanError = $e->getMessage();
    }
}
// Bagian menampilkan data
$daftarRekamKesehatan = [];
try {
    $query = "SELECT m.*, u.nama_lengkap 
              FROM monitoringkesehatan m 
              LEFT JOIN users u ON m.user_id = u.id 
              ORDER BY m.id DESC";
    $result = $conn->query($query);
    
    if ($result === false) {
        throw new Exception($conn->error);
    }
    
    while($row = $result->fetch_assoc()) {
        $daftarRekamKesehatan[] = $row;
    }
    $result->close();
    
} catch (Exception $e) {
    $pesanError = "Error mengambil data: " . $e->getMessage();
}
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
       /* Reset and Variables */
/* Modern Health Monitoring System Theme */
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

/* Table Container */
/* Table Styles */
table {
    grid-column: span 8;
    background: var(--white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--box-shadow);
    width: 100%;
    border-collapse: collapse;
    border: 3px solid #1ca883; /* Border utama lebih tebal */
}

th {
    background: var(--primary-color);
    color: var(--white);
    padding: 1rem;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 600;
    border: 2px solid #159f7f; /* Border header lebih tegas */
}

td {
    padding: 1rem;
    border: 2px solid #ccc; /* Border sel lebih tegas dan warna lebih gelap */
    font-size: 0.9rem;
    vertical-align: middle;
}

tr {
    border: 2px solid #ccc; /* Border baris lebih tegas */
}

tr:last-child td {
    border-bottom: 2px solid #ccc; /* Border baris terakhir */
}

/* Tambahan untuk mempertegas garis vertikal */
th:not(:last-child),
td:not(:last-child) {
    border-right: 2px solid #ccc;
}

/* Mempertegas header */
thead tr {
    border-bottom: 3px solid #159f7f;
}

/* Hover state */
tbody tr:hover {
    background-color: #f5f5f5;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.6rem;
    border: 2px solid var(--primary-light);
    border-radius: 8px;
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
th {
    background: var(--primary-color);
    color: var(--white);
    padding: 0.8rem;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 600;
}

td {
    padding: 0.8rem;
    border-bottom: 1px solid var(--primary-light);
    font-size: 0.9rem;
}

tr:hover {
    background-color: var(--primary-light);
}

/* Button Styles */
.btn-submit {
    background: linear-gradient(45deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    width: 100%;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1rem;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(28, 168, 131, 0.2);
}

/* Alert Styles */
.alert {
    grid-column: span 12;
    padding: 0.8rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: slideIn 0.5s ease-out;
}

.alert-success {
    background-color: var(--success);
    color: var(--white);
}

.alert-danger {
    background-color: var(--error);
    color: var(--white);
}

/* Health Status Indicators */
.status-healthy,
.status-sick {
    padding: 0.3rem 0.6rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-healthy {
    background-color: var(--success);
    color: var(--white);
}

.status-sick {
    background-color: var(--error);
    color: var(--white);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .form-container {
        grid-column: span 5;
    }
    
    table {
        grid-column: span 7;
    }
}

@media (max-width: 992px) {
    .form-container,
    table {
        grid-column: span 12;
    }
    
    .form-container {
        position: static;
    }
    
    table {
        overflow-x: auto;
        display: block;
    }
}

/* Animations */
@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Medical Icons Enhancement */
.medical-icon {
    font-size: 1.2rem;
    color: var(--primary-color);
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
    </style>
</head>
<body>
    
    <div class="container">
        <header>
            <h1><i class="fas fa-heartbeat"></i> Monitoring Kesehatan Siswa</h1>
        </header>

        <?php if ($pesanSukses): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($pesanSukses); ?></div>
        <?php endif; ?>
        <?php if ($pesanError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($pesanError); ?></div>
        <?php endif; ?>

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
                    <input type="text" name="keluhan" id="keluhan" required>
                </div>
                <div class="form-group">
                    <label for="diagnosis">Diagnosis:</label>
                    <input type="text" name="diagnosis" id="diagnosis" required>
                </div>
                <div class="form-group">
                    <label for="pertolongan">Pertolongan Pertama:</label>
                    <textarea name="pertolongan" id="pertolongan" rows="3" class="form-control"></textarea>
                </div>
                <button type="submit" class="btn-submit">Tambah Siswa</button>
            </form>
            
        </div>

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
                    <th>Pertolongan Pertama</th>
                </tr>
            </thead>
            <tbody id="siswaTableBody">
                <?php foreach ($daftarRekamKesehatan as $rekam): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rekam['id']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['nama']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['nis']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['kelas']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['suhu']); ?>°C</td>
                    <td><?php echo htmlspecialchars($rekam['status']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['keluhan']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['diagnosis']); ?></td>
                    <td><?php echo htmlspecialchars($rekam['pertolongan_pertama']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

       

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const nisInput = document.getElementById('nis');
    const namaInput = document.getElementById('nama');
    let isUpdating = false;

    async function searchStudent(searchTerm, searchType) {
        if (isUpdating || !searchTerm) return;
        
        try {
            isUpdating = true;
            const response = await fetch(`?action=getStudent&${searchType}=${encodeURIComponent(searchTerm)}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            
            if (data && (data.nama || data.nis)) {
                if (searchType === 'nis') {
                    namaInput.value = data.nama;
                } else if (searchType === 'nama') {
                    nisInput.value = data.nis;
                }
            }
        } catch (error) {
            console.error('Search error:', error);
        } finally {
            isUpdating = false;
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    const debouncedSearch = debounce(searchStudent, 300);

    nisInput.addEventListener('input', function(e) {
        const value = e.target.value.trim();
        if (value) debouncedSearch(value, 'nis');
    });

    namaInput.addEventListener('input', function(e) {
        const value = e.target.value.trim();
        if (value.length >= 3) debouncedSearch(value, 'nama');
    });
});
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const suhu = parseFloat(document.getElementById('suhu').value);
        
        if (suhu < 35 || suhu > 42) {
            e.preventDefault();
            alert('Suhu harus berada dalam rentang 35°C - 42°C');
            return false;
        }

        const nis = document.getElementById('nis').value;
        if (!/^\d+$/.test(nis)) {
            e.preventDefault();
            alert('NIS harus berupa angka');
            return false;
        }

        const kelas = document.getElementById('kelas').value;
        if (kelas.length < 2) {
            e.preventDefault();
            alert('Kelas harus diisi dengan benar (minimal 3 karakter)');
            return false;
        }
    });

    // Alert auto-hide
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    </script>
</body>
</html>