<?php
// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "user_database");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Mulai session
session_start();

// Fungsi untuk mengecek status login
function isLoggedIn() {
    return isset($_SESSION['username']) && isset($_SESSION['role']);
}

// Fungsi untuk mengecek apakah user memiliki akses
function hasAccess() {
    // Izinkan akses untuk admin dan siswa
    return isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'siswa');
}

// Fungsi untuk redirect ke halaman login dengan pesan
function redirectToLogin($message = '') {
    $redirect_url = 'login.php';
    if (!empty($message)) {
        $redirect_url .= '?message=' . urlencode($message);
    }
    header("Location: " . $redirect_url);
    exit();
}

// Cek akses
if (!hasAccess()) {
    redirectToLogin('Silakan login terlebih dahulu');
}

// Fungsi untuk mendapatkan ID video YouTube dari URL
function getYoutubeVideoId($url) {
    $video_id = '';
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        $video_id = $match[1];
    }
    return $video_id;
}

// Mengambil data dari database
$sql = "SELECT * FROM edukasi_kesehatan ORDER BY id DESC";
$result = $conn->query($sql);

// Tampilkan pesan jika ada
if (isset($_GET['message'])) {
    if ($_GET['message'] == 'success') {
        $alert_message = "Data berhasil dihapus!";
        $alert_class = "success";
    } else if ($_GET['message'] == 'error') {
        $alert_message = "Gagal menghapus data!";
        $alert_class = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Edukasi Kesehatan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
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
    }

    header {
        background: linear-gradient(135deg, var(--primary-color), #159f7f);
        color: white;
        text-align: center;
        padding: 1rem;
        border-radius: 20px 20px 0 0;
        margin-bottom: 2rem;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: white;
        padding: 2rem;
        border-radius: 20px;
        width: 90%;
        max-width: 400px;
        text-align: center;
        position: relative;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        animation: modalAppear 0.3s ease-out;
    }

    @keyframes modalAppear {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-title {
        color: var(--text-color);
        margin-bottom: 1rem;
        font-size: 1.2rem;
    }

    .modal-buttons {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 1.5rem;
    }

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
        max-width: 300px;
    }

    /* Column widths */
    .content-table th:nth-child(1),
    .content-table td:nth-child(1) {
        width: 50px;
    }

    .content-table th:nth-child(2),
    .content-table td:nth-child(2) {
        width: 20%;
    }

    .content-table th:nth-child(3),
    .content-table td:nth-child(3) {
        width: 30%;
    }

    .content-table th:nth-child(4),
    .content-table td:nth-child(4),
    .content-table th:nth-child(5),
    .content-table td:nth-child(5) {
        width: 20%;
    }

    .content-table th:nth-child(6),
    .content-table td:nth-child(6) {
        width: 100px;
    }

    .content-table tr:hover {
        background-color: var(--card-hover);
    }

    .thumbnail {
        max-width: 150px;
        border-radius: 10px;
        margin: 0.5rem 0;
    }

    .video-container {
        width: 200px;
        height: 113px;
        margin: 0.5rem 0;
        border-radius: 10px;
        overflow: hidden;
    }

    .video-container iframe,
    .video-container video {
        width: 100%;
        height: 100%;
        border: none;
    }

    .btn {
        padding: 0.6rem 1.2rem;
        background: linear-gradient(135deg, var(--accent-color), #ff8f8f);
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

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
    }

    .delete-btn {
        background: linear-gradient(135deg, var(--danger-color), #c0392b);
        color: white;
        padding: 0.8rem 1.2rem;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        margin: 0.25rem;
    }

    .delete-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
    }

    .delete-btn i {
        font-size: 0.9rem;
    }

    .btn-delete {
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
    }

    .btn-yes {
        background: linear-gradient(135deg, var(--danger-color), #c0392b);
    }

    .btn-no {
        background: linear-gradient(135deg, #95a5a6, #7f8c8d);
    }

    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

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

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
            display: none;
        }
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

    /* Responsive styles */
    @media screen and (max-width: 1024px) {
        .container {
            padding: 0 1rem;
        }

        .content-table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }

        .thumbnail {
            max-width: 120px;
        }

        .video-container {
            width: 160px;
            height: 90px;
        }
    }

    @media screen and (max-width: 768px) {
        .container {
            padding: 0 0.5rem;
        }

        header {
            padding: 0.8rem;
        }

        .content-table th,
        .content-table td {
            padding: 0.8rem;
        }

        .btn,
        .delete-btn {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Data Edukasi Kesehatan</h1>
            <div class="user-info">
                <span>Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="login.php" class="btn" style="margin-left: 1rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <?php if (isset($alert_message)): ?>
            <div class="alert <?php echo $alert_class; ?>">
                <?php echo $alert_message; ?>
            </div>
        <?php endif; ?>

        <table class="content-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul</th>
                    <th>Konten</th>
                    <th>Gambar</th>
                    <th>Video</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    $no = 1;
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
                        echo "<td>" . nl2br(htmlspecialchars($row['konten'])) . "</td>";
                        echo "<td>";
                        if ($row['gambar']) {
                            echo "<img src='" . htmlspecialchars($row['gambar']) . "' alt='Thumbnail' class='thumbnail'>";
                        }
                        echo "</td>";
                        echo "<td>";
                        if ($row['video']) {
                            $youtube_id = getYoutubeVideoId($row['video']);
                            if ($youtube_id) {
                                echo "<div class='video-container'>";
                                echo "<iframe src='https://www.youtube.com/embed/" . htmlspecialchars($youtube_id) . "' 
                                      frameborder='0' allowfullscreen></iframe>";
                                echo "</div>";
                            }
                        } elseif ($row['video_file']) {
                            echo "<div class='video-container'>";
                            echo "<video controls>";
                            echo "<source src='" . htmlspecialchars($row['video_file']) . "' 
                                  type='video/" . pathinfo($row['video_file'], PATHINFO_EXTENSION) . "'>";
                            echo "Browser Anda tidak mendukung tag video.";
                            echo "</video>";
                            echo "</div>";
                        }
                        echo "</td>";
                        echo "<td>";
                        echo "<a href='Deletefotodanvideo.php?id=" . $row['id'] . "' class='delete-btn' 
                              onclick='return confirm(\"Apakah Anda yakin ingin menghapus data ini?\");'>";
                        echo "<i class='fas fa-trash'></i> Hapus</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align: center;'>Tidak ada data</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <footer>
        <p>&copy; 2024 Sistem Informasi Kesehatan Sekolah (SIKS). All Rights Reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>