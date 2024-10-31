<?php
// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "user_database");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edukasi Kesehatan - Portal Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1ca883;
            --primary-dark: #158c6e;
            --secondary-color: #f0f9f6;
            --text-color: #2c3e50;
            --text-light: #6c757d;
            --background-color: #f8f9fa;
            --card-shadow: 0 8px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
            padding-bottom: 70px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 25px;
            margin-bottom: 3rem;
            box-shadow: var(--card-shadow);
        }

        header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
            padding: 0.5rem;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.12);
        }

        .image-container {
            position: relative;
            width: 100%;
            padding-top: 66.67%; /* 3:2 Aspect Ratio */
            background-color: #f8f9fa;
            overflow: hidden;
        }

        .card-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: white;
            transition: var(--transition);
            padding: 1rem;
        }

        .card-content {
            padding: 2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .card-title {
            font-size: 1.4rem;
            color: var(--primary-color);
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 0.5rem;
        }

        .card-text {
            font-size: 1rem;
            color: var(--text-color);
            flex-grow: 1;
            line-height: 1.8;
        }

        .video-container {
            width: 100%;
            position: relative;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            text-align: center;
            padding: 1.5rem 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        footer p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            header {
                padding: 2rem 1.5rem;
                margin-bottom: 2rem;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .card-title {
                font-size: 1.2rem;
            }

            .card-content {
                padding: 1.5rem;
            }
        }

        @media (min-width: 2000px) {
            .container {
                max-width: 1800px;
            }
            
            .content-grid {
                grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-heartbeat"></i> Portal Edukasi Kesehatan Siswa</h1>
            <p>Informasi dan edukasi kesehatan untuk meningkatkan kualitas hidup siswa</p>
        </header>

        <div class="content-grid">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<div class='content-card'>";
                    
                    // Gambar dengan container
                    if ($row['gambar']) {
                        echo "<div class='image-container'>";
                        echo "<img src='" . htmlspecialchars($row['gambar']) . "' alt='" . htmlspecialchars($row['judul']) . "' class='card-image'>";
                        echo "</div>";
                    }
                    
                    echo "<div class='card-content'>";
                    echo "<h2 class='card-title'><i class='fas fa-file-medical'></i> " . htmlspecialchars($row['judul']) . "</h2>";
                    echo "<p class='card-text'>" . nl2br(htmlspecialchars($row['konten'])) . "</p>";
                    
                    // Video
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
                    
                    echo "</div>"; // card-content
                    echo "</div>"; // content-card
                }
            } else {
                echo "<div class='empty-state'>";
                echo "<i class='fas fa-folder-open'></i>";
                echo "<p>Tidak ada konten edukasi tersedia saat ini.</p>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Sistem Informasi Kesehatan Sekolah (SIKS). All Rights Reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>