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
$database = 'user_database'; // Periksa ejaan nama database
$conn = mysqli_connect($server, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}


// Koneksi ke database
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "user_database"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error . " (" . $conn->connect_errno . ")");
} else {
    echo "";
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

    // Validasi input
    if (empty($nama) || empty($nis) || empty($kelas) || empty($suhu) || empty($status)) {
        $pesanError = "Mohon lengkapi semua data yang diperlukan.";
    } else {
        // Cek apakah nis sudah ada di tabel monitoringkesehatan
        $cekNis = $conn->prepare("SELECT nis FROM monitoringkesehatan WHERE nis = ?");
        $cekNis->bind_param("s", $nis);
        $cekNis->execute();
        $cekNis->store_result();

        if ($cekNis->num_rows > 0) {
            $pesanError = "NIS ini sudah ada dalam rekam kesehatan.";
        } else {
            // Insert data ke tabel monitoringkesehatan
            $stmt = $conn->prepare("INSERT INTO monitoringkesehatan ( nama, nis, keluhan, diagnosis, kelas, suhu, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                $pesanError = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            } else {
                $stmt->bind_param("sssssds", $nama, $nis, $keluhan, $diagnosis, $kelas, $suhu, $status); 
                if ($stmt->execute()) {
                    $pesanSukses = "Rekam kesehatan berhasil ditambahkan!";
                } else {
                    $pesanError = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $cekNis->close();
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

$conn->close();
?>




<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Kesehatan Siswa</title>
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
    --shadow-color: rgba(28, 168, 131, 0.1);
    --border-radius: 12px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--background-color);
    color: var(--text-color);
    line-height: 1.6;
    padding: 2rem;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Header Styles */
header {
    text-align: center;
    margin-bottom: 2rem;
}

header h1 {
    color: var(--primary-color);
    font-size: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

/* Dashboard Cards */
.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.card {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: 0 4px 6px var(--shadow-color);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px var(--shadow-color);
    background-color: var(--card-hover);
}

.card i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.card h3 {
    color: var(--text-color);
    margin-bottom: 0.5rem;
    font-size: 1.2rem;
}

.card p {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
}

/* Table Styles */
table {
    width: 100%;
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    border-collapse: collapse;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px var(--shadow-color);
}

th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--secondary-color);
}

th {
    background-color: var(--primary-color);
    color: white;
    font-weight: 600;
}

tr:hover {
    background-color: var(--secondary-color);
}

.status-sehat, .status-sakit {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    text-align: center;
}

.status-sehat {
    background-color: #e3fcef;
    color: #0f766e;
}

.status-sakit {
    background-color: #fee2e2;
    color: #dc2626;
}

/* Form Styles */
.form-container {
    background: white;
    padding: 2rem;
    border-radius: var(--border-radius);
    box-shadow: 0 4px 6px var(--shadow-color);
    margin-top: 2rem;
}

.form-container h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    text-align: center;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid var(--secondary-color);
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
}

.btn-submit {
    width: 100%;
    padding: 1rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    background: #158f6e;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px var(--shadow-color);
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 1rem;
    }
    
    .dashboard {
        grid-template-columns: 1fr;
    }
    
    table {
        display: block;
        overflow-x: auto;
    }
    
    .card {
        padding: 1rem;
    }
    
    header h1 {
        font-size: 2rem;
    }
}
    </style>
</head>
<body>

    <div class="container">
        <header>
            <h1><i class="fas fa-heartbeat"></i> Monitoring Kesehatan Siswa</h1>
        </header>
        
        <div class="dashboard">
            <div class="card">
                <i class="fas fa-user-graduate"></i>
                <h3>Total Siswa</h3>
                <p id="totalSiswa">0</p>
            </div>
            <div class="card">
                <i class="fas fa-procedures"></i>
                <h3>Siswa Sakit</h3>
                <p id="siswaSakit">0</p>
            </div>
            <div class="card">
                <i class="fas fa-temperature-high"></i>
                <h3>Rata-rata Suhu</h3>
                <p id="ratarataSuhu">0°C</p>
            </div>
        </div>

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
        
    </tr>
    <?php endforeach; ?>
</tbody>

</table>

        <!-- Form untuk Input Data Siswa Baru -->
        <div class="form-container">
    <h2>Tambah Data Siswa</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="nama">Nama:</label>
            <input type="text" name="nama" id="nama" required>
        </div>
        <div class="form-group">
            <label for="nis">NIS:</label>
            <input type="text" name="nis" id="nis" required>
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
            <input type="text" name="keluhan" id="keluhan" requied>
        </div>
        <div class="form-group">
            <label for="diagnosis">Diagnosis:</label>
            <input type="text" name="diagnosis" id="diagnosis" requied>
        </div>
        
        <button type="submit" class="btn-submit">Tambah Siswa</button>

        <!-- Alternatif menggunakan button -->
<div style="text-align: center; margin-top: 20px;">
    <button onclick="window.location.href='dashboard.php';" class="btn-submit">Kembali ke Dashboard</button>
</div>
    </form>
</div>
        </body>

    <script>
        function updateDashboard() {
    const tableBody = document.getElementById('siswaTableBody');
    const rows = tableBody.querySelectorAll('tr');
    let totalSiswa = rows.length;
    let siswaSakit = 0;
    let totalSuhu = 0;

    rows.forEach((row) => {
        const status = row.querySelector('td:nth-child(6)').textContent.trim();
        const suhuText = row.querySelector('td:nth-child(5)').textContent.trim();
        const suhu = parseFloat(suhuText.replace('°C', ''));

        if (status === 'Sakit') siswaSakit++;
        totalSuhu += suhu;
    });

    const ratarataSuhu = totalSuhu / totalSiswa;

    document.getElementById('totalSiswa').textContent = totalSiswa;
    document.getElementById('siswaSakit').textContent = siswaSakit;
    document.getElementById('ratarataSuhu').textContent = ratarataSuhu.toFixed(1) + '°C';
}

// Inisialisasi dashboard setelah halaman dimuat
window.onload = updateDashboard;


        // Tambahkan fungsi untuk menambah data ke tabel
        function addRowToTable(nama, kelas, suhu, status) {
            const tableBody = document.getElementById('siswaTableBody');
            const rowCount = tableBody.rows.length + 1; // New ID based on existing rows
            const row = tableBody.insertRow();
            row.innerHTML = `
                <td>${rowCount}</td>
                <td>${nama}</td>
                <td>${kelas}</td>
                <td>${suhu}</td>
                <td class="status-${status.toLowerCase()}">${status}</td>
            `;
            updateDashboard();
        }

        // Handle tombol submit
        document.getElementById('submitData').addEventListener('click', function() {
            const nama = document.getElementById('nama').value;
            const kelas = document.getElementById('kelas').value;
            const suhu = parseFloat(document.getElementById('suhu').value);
            const status = document.getElementById('status').value;

            if (nama && kelas && suhu && status) {
                addRowToTable(nama, kelas, suhu, status);
                document.getElementById('nama').value = '';
                document.getElementById('kelas').value = '';
                document.getElementById('suhu').value = '';
                document.getElementById('status').value = 'Sehat';
            } else {
                alert('Mohon lengkapi semua data.');
            }
        });

        // Inisialisasi dashboard
        updateDashboard();
    </script>
</body>
</html>
