<?php
require_once 'sessioncheck.php';
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// haloo

$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKS Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
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

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            text-decoration: none;
        }

        .btn-profile {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover, .btn-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 168, 131, 0.2);
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
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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

        .health-icon, .save-icon, .feature-icon {
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
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
            .navbar {
                flex-direction: column;
                padding: 0.5rem;
            }

            .navbar > div {
                width: 100%;
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                margin: 0.5rem 0;
            }

            .nav-buttons {
                justify-content: center;
                width: 100%;
                gap: 0.8rem;
            }

            .btn, .btn-profile {
                width: auto;
                min-width: 120px;
                justify-content: center;
                font-size: 0.9rem;
                margin: 0.2rem;
            }

            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0.5rem;
            }

            .dashboard-card {
                margin: 0.5rem 0;
            }

            .dashboard-header h2 {
                font-size: 1.5rem;
                padding: 0 1rem;
            }
        }

        @media (max-width: 480px) {
            .navbar a {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }

            .nav-buttons {
                flex-direction: row;
                gap: 0.5rem;
                width: 100%;
                justify-content: center;
            }

            .btn, .btn-profile {
                font-size: 0.85rem;
                padding: 0.7rem 1rem;
            }

            .dashboard-card {
                padding: 1.5rem;
            }

            .btn-card {
                padding: 0.5rem 0.8rem;
                font-size: 0.9rem;
            }

            .health-icon,
            .save-icon,
            .feature-icon {
                font-size: 3rem;
            }
        }

        @viewport {
            width: device-width;
            zoom: 1.0;
        }

        /* Style untuk tombol kembali */
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
}

.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.btn-back i {
    font-size: 1.1rem;
}

/* Mengubah posisi container untuk tombol */
.button-container {
    grid-column: span 12;
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
}
    </style>
</head>
<body>

<nav class="navbar">
    <div>
        <a href="Home.php"><i class="fas fa-home icon"></i>Home</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i>Dashboard</a>
        <?php if ($role === 'admin'): ?>
            <a href="about.php"><i class="fas fa-info-circle icon"></i>Tentang Kami</a>
        <?php endif; ?>
    </div>
    <div class="nav-buttons">
        <?php if ($role === 'siswa'): ?>
            <a href="profile.php" class="btn-profile">
                <i class="fas fa-user"></i>
                Profile
            </a>
        <?php endif; ?>
        <a href="logout.php" class="btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</nav>


<div class="container">
    <div class="dashboard-header">
        <h2>Sistem Informasi Kesehatan Sekolah</h2>
        <h2> SMA Muhammadiyah 3 Jember</h2>
    </div>
    <div class="dashboard-grid">
        <?php if ($role === 'admin'): ?>
                        <div class="dashboard-card">
                <i class="fas fa-heartbeat health-icon"></i>
                <div>
                    <h3><i class="fas fa-heartbeat icon"></i>Monitoring Kesehatan</h3>
                    <p>Pantau kondisi kesehatan siswa secara real-time dengan sistem monitoring yang akurat dan responsif.</p>
                </div>
                <a href="monitoringkesehatan.php" class="btn-card">Buka Monitoring Kesehatan</a>
            </div>

            <!-- Rekam Kesehatan Digital -->
            <div class="dashboard-card">
                <i class="fas fa-file-medical feature-icon"></i>
                <div>
                    <h3><i class="fas fa-file-medical icon"></i>Rekam Kesehatan Digital</h3>
                    <p>Dapatkan notifikasi real-time untuk kondisi kesehatan yang memerlukan penanganan segera.</p>
                </div>
                <a href="RekamKesehatan.php" class="btn-card">Buka Rekam Kesehatan</a>
            </div>

            <!-- Sistem Peringatan Dini -->
            <div class="dashboard-card">
                <i class="fas fa-bell health-icon"></i>
                <div>
                    <h3><i class="fas fa-bell icon"></i>Sistem Peringatan Dini</h3>
                    <p>Dapatkan notifikasi real-time untuk kondisi kesehatan yang memerlukan penanganan segera.</p>
                </div>
                <a href="peringatandini.php" class="btn-card">Buka Monitoring Sistem Peringatan Dini</a>
            </div>

            <!-- Analisis Kesehatan -->
            <div class="dashboard-card">
                <i class="fas fa-chart-bar health-icon"></i>
                <div>
                    <h3><i class="fas fa-chart-bar icon"></i>Check BMI </h3>
                    <p>Visualisasi data kesehatan komprehensif dengan grafik dan laporan yang mudah dipahami berupa Kalkulator BMI,Tekanan Darah dan Gula Darah.</p>
                </div>
                <a href="checkbmi.php" class="btn-card">Buka Check BMI</a>
            </div>

            <!-- Penyimpanan File dan Video Admin -->
            <div class="dashboard-card">
                <i class="fas fa-folder-open save-icon"></i>
                <div>
                    <h3><i class="fas fa-folder-open icon"></i>Penyimpanan File dan Video Admin</h3>
                    <p>Disini anda bisa melihat foto dan video yang admin simpan sebelumnya.</p>
                </div>
                <a href="savefilefotodanvideo.php" class="btn-card">Buka Penyimpanan File Foto dan Video</a>
            </div>

            <!-- Upload Edukasi Kesehatan -->
            <div class="dashboard-card">
                <i class="fas fa-upload save-icon"></i>
                <div>
                    <h3><i class="fas fa-upload icon"></i>Upload Edukasi Kesehatan</h3>
                    <p>Disini anda bisa melihat foto dan video kalian yang sudah kalian simpan.</p>
                </div>
                <a href="edukasikesehatan.php" class="btn-card">Buka Upload Edukasi Kesehatan</a>
            </div>

            <!-- Inventaris Kesehatan -->
            <div class="dashboard-card">
                <i class="fas fa-box-open save-icon"></i>
                <div>
                    <h3><i class="fas fa-box-open icon"></i>Inventaris Kesehatan</h3>
                    <p>Disini anda bisa melihat barang barang simpan.</p>
                </div>
                <a href="inventaris_uks.php" class="btn-card">Buka Inventaris Kesehatan</a>
            </div>

            <!-- Daftar Users -->
            <div class="dashboard-card">
                <i class="fas fa-users save-icon"></i>
                <div>
                    <h3><i class="fas fa-users icon"></i>Daftar Users</h3>
                    <p>Disini anda bisa melihat akun.</p>
                </div>
                <a href="userslist.php" class="btn-card">Buka Users List</a>
            </div>

            <!-- Stok Obat -->
            <div class="dashboard-card">
                <i class="fas fa-pills save-icon"></i>
                <div>
                    <h3><i class="fas fa-pills icon"></i>Stok Obat</h3>
                    <p>Disini anda bisa melihat barang barang simpan.</p>
                </div>
                <a href="stokobat.php" class="btn-card">Buka Stok Obat</a>
            </div>
            


        <?php endif; ?>

        <?php if ($role === 'siswa'): ?>
                    <div class="dashboard-card">
            <i class="fas fa-graduation-cap save-icon"></i>
            <div>
                <h3><i class="fas fa-graduation-cap icon"></i>Edukasi Kesehatan Siswa</h3>
                <p>Disini anda bisa melihat foto dan video edukasi yang telah disediakan.</p>
            </div>
            <a href="savefilefotodanvideosiswa.php" class="btn-card">Buka Edukasi Kesehatan Siswa</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; 2024 Sistem Informasi Kesehatan Sekolah (SIKS). All Rights Reserved.</p>
</footer>

</body>
</html>