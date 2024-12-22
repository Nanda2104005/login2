<?php
session_start();
require_once 'config.php';
require_once 'sessioncheck.php';

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk mendapatkan ID video YouTube dari URL
function getYoutubeVideoId($url) {
    $video_id = '';
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        $video_id = $match[1];
    }
    return $video_id;
}

// Buat folder uploads jika belum ada
$upload_dir = "uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu");
}

$user_id = $_SESSION['user_id'];

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = clean_input($_POST["judul"]);
    $konten = clean_input($_POST["konten"]);
    $video = clean_input($_POST["video"] ?? '');
    
    // Proses unggahan video lokal
    $video_file = "";
    if (isset($_FILES["video_file"]) && $_FILES["video_file"]["error"] == 0) {
        $target_file = $upload_dir . basename($_FILES["video_file"]["name"]);
        $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Cek apakah file video valid
        $allowed_types = array("mp4", "webm", "ogg");
        if (in_array($videoFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
                $video_file = $target_file;
            } else {
                $error_message = "Maaf, terjadi kesalahan saat mengunggah file video.";
            }
        } else {
            $error_message = "Format video tidak didukung. Gunakan format MP4, WebM, atau OGG.";
        }
    }

    // Proses unggahan gambar
    $gambar = "";
    if (isset($_FILES["gambar"]) && $_FILES["gambar"]["error"] == 0) {
        $target_file = $upload_dir . basename($_FILES["gambar"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $check = getimagesize($_FILES["gambar"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                $gambar = $target_file;
            } else {
                $error_message = "Maaf, terjadi kesalahan saat mengunggah file.";
            }
        } else {
            $error_message = "File bukan merupakan gambar.";
        }
    }

    // Simpan ke database dengan user_id
    $sql = "INSERT INTO edukasi_kesehatan (judul, konten, gambar, video, video_file, user_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $judul, $konten, $gambar, $video, $video_file, $user_id);

    if ($stmt->execute()) {
        $success_message = "Data berhasil disimpan.";
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Ambil data dari database (sesuaikan dengan user_id)
$sql = "SELECT * FROM edukasi_kesehatan WHERE user_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$judul = $row['judul'] ?? '';
$konten = $row['konten'] ?? '';
$gambar = $row['gambar'] ?? '';
$video = $row['video'] ?? '';
$video_file = $row['video_file'] ?? '';

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edukasi Kesehatan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
    --primary-color: #1ca883;
    --primary-dark: #159f7f;
    --primary-light: #e8f5f1;
    --secondary-color: #f0f9f6;
    --accent-color: #ff6b6b;
    --text-color: #2c3e50;
    --background-color: #ecf0f1;
    --card-hover: #e8f5f1;
    --danger-color: #e74c3c;
    --white: #ffffff;
    --shadow: 0 10px 30px rgba(28, 168, 131, 0.1);
    --transition: all 0.3s ease;
}

body {
    font-family: 'Poppins', sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 0;
    background: linear-gradient(135deg, var(--background-color) 0%, var(--primary-light) 100%);
    color: var(--text-color);
    padding-bottom: 60px;
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 2rem;
    background: var(--white);
    border-radius: 25px;
    box-shadow: var(--shadow);
}

header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    text-align: center;
    padding: 2rem;
    border-radius: 20px 20px 0 0;
    margin: -2rem -2rem 2rem -2rem;
    position: relative;
    overflow: hidden;
}

header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1));
    pointer-events: none;
}

h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 600;
    letter-spacing: 1px;
}

form {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
    background: var(--white);
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 500;
    color: var(--text-color);
    font-size: 0.95rem;
}

input[type="text"], 
textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid var(--secondary-color);
    border-radius: 12px;
    font-family: 'Poppins', sans-serif;
    transition: var(--transition);
    background: var(--white);
    color: var(--text-color);
}

input[type="text"]:focus, 
textarea:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
    outline: none;
}

input[type="file"] {
    width: 100%;
    padding: 1rem;
    border: 2px dashed var(--primary-color);
    border-radius: 12px;
    background-color: var(--primary-light);
    cursor: pointer;
    transition: var(--transition);
}

input[type="file"]:hover {
    background-color: var(--secondary-color);
}

.btn {
    padding: 1rem 2rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: var(--transition);
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    justify-content: center;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.9rem;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(28, 168, 131, 0.2);
}

.message {
    padding: 1.2rem;
    margin-bottom: 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.success {
    background-color: var(--primary-light);
    color: var(--primary-dark);
    border-left: 4px solid var(--primary-color);
}

.error {
    background-color: #fde2e2;
    color: var(--danger-color);
    border-left: 4px solid var(--danger-color);
}

.video-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    margin-top: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.video-container iframe,
.video-container video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 15px;
}

.video-upload-container {
    margin-top: 1.5rem;
}

.video-source-selector {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--secondary-color);
    border-radius: 12px;
}

.video-source-selector label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
}

img {
    max-width: 100%;
    height: auto;
    border-radius: 15px;
    margin-top: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

footer {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    text-align: center;
    padding: 1.2rem 0;
    position: fixed;
    bottom: 0;
    width: 100%;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

/* Tambahkan tombol kembali */
.btn-back {
    position: fixed;
    top: 2rem;
    left: 2rem;
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: var(--transition);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

/* Responsif */
@media (max-width: 768px) {
    .container {
        margin: 1rem;
        padding: 1rem;
    }

    header {
        padding: 1.5rem;
    }

    h1 {
        font-size: 2rem;
    }

    .btn-back {
        top: 1rem;
        left: 1rem;
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
    }
}
        
    </style>
</head>
<body>
    <a href="dashboard.php" class="btn-back">
    <i class="fas fa-arrow-left"></i>
    Kembali ke Dashboard
</a>
    <div class="container">
        <header>
            <h1>Edukasi Kesehatan</h1>
        </header>
        
        <?php
        if (isset($success_message)) {
            echo "<div class='message success'>$success_message</div>";
        }
        if (isset($error_message)) {
            echo "<div class='message error'>$error_message</div>";
        }
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
            <input type="text" name="judul" placeholder="Judul" value="<?php echo htmlspecialchars($judul); ?>" required>
            <textarea name="konten" placeholder="Konten" rows="10" required><?php echo htmlspecialchars($konten); ?></textarea>
            <input type="file" name="gambar" accept="image/*">
            
            <div class="video-source-selector">
                <label>
                    <input type="radio" name="video_source" value="youtube" checked> YouTube URL
                </label>
                <label>
                    <input type="radio" name="video_source" value="file"> Upload Video
                </label>
            </div>

            <div id="youtube-input">
                <input type="text" name="video" placeholder="URL Video YouTube" value="<?php echo htmlspecialchars($video); ?>">
            </div>

            <div id="file-input" style="display: none;">
                <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg">
            </div>

            <button type="submit" class="btn"><i class="fas fa-save"></i> Simpan</button>
        </form>

        <?php if ($judul && $konten): ?>
            <h2><?php echo htmlspecialchars($judul); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($konten)); ?></p>
        <?php endif; ?>

        <?php if ($gambar): ?>
            <h3>Gambar:</h3>
            <img src="<?php echo htmlspecialchars($gambar); ?>" alt="Gambar Edukasi Kesehatan">
        <?php endif; ?>

        <?php if ($video || $video_file): ?>
            <h3>Video:</h3>
            <div class="video-container">
                <?php
                if ($video) {
                    $youtube_id = getYoutubeVideoId($video);
                    if ($youtube_id) {
                        echo '<iframe src="https://www.youtube.com/embed/' . htmlspecialchars($youtube_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                    }
                } elseif ($video_file) {
                    echo '<video controls>
                            <source src="' . htmlspecialchars($video_file) . '" type="video/' . pathinfo($video_file, PATHINFO_EXTENSION) . '">
                            Browser Anda tidak mendukung tag video.
                          </video>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Sistem Informasi Kesehatan Sekolah (SIKS). All Rights Reserved.</p>
    </footer>

    <script>
        document.querySelectorAll('input[name="video_source"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('youtube-input').style.display = 
                    this.value === 'youtube' ? 'block' : 'none';
                document.getElementById('file-input').style.display = 
                    this.value === 'file' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>