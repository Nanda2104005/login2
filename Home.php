

<?php

session_start();

// database_config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'user_database');

require_once 'sessioncheck.php';
// Connection function
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Update user's online status
function updateUserStatus($userId, $status) {
    $conn = connectDB();
    $status = $status ? 1 : 0;
    
    $sql = "UPDATE users SET 
            is_online = ?, 
            last_activity = CURRENT_TIMESTAMP,
            status_updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $status, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function cekAkses($roleYangDiizinkan) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $roleYangDiizinkan) {
        header("Location: akses_ditolak.php");
        exit();
    }
}

// Get all online users
function getOnlineUsers() {
    $conn = connectDB();
    // Consider users offline if no activity for 5 minutes
    $sql = "SELECT id, full_name, class, is_online 
            FROM users 
            WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
            OR is_online = 1 
            ORDER BY is_online DESC, full_name ASC";
            
    $result = $conn->query($sql);
    $users = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    return $users;
}

// Auto-update status based on activity
function autoUpdateStatus() {
    $conn = connectDB();
    // Set users as offline if inactive for more than 5 minutes
    $sql = "UPDATE users 
            SET is_online = 0 
            WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
            AND is_online = 1";
    
    $conn->query($sql);
    $conn->close();
}

if ($_SERVER['PHP_SELF'] === '/manajemen_kesehatan.php' || 
    $_SERVER['PHP_SELF'] === '/monitoringkesehatan.php' || 
    $_SERVER['PHP_SELF'] === '/rekamkesehatan.php' || 
    $_SERVER['PHP_SELF'] === '/analisiskesehatan.php') {
    cekAkses('admin');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M3 Care - Modern Healthcare System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        
        :root {
    --primary-color: #1ca883;
    --primary-dark: #158a6d;
    --secondary-color: #f0f9f6;
    --accent-color: #ff6b6b;
    --text-color: #2c3e50;
    --card-hover: #e8f5f1;
    --glass-bg: rgba(255, 255, 255, 0.95);
    --glass-border: rgba(255, 255, 255, 0.18);
        }

        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
        }    

        /* Updated: Added flex layout and min-height for better footer positioning */
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background-color: var(--secondary-color);
    color: var(--text-color);
    line-height: 1.6;
    overflow-x: hidden;
    margin-right: 300px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
}


        /* Animated Background */
        .bg-animation {
            position: fixed;
            width: 100vw;
            height: 100vh;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(45deg, rgba(28, 168, 131, 0.1), rgba(255, 107, 107, 0.1));
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, var(--primary-color) 0%, transparent 70%);
            top: -300px;
            left: -300px;
            opacity: 0.1;
            animation: float 15s infinite alternate;
        }

        .bg-animation::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--accent-color) 0%, transparent 70%);
            bottom: -250px;
            right: -250px;
            opacity: 0.1;
            animation: float 20s infinite alternate-reverse;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(100px, 100px) rotate(360deg); }
        }

        /* Modern Navbar */
        .navbar {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            padding-right: calc(300px + 2rem);
        }

        .navbar-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
        .navbar-brand {
    color: var(--primary-color);
    font-size: 1.8rem;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

        .navbar-links {
    display: flex;
    align-items: center;
    gap: 2rem;
}

        .nav-link {
    color: var(--text-color);
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-link:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

        .btn-logout {
    background: linear-gradient(135deg, var(--accent-color), #ff8f8f);
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    position: absolute;
    top: 1rem;
    right: 2rem;
}

.btn-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

        /* Hero Section */
        .hero {
            margin-top: 5rem;
            padding: 8rem 2rem;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            color: white;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .hero-text p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-image {
            position: relative;
            animation: float-slow 6s infinite ease-in-out;
        }

        .hero-image img {
            width: 100%;
            height: auto;
        }

        @keyframes float-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Feature Cards */
        .features {
            padding: 4rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: 0.5s;
        }

        .feature-card:hover::before {
            transform: translateX(100%);
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(28, 168, 131, 0.15);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(-10deg);
            color: var(--accent-color);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .feature-card p {
            color: var(--text-color);
            opacity: 0.8;
            font-size: 1.1rem;
        }

        /* Stats Section */
        .stats {
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            clip-path: polygon(0 15%, 100% 0, 100% 85%, 0 100%);
            color: white;
            text-align: center;
        }

        .stats-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            padding: 2rem 0;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Footer */
        footer {
            background: var(--primary-dark);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .status-sidebar {
            position: fixed;
            right: -300px; /* Start hidden */
            top: 0;
            width: 300px;
            height: 100vh;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-left: 1px solid var(--glass-border);
            padding: 6rem 1rem 1rem 1rem;
            z-index: 999;
            overflow-y: auto;
            transition: right 0.3s ease-in-out;
        }

        .status-sidebar.open{

            right: 0;
        }

        .status-toggle {
            position: fixed;
            right: 20px;
            top: 85px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(28, 168, 131, 0.2);
        }

        .status-toggle:hover {
            transform: translateY(-2px);
            background: var(--primary-dark);
            box-shadow: 0 6px 20px rgba(28, 168, 131, 0.3);
        }

        .status-toggle i {
            transition: transform 0.3s ease;
        }

        .status-toggle.open i {
            transform: rotate(180deg);
        }

        .status-header {
            color: var(--primary-color);
            padding: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .student-status {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .student-status:hover {
            transform: translateX(-5px);
            background: rgba(255, 255, 255, 0.8);
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .online {
            background-color: var(--primary-color);
            box-shadow: 0 0 5px var(--primary-color);
        }

        .offline {
            background-color: var(--accent-color);
            opacity: 0.5;
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-weight: 500;
            color: var(--text-color);
        }

        .student-class {
            font-size: 0.8rem;
            color: var(--text-color);
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .status-sidebar {
                width: 280px;
                right: -280px;
            }
            body.sidebar-open {
                margin-right: 0; /* Don't shift content on mobile */
            }
            .navbar.sidebar-open {
                padding-right: 2rem; /* Don't adjust navbar on mobile */
            }
        
            }

            body {
                margin-right: 0;
                transition: margin-right 0.3s ease-in-out;
            }

            .navbar {
                padding-right: calc(250px + 2rem);
            }

            body.sidebar-open {
            margin-right: 300px;
        }
           
            

            .hero-image {
                order: -1;
                margin: 0 auto;
                max-width: 500px;
            }

            .hero-text h1 {
                font-size: 2.8rem;
            }
        

            @media (max-width: 768px) {
            body {
                margin-right: 0 !important; /* Override margin untuk mobile */
            }
            
            .navbar {
                padding-right: 1rem !important; /* Reset padding navbar */
            }
            
            .navbar-content {
                position: relative;
            }
            
            .navbar-links {
                display: none; /* Sembunyikan menu secara default */
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--glass-bg);
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
                border-radius: 0 0 12px 12px;
            }
            
            .navbar-links.show {
                display: flex;
            }
            
            .menu-toggle {
                display: block;
                background: none;
                border: none;
                color: var(--primary-color);
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0.5rem;
                position: absolute;
                right: 1rem;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .btn-logout {
                position: static;
                width: 100%;
                justify-content: center;
                margin-top: 0.5rem;
            }
            
            .hero-content {
                grid-template-columns: 1fr;
                padding: 2rem 1rem;
                text-align: center;
            }
            
            .hero-image {
                order: -1;
                margin: 0 auto 2rem;
            }
            
            .hero-image img {
                max-width: 200px;
                margin: 0 auto;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            
            .feature-card {
                margin-bottom: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                padding: 1rem;
            }
            
            .status-sidebar {
                width: 100%;
                right: -100%;
            }
            
            .status-sidebar.open {
                right: 0;
            }
        }

        /* Tambahan untuk tablet */
        @media (min-width: 769px) and (max-width: 1024px) {
            body {
                margin-right: 0;
            }
            
            .hero-content {
                gap: 2rem;
                padding: 4rem 2rem;
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .btn-start {
    background: linear-gradient(135deg, var(--accent-color), #ff8f8f);
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-start:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}
        

        
    </style>
</head>
<body>

    <div class="bg-animation"></div>

    <nav class="navbar">
        <div class="navbar-content">
            <a href="Home.php" class="navbar-brand">
                <i class="fas fa-heartbeat"></i>
                M3 Care
            </a>
            <div class="navbar-links">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="monitoringkesehatan.php" class="nav-link">
                    <i class="fas fa-heartbeat"></i>
                    Monitoring Kesehatan
                </a>
                <a href="rekamkesehatan.php" class="nav-link">
                    <i class="fas fa-notes-medical"></i>
                    Rekam Kesehatan
                </a>
                <?php endif; ?>
            <a href="about.php" class="nav-link">
                <i class="fas fa-info-circle"></i>
                Tentang Kami
            </a>

            <a href="logout.php" class="btn-logout">
    <i class="fas fa-sign-out-alt"></i>
    Logout
</a>

            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Selamat Datang di M3 Care</h1>
                <p>Sistem Informasi Kesehatan Modern untuk SMA Muhammadiyah 3 Jember. Monitoring kesehatan siswa dengan teknologi terkini untuk masa depan yang lebih sehat.</p>
                <a href="dashboard.php" class="btn-start">
                    <i class="fas fa-arrow-right"></i>
                    Mulai Sekarang
                </a>
            </div>
            <div class="hero-image">
            <img src="logo MU.png" alt="Healthcare Illustration" style="max-width: 300px; height: auto; float: right;">
            </div>
        </div>
    </section>

    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-user-md feature-icon"></i>
                <h3>Manajemen Kesehatan Digital</h3>
                <p>Kelola data kesehatan siswa dengan sistem digital yang terintegrasi dan mudah diakses.</p>
            </div>

            <div class="feature-card">
                <i class="fas fa-heartbeat feature-icon"></i>
                <h3>Monitoring Real-time</h3>
                <p>Pantau kondisi kesehatan siswa secara real-time dengan teknologi modern.</p>
             </a>
            </div>

            <div class="feature-card">
        <i class="fas fa-notes-medical feature-icon"></i>
        <h3>Rekam Kesehatan Siswa Terpadu</h3>
        <p>Akses riwayat kesehatan lengkap dengan sistem penyimpanan yang aman.</p>
            </a>
           </div>

            <div class="feature-card">
                <i class="fas fa-chart-line feature-icon"></i>
                <h3>Analisis & Laporan</h3>
                <p>Dapatkan insight kesehatan melalui analisis data dan laporan komprehensif.</p>
            </div>
        </div>
    </section>
           
        </div>
    </div>



    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>350+</h3>
                <p>Siswa Terpantau</p>
            </div>
            <div class="stat-item">
                <h3>10/5</h3>
                <p>Monitoring Aktif</p>
            </div>
            <div class="stat-item">
                <h3>99,9%</h3>
                <p>Data Transparan</p>
            </div>
            <div class="stat-item">
                <h3>-</h3>
                <p>Tahun Pengalaman</p>           
    </section>



    <footer>
        <p>&copy; 2024 M3 Care - Sistem Informasi Kesehatan Sekolah SMA Muhammadiyah 3 Jember
                </footer>

                <script>
        // Toggle menu mobile
        const menuToggle = document.querySelector('.menu-toggle');
        const navbarLinks = document.querySelector('.navbar-links');
        
        // Tampilkan tombol menu hanya di mobile
        function checkScreenSize() {
            if (window.innerWidth <= 768) {
                menuToggle.style.display = 'block';
            } else {
                menuToggle.style.display = 'none';
                navbarLinks.style.display = 'flex';
            }
        }
        
        // Check saat halaman dimuat dan saat ukuran layar berubah
        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);
        
        // Toggle menu saat tombol diklik
        menuToggle.addEventListener('click', () => {
            navbarLinks.classList.toggle('show');
        });
        
        // Tutup menu saat link diklik
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    navbarLinks.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
