<?php
session_start();

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "user_database");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Tambahkan kode pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchResults = array(); // Array untuk menyimpan id hasil pencarian

if (!empty($search)) {
    $sql = "SELECT * FROM rekam_kesehatan WHERE 
            nama LIKE ? OR 
            nis LIKE ? OR 
            keluhan LIKE ? OR 
            diagnosis LIKE ? OR 
            Pertolongan_Pertama LIKE ? 
            ORDER BY tanggal DESC";
    $search_term = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $search_term);
    $stmt->execute();
    $daftarRekamKesehatan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Simpan ID hasil pencarian
    foreach ($daftarRekamKesehatan as $rekam) {
        $searchResults[] = $rekam['id'];
    }
} else {
    $daftarRekamKesehatan = ambilSemuaRekamKesehatan();
}

// Fungsi untuk menambah rekam medis baru
function tambahRekamKesehatan($nama, $nis, $keluhan, $diagnosis, $Pertolongan_Pertama) {
    global $conn;
    $sql = "INSERT INTO rekam_kesehatan (nama, nis, keluhan, diagnosis, Pertolongan_Pertama, tanggal) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nama, $nis, $keluhan, $diagnosis, $Pertolongan_Pertama);
    
    if ($stmt->execute()) {
        return true;
    } else {
        echo "Error: " . $stmt->error; // Tampilkan pesan kesalahan
        return false;
    }
}

// Fungsi untuk mengambil semua rekam medis
function ambilSemuaRekamKesehatan() {
    global $conn;
    $result = $conn->query("SELECT * FROM rekam_kesehatan ORDER BY tanggal DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk mengambil data by ID
function ambilRekamKesehatanById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM rekam_kesehatan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Fungsi untuk update data
function updateRekamKesehatan($id, $nama, $nis, $keluhan, $diagnosis, $Pertolongan_Pertama) {
    global $conn;
    $sql = "UPDATE rekam_kesehatan SET nama=?, nis=?, keluhan=?, diagnosis=?, Pertolongan_Pertama=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $nama, $nis, $keluhan, $diagnosis, $Pertolongan_Pertama, $id);
    
    if ($stmt->execute()) {
        return true;
    } else {
        echo "Error: " . $stmt->error; // Tampilkan pesan kesalahan
        return false;
    }
}

// Proses form jika ada submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['edit_id'])) {
        // Proses edit
        $id = $_POST['edit_id'];
        $nama = $_POST["nama"];
        $nis = $_POST["nis"];
        $keluhan = $_POST["keluhan"];
        $diagnosis = $_POST["diagnosis"];
        $Pertolongan_Pertama = $_POST["Pertolongan_Pertama"];
        
        if (updateRekamKesehatan($id, $nama, $nis, $keluhan, $diagnosis, $Pertolongan_Pertama)) {
            $pesanSukses = "Data berhasil diupdate.";
        } else {
            $pesanError = "Terjadi kesalahan saat update data.";
        }
    } else {
        // Proses tambah baru
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
}

// Ambil data untuk edit jika ada parameter id
$dataEdit = null;
if (isset($_GET['edit'])) {
    $dataEdit = ambilRekamKesehatanById($_GET['edit']);
}

$daftarRekamKesehatan = ambilSemuaRekamKesehatan();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Rekam Kesehatan Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

    header, .dashboard-header {
        background: linear-gradient(135deg, var(--primary-color), #159f7f);
        color: white;
        text-align: center;
        padding: 1rem;
        border-radius: 20px 20px 0 0;
        margin-bottom: 2rem;
    }

    .dashboard-header {
        position: relative;
        color: white; /* Changed to white */
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        background: linear-gradient(135deg, var(--primary-color), #159f7f);
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Removed the :after pseudo-element for dashboard-header */

    /* Form Styles */
    form {
        background-color: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
    }

    input[type="text"], 
    input[type="number"], 
    textarea {
        width: 100%;
        padding: 12px;
        margin-bottom: 20px;
        border: 1px solid var(--secondary-color);
        border-radius: 8px;
        box-sizing: border-box;
        transition: border-color 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    /* Table Styles */
    .content-table {
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
    }

    .content-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--secondary-color);
        vertical-align: middle;
        word-wrap: break-word;
    }

    .content-table tr:hover {
        background-color: var(--card-hover);
    }

    /* Button Styles */
    .btn,
    .btn-back,
    .btn-edit,
    .btn-card,
    .btn-search,
    input[type="submit"] {
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

    .btn:hover,
    .btn-back:hover,
    .btn-edit:hover,
    .btn-card:hover,
    .btn-search:hover,
    input[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
    }

    .btn-back {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1000;
    }

    /* Search Container */
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
    }

    /* Alert Messages */
    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        text-align: center;
        animation: fadeOut 3s forwards;
        animation-delay: 2s;
    }

    .alert.success {
        background-color: var(--secondary-color);
        color: var(--primary-color);
    }

    .alert.error {
        background-color: #fde2e2;
        color: var(--danger-color);
    }

    /* Footer */
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

    /* Animation */
    .highlight {
        animation: highlightFade 3s forwards;
    }

    @keyframes highlightFade {
        0% {
            background-color: rgba(28, 168, 131, 0.2);
        }
        100% {
            background-color: transparent;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
            display: none;
        }
    }

    /* Responsive Design */
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

        header, .dashboard-header {
            padding: 0.8rem;
        }

        .content-table th,
        .content-table td {
            padding: 0.8rem;
        }

        .btn,
        .btn-back,
        .btn-edit,
        .btn-card,
        .btn-search,
        input[type="submit"] {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .search-form {
            flex-direction: column;
        }

        .search-form input[type="text"] {
            width: 100%;
        }
    }

    .btn,
.btn-back,
.btn-edit,
.btn-delete,
.btn-card,
.btn-search,
input[type="submit"] {
    padding: 0.6rem 1.2rem;
    color: white;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    margin: 0 5px;
}

.btn,
.btn-back,
.btn-edit,
.btn-card,
.btn-search,
input[type="submit"] {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
}

.btn-delete {
    background: linear-gradient(135deg, var(--danger-color), #c0392b);
}

.btn:hover,
.btn-back:hover,
.btn-edit:hover,
.btn-delete:hover,
.btn-card:hover,
.btn-search:hover,
input[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
}

.btn-delete:hover {
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    .btn,
    .btn-back,
    .btn-edit,
    .btn-delete,
    .btn-card,
    .btn-search,
    input[type="submit"] {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">Kembali ke Dashboard</a>
        
        <h1 class="dashboard-header">Sistem Rekam Kesehatan Digital</h1>
        
        <?php if (isset($pesanSukses)): ?>
            <div class="alert success"><?php echo $pesanSukses; ?></div>
        <?php endif; ?>
        
        <?php if (isset($pesanError)): ?>
            <div class="alert error"><?php echo $pesanError; ?></div>
        <?php endif; ?>
        
        <form method="post" action="" class="search-container">
            <?php if ($dataEdit): ?>
                <input type="hidden" name="edit_id" value="<?php echo $dataEdit['id']; ?>">
            <?php endif; ?>
            <input type="text" name="nama" placeholder="Nama Siswa" value="<?php echo $dataEdit ? $dataEdit['nama'] : ''; ?>" required>
            <input type="number" name="nis" placeholder="NIS" value="<?php echo $dataEdit ? $dataEdit['nis'] : ''; ?>" required>
            <textarea name="keluhan" placeholder="Keluhan" required><?php echo $dataEdit ? $dataEdit['keluhan'] : ''; ?></textarea>
            <textarea name="diagnosis" placeholder="Diagnosis" required><?php echo $dataEdit ? $dataEdit['diagnosis'] : ''; ?></textarea>
            <textarea name="Pertolongan_Pertama" placeholder="Pertolongan Pertama" required><?php echo $dataEdit ? $dataEdit['Pertolongan_Pertama'] : ''; ?></textarea>
            <div class="button-group">
                <input type="submit" value="<?php echo $dataEdit ? 'Update Data' : 'Tambah Rekam Kesehatan'; ?>" class="btn-card">
                <?php if ($dataEdit): ?>
                    <a href="?" class="btn-card">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
        
        <h2 class="dashboard-header">Daftar Rekam Kesehatan</h2>
        
        <div class="search-container">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Cari siswa..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">Cari</button>
            </form>
        </div>

        <table class="content-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>NIS</th>
                    <th>Keluhan</th>
                    <th>Diagnosis</th>
                    <th>Pertolongan Pertama</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftarRekamKesehatan as $rekam): ?>
                <tr class="<?php echo (!empty($search) && in_array($rekam['id'], $searchResults)) ? 'highlight' : ''; ?>">
                    <td><?php echo $rekam['tanggal']; ?></td>
                    <td><?php echo $rekam['nama']; ?></td>
                    <td><?php echo $rekam['nis']; ?></td>
                    <td><?php echo $rekam['keluhan']; ?></td>
                    <td><?php echo $rekam['diagnosis']; ?></td>
                    <td><?php echo $rekam['Pertolongan_Pertama']; ?></td>
                    <td>
                        <a href="?edit=<?php echo $rekam['id']; ?>" class="btn-edit">Edit</a>
                        <a href="hapusrekam.php?id=<?php echo $rekam['id']; ?>" class="btn-delete" onclick="return confirm('Anda yakin ingin menghapus data ini?');">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
