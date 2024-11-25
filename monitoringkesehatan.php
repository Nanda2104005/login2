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
    $nama_obat = isset($_POST['nama_obat']) ? htmlspecialchars(trim($_POST['nama_obat'])) : '';
    $jumlah_obat = isset($_POST['jumlah_obat']) ? intval($_POST['jumlah_obat']) : 0;

    try {
        $user_id = getUserId($conn, $_SESSION['username']);
        if (!$user_id) {
            throw new Exception("User ID tidak ditemukan.");
        }

        if (empty($nama) || empty($nis) || empty($kelas) || empty($suhu) || empty($status)) {
            throw new Exception("Mohon lengkapi semua data yang diperlukan.");
        }

        mysqli_begin_transaction($conn);

        // Insert ke pengingatobat terlebih dahulu
        $stmt = mysqli_prepare($conn, "INSERT INTO pengingatobat 
            (patient_id, condition_name, severity, user_id) 
            VALUES (?, ?, ?, ?)");
        
        $patient_id = $nis;
        $condition_name = $status == 'Sakit' ? $diagnosis : 'Sehat';
        $severity = $status == 'Sakit' ? 'Medium' : 'Low';
        
        mysqli_stmt_bind_param($stmt, "sssi", 
            $patient_id, $condition_name, $severity, $user_id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error executing pengingat statement: " . mysqli_stmt_error($stmt));
        }
        
        $pengingat_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Jika status sakit dan ada pemberian obat
        if ($status === 'Sakit' && !empty($nama_obat) && $jumlah_obat > 0) {
            // Cek stok obat
            $stmt = mysqli_prepare($conn, "SELECT jumlah FROM stok_obat WHERE nama = ? FOR UPDATE");
            mysqli_stmt_bind_param($stmt, "s", $nama_obat);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stok_current = $row['jumlah'];

            if ($stok_current < $jumlah_obat) {
                throw new Exception("Stok obat tidak mencukupi!");
            }

            // Update stok obat
            $stmt = mysqli_prepare($conn, "UPDATE stok_obat SET jumlah = jumlah - ? WHERE nama = ?");
            mysqli_stmt_bind_param($stmt, "is", $jumlah_obat, $nama_obat);
            mysqli_stmt_execute($stmt);
            
            // Simpan info obat di pengingatobat
            $stmt = mysqli_prepare($conn, "UPDATE pengingatobat SET nama_obat = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $nama_obat, $pengingat_id);
            mysqli_stmt_execute($stmt);
        }

        // Insert ke monitoringkesehatan
        $stmt = mysqli_prepare($conn, "INSERT INTO monitoringkesehatan 
            (nama, nis, keluhan, diagnosis, kelas, suhu, status, pertolongan_pertama, pengingat_id, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param($stmt, "sssssdssii", 
            $nama, $nis, $keluhan, $diagnosis, $kelas, $suhu, $status, $pertolongan, $pengingat_id, $user_id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error executing monitoring statement: " . mysqli_stmt_error($stmt));
        }
        
        $monitoring_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Insert ke rekam_kesehatan
        $stmt = mysqli_prepare($conn, "INSERT INTO rekam_kesehatan 
            (nama, nis, keluhan, diagnosis, Pertolongan_Pertama, user_id) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        mysqli_stmt_bind_param($stmt, "sssssi", 
            $nama, $nis, $keluhan, $diagnosis, $pertolongan, $user_id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error executing rekam statement: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        $pesanSukses = "Data berhasil ditambahkan!";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $pesanError = $e->getMessage();
    }
}

// Bagian menampilkan data
$daftarRekamKesehatan = [];
try {
    $query = "SELECT m.*, u.nama_lengkap, p.nama_obat 
              FROM monitoringkesehatan m 
              LEFT JOIN users u ON m.user_id = u.id 
              LEFT JOIN pengingatobat p ON m.pengingat_id = p.id
              ORDER BY m.id DESC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }
    
    while($row = mysqli_fetch_assoc($result)) {
        $daftarRekamKesehatan[] = $row;
    }
    mysqli_free_result($result);
    
} catch (Exception $e) {
    $pesanError = "Error mengambil data: " . $e->getMessage();
}

// Query untuk mendapatkan daftar obat
$obat_list = [];
try {
    $stmt = mysqli_prepare($conn, "SELECT nama FROM stok_obat WHERE jumlah > 0");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)) {
        $obat_list[] = $row['nama'];
    }
} catch (Exception $e) {
    $pesanError = "Error mengambil data obat: " . $e->getMessage();
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

.btn-back {
    position: fixed;
    top: 20px;
    left: 20px;
    background: var(--primary-color);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 1000;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-back:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
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
                    <label for="pertolongan">Tindakan:</label>
                    <textarea name="pertolongan" id="pertolongan" rows="3" class="form-control"></textarea>
                </div>

                <!-- Field obat yang hanya muncul jika status Sakit -->
                <div class="form-group obat-fields" style="display: none;">
                    <label for="nama_obat">Nama Obat:</label>
                    <select name="nama_obat" id="nama_obat" class="form-control">
                        <option value="">Pilih Obat</option>
                        <?php foreach($obat_list as $obat): ?>
                            <option value="<?php echo htmlspecialchars($obat); ?>">
                                <?php echo htmlspecialchars($obat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group obat-fields" style="display: none;">
                    <label for="jumlah_obat">Jumlah Obat:</label>
                    <input type="number" name="jumlah_obat" id="jumlah_obat" min="1" class="form-control">
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
                    <th>Tindakan</th>
                    <th>Obat</th>
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
                   <td>
                       <?php 
                       if ($rekam['status'] === 'Sakit') {
                           echo htmlspecialchars($rekam['nama_obat'] ?: '-');
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

   <script>
       document.addEventListener('DOMContentLoaded', function() {
           const nisInput = document.getElementById('nis');
           const namaInput = document.getElementById('nama');
           const statusSelect = document.getElementById('status');
           const obatFields = document.querySelectorAll('.obat-fields');
           let isUpdating = false;

           // Fungsi untuk menampilkan/menyembunyikan field obat
           function toggleObatFields(status) {
               obatFields.forEach(field => {
                   field.style.display = status === 'Sakit' ? 'block' : 'none';
               });
               
               // Reset nilai field obat jika status bukan Sakit
               if (status !== 'Sakit') {
                   document.getElementById('nama_obat').value = '';
                   document.getElementById('jumlah_obat').value = '';
               }
           }

           // Event listener untuk perubahan status
           statusSelect.addEventListener('change', function() {
               toggleObatFields(this.value);
           });

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
                   alert('Kelas harus diisi dengan benar (minimal 2 karakter)');
                   return false;
               }

               // Validasi field obat jika status Sakit
               if (statusSelect.value === 'Sakit') {
                   const namaObat = document.getElementById('nama_obat').value;
                   const jumlahObat = document.getElementById('jumlah_obat').value;
                   
                   if (!namaObat) {
                       e.preventDefault();
                       alert('Pilih obat yang akan diberikan');
                       return false;
                   }

                   if (!jumlahObat || jumlahObat < 1) {
                       e.preventDefault();
                       alert('Masukkan jumlah obat yang valid');
                       return false;
                   }
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
       });
   </script>
</body>
</html>