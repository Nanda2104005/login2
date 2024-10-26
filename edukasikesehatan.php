<?php
// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "user_databasee");

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

    // Simpan ke database
    $sql = "INSERT INTO edukasi_kesehatan (judul, konten, gambar, video, video_file) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $judul, $konten, $gambar, $video, $video_file);

    if ($stmt->execute()) {
        $success_message = "Data berhasil disimpan.";
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Ambil data dari database
$sql = "SELECT * FROM edukasi_kesehatan ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$judul = $row['judul'] ?? '';
$konten = $row['konten'] ?? '';
$gambar = $row['gambar'] ?? '';
$video = $row['video'] ?? '';
$video_file = $row['video_file'] ?? '';

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

        form {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--secondary-color);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
        }

        input[type="file"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--secondary-color);
            border-radius: 10px;
            background-color: white;
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
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            font-weight: 500;
        }

        .success {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .error {
            background-color: #fde2e2;
            color: var(--danger-color);
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            margin-top: 1rem;
            border-radius: 10px;
        }

        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 10px;
        }

        .video-upload-container {
            margin-top: 1rem;
        }

        .video-source-selector {
            margin-bottom: 1rem;
        }

        .video-source-selector label {
            margin-right: 1rem;
        }

        img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-top: 1rem;
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


        
    </style>
</head>
<body>
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