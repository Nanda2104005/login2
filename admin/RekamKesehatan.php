<?php
session_start();

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "user_database");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Fungsi untuk menambah rekam medis baru
function tambahRekamKesehatan($nama, $nis, $keluhan, $diagnosis, $Pertolongan_Pertama) {
    global $conn;
    $sql = "INSERT INTO rekam_kesehatan (nama, nis, keluhan, diagnosis, Pertolongan Pertama, tanggal) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nama, $nis, $keluhan, $diagnosis, $Pertolongan_Pertama);
    return $stmt->execute();
}

// Fungsi untuk mengambil semua rekam medis
function ambilSemuaRekamKesehatan() {
    global $conn;
    $result = $conn->query("SELECT * FROM rekam_kesehatan ORDER BY tanggal DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Proses form jika ada submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST["nama"];
    $nis = $_POST["nis"];
    $keluhan = $_POST["keluhan"];
    $diagnosis = $_POST["diagnosis"];
    $Pertolongan_Pertama = $_POST["Pertolongan_Pertama"];
    
    if (tambahRekamKesehatan($nama, $nis, $keluhan, $diagnosis, $Pertolongan_Pertama)) {
        $pesanSukses = "Rekam kesehatan berhasil ditambahkan.";
    } else {
        $pesanError = "Terjadi kesalahan. Silakan coba lagi.";
    }
}

$daftarRekamKesehatan = ambilSemuaRekamKesehatan();
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Rekam Kesehatan Digital</title>
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
    line-height: 1.6;
    margin: 0;
    padding: 0;
    background-color: var(--background-color);
    color: var(--text-color);
    padding-bottom: 60px;
}

.container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(28, 168, 131, 0.1);
}

header {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    color: white;
    text-align: center;
    padding: 1rem;
    border-radius: 20px 20px 0 0;
    margin: -2rem -2rem 2rem -2rem;
}

h1 {
    margin: 0;
    font-size: 2.5rem;
}

.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 5px 15px rgba(28, 168, 131, 0.1);
    transition: all 0.3s ease;
    border: 1px solid rgba(28, 168, 131, 0.1);
}

.card:hover {
    transform: translateY(-5px);
    background-color: var(--card-hover);
}

.card i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

.card h3 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--primary-color);
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(28, 168, 131, 0.1);
}

th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--secondary-color);
}

th {
    background-color: var(--primary-color);
    color: white;
    font-weight: bold;
    text-transform: uppercase;
}

tr:hover {
    background-color: var(--card-hover);
}

.status-sehat {
    color: var(--primary-color);
    font-weight: bold;
}

.status-sakit {
    color: var(--danger-color);
    font-weight: bold;
}

.btn {
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, var(--accent-color), #ff8f8f);
    color: white;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

footer {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    color: white;
    text-align: center;
    padding: 1rem 0;
    position: fixed;
    bottom: 0;
    width: 100%;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    table {
        font-size: 0.9rem;
    }
}

input[type="text"], input[type="number"], textarea {
    width: 100%;
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid var(--secondary-color);
    border-radius: 8px;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
    font-family: 'Poppins', sans-serif;
}

input[type="text"]:focus, input[type="number"]:focus, textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(28, 168, 131, 0.1);
}

input[type="submit"] {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
}

input[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
}

.success {
    color: var(--primary-color);
    margin-bottom: 15px;
    font-weight: 500;
}

.error {
    color: var(--danger-color);
    margin-bottom: 15px;
    font-weight: 500;
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistem Rekam Kesehatan Digital</h1>
        
        <?php if (isset($pesanSukses)): ?>
            <p class="success"><?php echo $pesanSukses; ?></p>
        <?php endif; ?>
        
        <?php if (isset($pesanError)): ?>
            <p class="error"><?php echo $pesanError; ?></p>
        <?php endif; ?>
        
        <form method="post" action="">
            <input type="text" name="nama" placeholder="Nama Siswa" required>
            <input type="number" name="nis" placeholder="NIS" required>
            <textarea name="keluhan" placeholder="Keluhan" required></textarea>
            <textarea name="diagnosis" placeholder="Diagnosis" required></textarea>
            <textarea name="Pertolongan Pertama" placeholder="Pertolongan Pertama" required></textarea>
            <input type="submit" value="Tambah Rekam Kesehatan">
        </form>
        
        <h2>Daftar Rekam Kesehatan</h2>
        <table>
            <tr>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>NIS</th>
                <th>Keluhan</th>
                <th>Diagnosis</th>
                <th>Pertolongan Pertama</th>
            </tr>
            <?php foreach ($daftarRekamKesehatan as $rekam): ?>
            <tr>
                <td><?php echo $rekam['tanggal']; ?></td>
                <td><?php echo $rekam['nama']; ?></td>
                <td><?php echo $rekam['nis']; ?></td>
                <td><?php echo $rekam['keluhan']; ?></td>
                <td><?php echo $rekam['diagnosis']; ?></td>
                <td><?php echo $rekam['Pertolongan_Pertama']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>