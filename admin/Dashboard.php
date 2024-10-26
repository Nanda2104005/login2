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

        
    </style>
</head>
<body>

<nav class="navbar">
    <div>
        <a href="Home.php"><i class="fas fa-home icon"></i>Home</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i>Dashboard</a>
        <a href="about.php"><i class="fas fa-info-circle icon"></i>About Us</a>
    </div>
    <form method="POST" action="login.php">
        <button type="submit" name="logout" class="btn">
            <i class="fas fa-sign-out-alt"></i>Logout
        </button>
    </form>
</nav>

<div class="container">
    <div class="dashboard-header">
        <h2>Sistem Informasi Kesehatan Sekolah (SIKS)</h2>
    </div>
    <div class="dashboard-grid">
    <div class="dashboard-card">
    <i class="fas fa-heartbeat health-icon"></i>
    <div>
        <h3><i class="fas fa-heartbeat icon"></i>Monitoring Kesehatan</h3>
        <p>Pantau kondisi kesehatan siswa secara real-time dengan sistem monitoring yang akurat dan responsif.</p>
    </div>
    <a href="monitoringkesehatan.php" class="btn-card">Buka Monitoring Kesehatan</a>
</div>

<div class="dashboard-card">
    <i class="fas fa-heartbeat health-icon"></i>
    <div>
        <h3><i class="fas fa-heartbeat icon"></i>Rekam Kesehatan Digital</h3>
        <p>Dapatkan notifikasi real-time untuk kondisi kesehatan yang memerlukan penanganan segera.</p>
    </div>
    <a href="RekamKesehatan.php" class="btn-card">Buka Monitoring Rekam Kesehatan</a>
</div>
        
<div class="dashboard-card">
    <i class="fas fa-heartbeat health-icon"></i>
    <div>
        <h3><i class="fas fa-heartbeat icon"></i>Sistem Peringatan Dini</h3>
        <p>Dapatkan notifikasi real-time untuk kondisi kesehatan yang memerlukan penanganan segera.</p>
    </div>
    <a href="peringatandini.php" class="btn-card">Buka Monitoring Sistem Peringatan Dini </a>
</div>

        <div class="dashboard-card">
            <i class="fas fa-chart-line health-icon"></i>
            <h3><i class="fas fa-chart-bar icon"></i>Analisis Kesehatan</h3>
            <p>Visualisasi data kesehatan komprehensif dengan grafik dan laporan yang mudah dipahami.</p>
        </div>

        
<div class="dashboard-card">
    <i class="fas fa-heartbeat health-icon"></i>
    <div>
        <h3><i class="fas fa-heartbeat icon"></i>Edukasi Kesehatan</h3>
        <p>Dapatkan informasi edukasi kesehatan berupa poster dan video yang dapat anda pelajari.</p>
    </div>
    <a href="edukasikesehatan.php" class="btn-card">Buka Monitoring Rekam Kesehatan</a>
</div>

    
    </div>
</div>

<footer>
    <p>&copy; 2024 Sistem Informasi Kesehatan Sekolah (SIKS). All Rights Reserved.</p>
</footer>

</body>
</html>