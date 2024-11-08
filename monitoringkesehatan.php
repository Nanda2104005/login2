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

// Konfigurasi Koneksi Database
$server = 'localhost';
$username = 'root';
$password = '';
$database = 'user_database';
$conn = mysqli_connect($server, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Function to get student data
function getStudentData($conn, $search, $type) {
    if ($type === 'nis') {
        $stmt = $conn->prepare("SELECT nama, nis FROM users WHERE nis = ?");
    } else {
        $stmt = $conn->prepare("SELECT nama, nis FROM users WHERE nama LIKE ?");
        $search = "%$search%";
    }
    
    $stmt->bind_param("s", $search);
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

// Inisialisasi pesan
$pesanSukses = "";
$pesanError = "";

// Proses form jika di-submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Menggunakan htmlspecialchars untuk mencegah XSS
    $nama = isset($_POST['nama']) ? htmlspecialchars(trim($_POST['nama'])) : '';
    $nis = isset($_POST['nis']) ? htmlspecialchars(trim($_POST['nis'])) : '';
    $kelas = isset($_POST['kelas']) ? htmlspecialchars(trim($_POST['kelas'])) : '';
    $suhu = isset($_POST['suhu']) ? floatval($_POST['suhu']) : 0.0;
    $status = isset($_POST['status']) ? htmlspecialchars(trim($_POST['status'])) : '';
    $keluhan = isset($_POST['keluhan']) ? htmlspecialchars(trim($_POST['keluhan'])) : '';
    $diagnosis = isset($_POST['diagnosis']) ? htmlspecialchars(trim($_POST['diagnosis'])) : '';
    $pertolongan = isset($_POST['pertolongan']) ? htmlspecialchars(trim($_POST['pertolongan'])) : '';

    // Validasi input
    if (empty($nama) || empty($nis) || empty($kelas) || empty($suhu) || empty($status)) {
        $pesanError = "Mohon lengkapi semua data yang diperlukan.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Cek apakah nis sudah ada di tabel monitoringkesehatan
            $cekNis = $conn->prepare("SELECT nis FROM monitoringkesehatan WHERE nis = ?");
            $cekNis->bind_param("s", $nis);
            $cekNis->execute();
            $cekNis->store_result();

            if ($cekNis->num_rows > 0) {
                throw new Exception("NIS ini sudah ada dalam rekam kesehatan.");
            }

            $stmt = $conn->prepare("INSERT INTO monitoringkesehatan (nama, nis, keluhan, diagnosis, kelas, suhu, status, pertolongan_pertama) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            
            $stmt->bind_param("sssssdss", $nama, $nis, $keluhan, $diagnosis, $kelas, $suhu, $status, $pertolongan);
            // Changed 'sssssds' to 'sssssdss' to match the number of parameters
            if (!$stmt->execute()) {
                throw new Exception("Error: " . $stmt->error);
            }
            $stmt->close();

            // Insert into rekam_kesehatan with pertolongan_pertama
            $stmtRekam = $conn->prepare("INSERT INTO rekam_kesehatan (nama, nis, keluhan, diagnosis, pertolongan_pertama) 
                           VALUES (?, ?, ?, ?, ?)");
            if ($stmtRekam === false) {
                throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            
            $stmtRekam->bind_param("sssss", $nama, $nis, $keluhan, $diagnosis, $pertolongan);
            if (!$stmtRekam->execute()) {
                throw new Exception("Error: " . $stmtRekam->error);
            }
            $stmtRekam->close();

            // If everything is successful, commit the transaction
            $conn->commit();
            $pesanSukses = "Data berhasil ditambahkan ke monitoring dan rekam kesehatan!";
            
        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $conn->rollback();
            $pesanError = $e->getMessage();
        }

        if (isset($cekNis)) {
            $cekNis->close();
        }
    }
}

// Mengambil data dari tabel untuk ditampilkan
$daftarRekamKesehatan = [];
$result = $conn->query("SELECT * FROM monitoringkesehatan ORDER BY id DESC");
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $daftarRekamKesehatan[] = $row;
        }
    }
} else {
    $pesanError = "Error fetching data: " . $conn->error;
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

        /* Header Styles */
        header {
            position: relative;
            margin-bottom: 2rem;
        }

        header h1 {
            color: white;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        /* Table Styles */
        table {
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        th {
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
            padding: 1.2rem;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--secondary-color);
            vertical-align: middle;
            word-wrap: break-word;
        }

        tr:hover {
            background-color: var(--card-hover);
        }

        /* Form Styles */
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-top: 2rem;
        }

        .form-container h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
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
        .form-group select,
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
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            background: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
        }

        .form-group input:hover,
        .form-group select:hover,
        .form-group textarea:hover {
            border-color: #ccc;
            background: white;
        }

        /* Button Styles */
        .btn-submit {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.95rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            animation: fadeIn 0.3s ease;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media screen and (max-width: 1024px) {
            .container {
                padding: 0 1rem;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media screen and (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }

            header h1 {
                font-size: 1.5rem;
                padding: 1rem;
            }

            th, td {
                padding: 0.8rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .btn-submit {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-heartbeat"></i> Monitoring Kesehatan Siswa</h1>
        </header>

        <table>
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
            <tbody id="siswaTableBody">
                <?php foreach ($daftarRekamKesehatan as $rekam): ?>
                <tr>
                    <td><?php echo $rekam['id']; ?></td>
                    <td><?php echo $rekam['nama']; ?></td>
                    <td><?php echo $rekam['nis']; ?></td>
                    <td><?php echo $rekam['kelas']; ?></td>
                    <td><?php echo $rekam['suhu']; ?>°C</td>
                    <td><?php echo $rekam['status']; ?></td>
                    <td><?php echo $rekam['keluhan']; ?></td>
                    <td><?php echo $rekam['diagnosis']; ?></td>
                    <td><?php echo $rekam['pertolongan_pertama']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="form-container">
            <h2>Tambah Data Siswa</h2>
            <?php if ($pesanSukses): ?>
                <div class="alert alert-success"><?php echo $pesanSukses; ?></div>
            <?php endif; ?>
            <?php if ($pesanError): ?>
                <div class="alert alert-danger"><?php echo $pesanError; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
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
            <div style="text-align: center; margin-top: 20px;">
                <button onclick="window.location.href='dashboard.php';" class="btn-submit">Kembali ke Dashboard</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nisInput = document.getElementById('nis');
            const namaInput = document.getElementById('nama');
            
            nisInput.addEventListener('input', async function() {
                if (this.value.length > 0) {
                    try {
                        const response = await fetch(`?action=getStudent&nis=${this.value}`);
                        const data = await response.json();
                        if (data) {
                            namaInput.value = data.nama;
                        } else {
                            namaInput.value = '';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            });
            
            namaInput.addEventListener('input', async function() {
                if (this.value.length > 2) {
                    try {
                        const response = await fetch(`?action=getStudent&nama=${this.value}`);
                        const data = await response.json();
                        if (data) {
                            nisInput.value = data.nis;
                        } else {
                            nisInput.value = '';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            });
        });

        function updateDashboard() {
            const tableBody = document.getElementById('siswaTableBody');
            if (!tableBody) return;

            const rows = tableBody.querySelectorAll('tr');
            let totalSiswa = rows.length;
            let siswaSakit = 0;
            let totalSuhu = 0;

            rows.forEach((row) => {
                const statusCell = row.querySelector('td:nth-child(6)');
                const suhuCell = row.querySelector('td:nth-child(5)');
                
                if (statusCell && suhuCell) {
                    const status = statusCell.textContent.trim();
                    const suhuText = suhuCell.textContent.trim();
                    const suhu = parseFloat(suhuText.replace('°C', ''));

                    if (status === 'Sakit') siswaSakit++;
                    if (!isNaN(suhu)) totalSuhu += suhu;
                }
            });

            const ratarataSuhu = totalSiswa > 0 ? (totalSuhu / totalSiswa) : 0;

            // Update dashboard elements if they exist
            const totalSiswaElem = document.getElementById('totalSiswa');
            const siswaSakitElem = document.getElementById('siswaSakit');
            const ratarataSuhuElem = document.getElementById('ratarataSuhu');

            if (totalSiswaElem) totalSiswaElem.textContent = totalSiswa;
            if (siswaSakitElem) siswaSakitElem.textContent = siswaSakit;
            if (ratarataSuhuElem) ratarataSuhuElem.textContent = ratarataSuhu.toFixed(1) + '°C';
        }

        // Alert styling
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-success')) {
                    alert.style.cssText = `
                        padding: 1rem;
                        margin-bottom: 1rem;
                        border-radius: 8px;
                        background-color: #d4edda;
                        color: #155724;
                        border: 1px solid #c3e6cb;
                    `;
                } else if (alert.classList.contains('alert-danger')) {
                    alert.style.cssText = `
                        padding: 1rem;
                        margin-bottom: 1rem;
                        border-radius: 8px;
                        background-color: #f8d7da;
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                    `;
                }

                // Auto-hide alerts after 5 seconds
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
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
            if (kelas.length < 3) {
                e.preventDefault();
                alert('Kelas harus diisi dengan benar (minimal 3 karakter)');
                return false;
            }
        });

        // Initialize dashboard on page load
        window.onload = function() {
            updateDashboard();
        };
    </script>
    <!-- Previous JavaScript code remains the same -->
</body>
</html>