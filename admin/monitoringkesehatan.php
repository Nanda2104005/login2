<!DOCTYPE html>
<html lang="id">
<head>
<a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Kesehatan Siswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
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

.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 5px 15px rgba(28, 168, 131, 0.1);
    transition: all 0.3s ease;
    border: 1px solid rgba(28, 168, 131, 0.1);
}

.card:hover {
    transform: translateY(-5px);
    background-color: var(--card-hover);
}

.card i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

.card h3 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--primary-color);
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(28, 168, 131, 0.1);
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

.status-sehat {
    color: var(--primary-color);
    font-weight: bold;
}

.status-sakit {
    color: var(--danger-color);
    font-weight: bold;
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
    text-decoration: none;
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

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    table {
        font-size: 0.9rem;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-heartbeat"></i> Monitoring Kesehatan Siswa</h1>
        </header>
        
        <div class="dashboard">
            <div class="card">
                <i class="fas fa-user-graduate"></i>
                <h3>Total Siswa</h3>
                <p id="totalSiswa">0</p>
            </div>
            <div class="card">
                <i class="fas fa-procedures"></i>
                <h3>Siswa Sakit</h3>
                <p id="siswaSakit">0</p>
            </div>
            <div class="card">
                <i class="fas fa-temperature-high"></i>
                <h3>Rata-rata Suhu</h3>
                <p id="ratarataSuhu">0°C</p>
            </div>
        </div>

        <?php
        // Simulasi data siswa
        $siswa = [
            ['id' => 1, 'nama' => 'Budi Santoso', 'kelas' => '10A', 'suhu' => 36.5, 'status' => 'Sehat'],
            ['id' => 2, 'nama' => 'Ani Widya', 'kelas' => '10B', 'suhu' => 37.8, 'status' => 'Sakit'],
            ['id' => 3, 'nama' => 'Citra Dewi', 'kelas' => '11A', 'suhu' => 36.7, 'status' => 'Sehat'],
            ['id' => 4, 'nama' => 'Doni Prakasa', 'kelas' => '11B', 'suhu' => 36.9, 'status' => 'Sehat'],
            ['id' => 5, 'nama' => 'Eka Putri', 'kelas' => '12A', 'suhu' => 38.1, 'status' => 'Sakit'],
        ];
        ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Suhu (°C)</th>
                <th>Status</th>
            </tr>
            <?php foreach ($siswa as $s): ?>
            <tr>
                <td><?php echo $s['id']; ?></td>
                <td><?php echo $s['nama']; ?></td>
                <td><?php echo $s['kelas']; ?></td>
                <td><?php echo $s['suhu']; ?></td>
                <td class="status-<?php echo strtolower($s['status']); ?>"><?php echo $s['status']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <script>
        function updateDashboard() {
            const table = document.querySelector('table');
            const rows = table.querySelectorAll('tr');
            let totalSiswa = rows.length - 1; // Exclude header row
            let siswaSakit = 0;
            let totalSuhu = 0;

            rows.forEach((row, index) => {
                if (index === 0) return; // Skip header row
                const status = row.querySelector('td:last-child').textContent;
                const suhu = parseFloat(row.querySelector('td:nth-child(4)').textContent);
                
                if (status === 'Sakit') siswaSakit++;
                totalSuhu += suhu;
            });

            const ratarataSuhu = totalSuhu / totalSiswa;

            document.getElementById('totalSiswa').textContent = totalSiswa;
            document.getElementById('siswaSakit').textContent = siswaSakit;
            document.getElementById('ratarataSuhu').textContent = ratarataSuhu.toFixed(1) + '°C';
        }

        // Inisialisasi dashboard
        updateDashboard();

        // Simulasi pembaruan data real-time
        setInterval(function() {
            // Di sini Anda bisa menambahkan kode AJAX untuk mengambil data terbaru dari server
            console.log('Data diperbarui pada ' + new Date().toLocaleTimeString());
            updateDashboard();
        }, 5000); // Perbarui setiap 5 detik
    </script>
</body>
</html>