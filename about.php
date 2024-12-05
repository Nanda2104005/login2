<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - SIKS SMA Muhammadiyah 3 Jember</title>
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
            line-height: 1.6;
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
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .about-section {
            background-color: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(28, 168, 131, 0.1);
            position: relative;
            overflow: hidden;
        }

        .about-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .section-title {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .feature-card {
            background: var(--secondary-color);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            transition: all 0.3s ease;
            border: 1px solid rgba(28, 168, 131, 0.1);
        }

        .feature-card:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(28, 168, 131, 0.1);
        }

        .contact-info {
            background: linear-gradient(135deg, #f8f9fa, var(--secondary-color));
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin: 1rem 0;
            color: var(--text-color);
        }

        .icon {
            color: var(--primary-color);
            margin-right: 1rem;
            font-size: 1.2rem;
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

        .mission-card {
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }

        .mission-card::before {
            content: '"';
            position: absolute;
            top: -20px;
            right: 20px;
            font-size: 150px;
            opacity: 0.1;
            font-family: serif;
            color: white;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }

        .feature-list li::before {
            content: '•';
            color: var(--primary-color);
            font-weight: bold;
            margin-right: 0.5rem;
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
    <a href="logout.php" class="btn">
    <i class="fas fa-sign-out-alt"></i>
    Logout
</a>
</nav>

<div class="container">
    <div class="about-section">
        <h2 class="section-title">
            <i class="fas fa-heart icon"></i>
            M3 Care - SMA Muhammadiyah 3 Jember
        </h2>

        <p>Selamat datang di M3 Care, sistem informasi kesehatan terpadu yang dirancang khusus untuk komunitas SMA Muhammadiyah 3 Jember. Sistem kami menghadirkan solusi modern untuk pemantauan dan pengelolaan kesehatan siswa.</p>

        <div class="mission-card">
            <h3><i class="fas fa-bullseye icon"></i>VISI SEKOLAH :</h3>
            <p>
“Mewujudkan Insan Mulia, Kreatif, Kolaboratif, dan Berdaya Saing”
Visi
Insan Mulia :
Warga sekolah yang terus berproses menjadi pribadi yang berakhlak mulia serta memiliki keshalihan pribadi dan sosial sebagai wujud pribadi yang beriman dan bertakwa kepada Allah SWT
Kreatif :
Mendorong warga sekolah untuk berkarya dan melalui ide-ide kreatif dan produktif yang memberikan manfaat bagi diri pribadi, sekolah maupun masyarakat
Kolaboratif :
Kerjasama dengan sekolah dalam dan luar negeri serta memperluas jejaring kerjasama dengan berbagai lembaga pemerintah maupun swasta dalam menjalankan program sekolah untuk mencapai tujuan sekolah
Berdaya Saing :
Pembangunan karakter serta peningkatan kualitas akademik dan non akademik melalui program pembimbingan dan pelatihan yang mendorong peserta didik untuk berprestasi sesuai bakat dan minat yang dimilikinya
<h3><i class="fas fa-bullseye icon"></i>MISI SEKOLAH :</h3>
1.	Pembangunan karakter dan pengamalan nilai-nilai Islam dalam praktek muamalah yang terintegrasi melalui program intra dan ekstrakurikuler
2.	Pembekalan ilmu pengetahuan sekaligus praktek ibadah mahdlah secara baik dan benar sesuai tuntunan Al Quran dan Hadits
3.	Kolaborasi dengan Muhammadiyah, ortom dan lembaganya di berbagai level serta organisasi dan lembaga pemerintah maupun swasta lainnya.
4.	Sinergi dan Kolaborasi dengan lembaga pendidikan dalam dan luar negeri
5.	Mendorong program edupreneurship yang inovatif melalui program pemberdayaan ekosistem di dalam dan di sekitar lingkungan sekolah
6.	Peningkatan sarana dan prasarana sekolah serta pembangunan Sistim Informasi Manajemen Sekolah
7.	Program beasiswa bagi siswa miskin dan berprestasi melalui program Orangtua Asuh, beasiswa Lazismu, serta donatur lain yang tidak mengikat
8.	Program akselerasi belajar khusus persiapan Seleksi Bersama Masuk PTN dan PTS favorit serta sekolah kedinasan dan TNI/POLRI
9.	Penyempurnaan pengelolaan manajemen keuangan dan sumberdaya manusia Guru dan Tenaga Kependidikan (GTK)</p>
        </div>

        <h3><i class="fas fa-cogs icon"></i>Layanan Unggulan M3 Care</h3>
        <div class="feature-list">
            <li><i class="fas fa-heartbeat icon"></i>Pemantauan Kesehatan Harian Real-time</li>
            <li><i class="fas fa-file-medical icon"></i>Sistem Rekam Medis Digital Terintegrasi</li>
            <li><i class="fas fa-bell icon"></i>Sistem Notifikasi Kesehatan Cerdas</li>
            <li><i class="fas fa-chart-line icon"></i>Analisis dan Laporan Kesehatan Komprehensif</li>
            <li><i class="fas fa-calendar-check icon"></i>Manajemen Program Kesehatan Sekolah</li>
            <li><i class="fas fa-user-md icon"></i>Layanan Konsultasi Kesehatan Online</li>
        </div>

        <div class="team-grid">
            <div class="feature-card">
                <h3><i class="fas fa-users icon"></i>Tim Profesional</h3>
                <p>Didukung oleh tim kesehatan profesional dan teknologi informasi yang berpengalaman untuk memberikan pelayanan terbaik.</p>
            </div>
            <div class="feature-card">
                <h3><i class="fas fa-handshake icon"></i>Kemitraan</h3>
                <p>Berkolaborasi dengan Dinas Kesehatan Jember dan fasilitas kesehatan setempat untuk pelayanan optimal.</p>
            </div>
        </div>

        <div class="contact-info">
            <h3><i class="fas fa-phone icon"></i>Hubungi Kami</h3>
            <div class="contact-item">
                <i class="fas fa-envelope icon"></i>
                <p>siks@smamuh3jember.sch.id</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone-alt icon"></i>
                <p>(0331) 123456</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock icon"></i>
                <p>Senin - Jumat: 07:00 - 15:00 WIB</p>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2024 Sistem Informasi Kesehatan Sekolah (SIKS) SMA Muhammadiyah 3 Jember. All Rights Reserved.</p>
</footer>

</body>
</html>