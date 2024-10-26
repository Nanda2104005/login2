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
    // Perbaikan query: menambahkan kolom diagnosis dan Pertolongan_Pertama
    $sql = "INSERT INTO rekam_kesehatan (Nama, ID, keluhan, diagnosis, Pertolongan_Pertama, tanggal) 
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
            --card-hover: #e8f5f1;
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

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 0.7rem 1.2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
            font-weight: 500;
        }

        .navbar a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
            color: var(--primary-color);
            position: relative;
        }

        .dashboard-header:after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--accent-color);
            margin: 1rem auto;
            border-radius: 2px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(28, 168, 131, 0.1);
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(28, 168, 131, 0.1);
            background-color: var(--card-hover);
        }

        .dashboard-card h3 {
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .dashboard-card p {
            color: #666;
            line-height: 1.6;
        }

        .icon {
            font-size: 1.5rem;
            color: var(--primary-color);
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Animated background for cards */
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(28, 168, 131, 0.03), transparent);
            background-size: 200% 200%;
            animation: shine 3s infinite;
            pointer-events: none;
        }

        @keyframes shine {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        /* Health-related icons and animations */
        .health-icon {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 4rem;
            opacity: 0.1;
            color: var(--primary-color);
            transform: rotate(10deg);
        }

        .btn-card {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-decoration: none;
            margin-top: 1rem;
            text-align: center;
        }

        .btn-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
        }

        .dashboard-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Tambahan CSS untuk menyesuaikan dengan HTML yang ada */
        h1 {
            text-align: center;
            color: var(--primary-color);
        }

        form {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
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

        input[type="submit"] {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, var(--accent-color), #ff8f8f);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
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

        .success {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 500;
        }

        .error {
            color: var(--accent-color);
            margin-bottom: 15px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="dashboard-header">Sistem Rekam Kesehatan Digital</h1>
        
        <?php if (isset($pesanSukses)): ?>
            <p class="success"><?php echo $pesanSukses; ?></p>
        <?php endif; ?>
        
        <?php if (isset($pesanError)): ?>
            <p class="error"><?php echo $pesanError; ?></p>
        <?php endif; ?>
        
        <form method="post" action="" class="dashboard-card">
            <input type="text" name="nama" placeholder="Nama Siswa" required>
            <input type="number" name="nis" placeholder="NIS" required>
            <textarea name="keluhan" placeholder="Keluhan" required></textarea>
            <textarea name="diagnosis" placeholder="Diagnosis" required></textarea>
            <textarea name="Pertolongan Pertama" placeholder="Pertolongan Pertama" required></textarea>
            <input type="submit" value="Tambah Rekam Kesehatan" class="btn-card">
        </form>
        
        <h2 class="dashboard-header">Daftar Rekam Kesehatan</h2>
        <div class="dashboard-card">
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
    </div>
</body>
</html>