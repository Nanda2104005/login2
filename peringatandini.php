    <?php
    session_start();

    // Database connection
    $conn = mysqli_connect("localhost", "root", "", "user_database");

    if (!$conn) {
        die("Koneksi gagal: " . mysqli_connect_error());
    }

    // Function to get monitoring data with status highlighting
    function getMonitoringData() {
        global $conn;
        $query = "SELECT m.*, 
                CASE 
                    WHEN m.status = 'Sakit' OR m.suhu >= 37.5 THEN 'severe'
                    ELSE 'healthy'
                END as severity
                FROM monitoringkesehatan m
                ORDER BY 
                    CASE WHEN m.status = 'Sakit' THEN 0 ELSE 1 END,
                    m.suhu DESC";

        $result = mysqli_query($conn, $query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // Function to update monitoring data
    function updateMonitoringData($id, $data) {
        global $conn;
        
        $checkQuery = "SELECT id FROM monitoringkesehatan WHERE nis = ? AND id != ?";
        $stmtCheck = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($stmtCheck, "si", $data['nis'], $id);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        
        if (mysqli_stmt_num_rows($stmtCheck) > 0) {
            mysqli_stmt_close($stmtCheck);
            return "NIS sudah digunakan oleh siswa lain.";
        }
        
        mysqli_stmt_close($stmtCheck);

        $query = "UPDATE monitoringkesehatan SET 
                nama = ?, nis = ?, kelas = ?, suhu = ?, 
                status = ?, keluhan = ?, diagnosis = ?
                WHERE id = ?";
                
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssdsssi", 
            $data['nama'], $data['nis'], $data['kelas'], $data['suhu'],
            $data['status'], $data['keluhan'], $data['diagnosis'], $id
        );
        
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
        $edit_id = $_POST['edit_id'];
        $update_data = array(
            'nama' => $_POST['nama'],
            'nis' => $_POST['nis'],
            'kelas' => $_POST['kelas'],
            'suhu' => $_POST['suhu'],
            'status' => $_POST['status'],
            'keluhan' => $_POST['keluhan'],
            'diagnosis' => $_POST['diagnosis']
        );
        
        $updateResult = updateMonitoringData($edit_id, $update_data);
        
        if ($updateResult === true) {
            header("Location: peringatandini.php?message=Data berhasil diupdate");
            exit();
        } else {
            echo "<p style='color: red;'>$updateResult</p>";
        }
    }

    $monitoringData = getMonitoringData();
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistem Peringatan Dini Kesehatan</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        --danger-bg: #fee2e2;
        --danger-color: #dc2626;
        --success-bg: #d1fae5;
        --success-color: #059669;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--secondary-color);
        color: var(--text-color);
        line-height: 1.6;
        overflow-x: hidden;
        min-height: 100vh;
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

    /* Tombol Kembali */
    .btn-back {
        position: fixed;
        top: 100px;
        left: 20px;
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
        z-index: 100;
    }

    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
    }

    /* Responsive untuk mobile */
    @media (max-width: 768px) {
        .btn-back {
            position: fixed;
            top: auto;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
        }

        .btn-back:hover {
            transform: translateX(-50%) translateY(-2px);
        }
    }

    /* Container */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 6rem 2rem 2rem;
    }

    /* Header */
    header {
        text-align: center;
        margin-bottom: 3rem;
        padding-top: 2rem;
    }

    header h1 {
        color: var(--primary-color);
        font-size: 2.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }

    /* Dashboard Cards */
    .dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
        padding: 0 1rem;
    }

    .card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 2.5rem;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .card::before {
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

    .card:hover::before {
        transform: translateX(100%);
    }

    .card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 20px 40px rgba(28, 168, 131, 0.15);
    }

    .card i {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }

    .card:hover i {
        transform: scale(1.1) rotate(-10deg);
        color: var(--accent-color);
    }

    .card h3 {
        color: var(--text-color);
        font-size: 1.5rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .card p {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    /* Alert Container */
    .alert-container {
        display: grid;
        gap: 1.5rem;
        padding: 0 1rem;
        margin-bottom: 2rem;
    }

    .alert {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 2rem;
        transition: all 0.3s ease;
    }

    .alert.severe {
        border-left: 4px solid var(--danger-color);
        background: linear-gradient(to right, var(--danger-bg), var(--glass-bg));
    }

    .alert.healthy {
        border-left: 4px solid var(--success-color);
        background: linear-gradient(to right, var(--success-bg), var(--glass-bg));
    }

    .alert-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--glass-border);
    }

    .patient {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-color);
    }

    .status-badge {
        background: var(--glass-bg);
        padding: 0.5rem 1rem;
        border-radius: 12px;
        font-weight: 500;
        margin-right: 1rem;
    }

    .alert.severe .status-badge {
        background-color: var(--danger-bg);
        color: var(--danger-color);
    }

    .alert.healthy .status-badge {
        background-color: var(--success-bg);
        color: var(--success-color);
    }

    .alert-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        background: rgba(255, 255, 255, 0.5);
        padding: 1rem;
        border-radius: 12px;
    }

    .info-label {
        font-size: 0.9rem;
        color: var(--text-color);
        opacity: 0.7;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    /* Tombol Edit */
    .edit-button {
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.8rem 1.5rem;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .edit-button:hover {
        transform: translateY(-2px);
        background: var(--primary-dark);
        box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        z-index: 1000;
    }

    .modal-content {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 2.5rem;
        width: 90%;
        max-width: 600px;
        margin: 2rem auto;
        position: relative;
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: var(--text-color);
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.8);
        transition: all 0.3s ease;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(28, 168, 131, 0.1);
    }

    /* Responsive Design */
    @media (max-width: 1024px) {    
        .dashboard {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .btn-back {
            top: 80px;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 5rem 1rem 1rem;
        }
        
        header h1 {
            font-size: 2rem;
        }
        
        .alert-header {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .alert-info {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            width: 95%;
            padding: 1.5rem;
            margin: 1rem;
        }

        .btn-back {
            position: fixed;
            top: auto;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 4px 15px rgba(28, 168, 131, 0.3);
        }

        .btn-back:hover {
            transform: translateX(-50%) translateY(-2px);
        }
    }

    .edit-button + .edit-button {
        margin-left: 0.5rem;
    }

    .edit-button i {
        margin-right: 0.5rem;
    }

    @media print {
        .no-print {
            display: none;
        }
    }
        </style>
    </head>
    <body>
        <div class="container">
            <header>
                <h1><i class="fas fa-heartbeat"></i> Sistem Peringatan Dini Kesehatan</h1>
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

            <a href="dashboard.php" class="btn-back">
        <i class="fas fa-arrow-left"></i>
        Kembali ke halaman dashboard
    </a>

            <div class="alert-container">
                <?php foreach ($monitoringData as $data): ?>
                    <div class="alert <?php echo $data['severity']; ?>">
                    <div class="alert-header">
        <span class="patient">
            <strong><?php echo htmlspecialchars($data['nama']); ?></strong>
            (<?php echo htmlspecialchars($data['nis']); ?>)
        </span>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <span class="status-badge"><?php echo htmlspecialchars($data['status']); ?></span>
            <button class="edit-button" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($data)); ?>)">
                <i class="fas fa-edit"></i> Edit
            </button>
            <?php if ($data['status'] === 'Sakit'): ?>
                <button class="edit-button" onclick="printSickLetter(<?php echo $data['id']; ?>)" style="background: #ff6b6b;">
                    <i class="fas fa-file-pdf"></i> Cetak Surat Izin
                </button>
            <?php endif; ?>
        </div>
    </div>

                        <div class="alert-info">
                            <div class="info-item">
                                <span class="info-label">Kelas</span>
                                <?php echo htmlspecialchars($data['kelas']); ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Suhu</span>
                                <?php echo htmlspecialchars($data['suhu']); ?>°C
                            </div>
                            <div class="info-item">
                                <span class="info-label">Keluhan</span>
                                <?php echo htmlspecialchars($data['keluhan']); ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Diagnosis</span>
                                <?php echo htmlspecialchars($data['diagnosis']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Modal Edit -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <h2>Edit Data Kesehatan</h2>
                <form id="editForm" method="POST">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_nama">Nama:</label>
                        <input type="text" id="edit_nama" name="nama" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_nis">NIS:</label>
                        <input type="text" id="edit_nis" name="nis" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_kelas">Kelas:</label>
                        <input type="text" id="edit_kelas" name="kelas" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_suhu">Suhu (°C):</label>
                        <input type="number" step="0.1" id="edit_suhu" name="suhu" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status:</label>
                        <select id="edit_status" name="status" required>
                            <option value="Sehat">Sehat</option>
                            <option value="Sakit">Sakit</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_keluhan">Keluhan:</label>
                        <input type="text" id="edit_keluhan" name="keluhan" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_diagnosis">Diagnosis:</label>
                        <input type="text" id="edit_diagnosis" name="diagnosis" required>
                    </div>
                    <button type="submit" class="edit-button">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <button type="button" class="edit-button" onclick="closeEditModal()" style="background-color: #64748b;">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </form>
            </div>
        </div>

        <script>
            function updateDashboard() {
                const data = <?php echo json_encode($monitoringData); ?>;
                let totalSiswa = data.length;
                let siswaSakit = data.filter(d => d.status === 'Sakit').length;
                let totalSuhu = data.reduce((sum, d) => sum + parseFloat(d.suhu), 0);
                let ratarataSuhu = totalSuhu / totalSiswa;

                document.getElementById('totalSiswa').textContent = totalSiswa;
                document.getElementById('siswaSakit').textContent = siswaSakit;
                document.getElementById('ratarataSuhu').textContent = 
                    isNaN(ratarataSuhu) ? '0°C' : ratarataSuhu.toFixed(1) + '°C';
            }

            function openEditModal(data) {
                const modal = document.getElementById('editModal');
                modal.style.display = 'block';
                
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_nama').value = data.nama;
                document.getElementById('edit_nis').value = data.nis;
                document.getElementById('edit_kelas').value = data.kelas;
                document.getElementById('edit_suhu').value = data.suhu;
                document.getElementById('edit_status').value = data.status;
                document.getElementById('edit_keluhan').value = data.keluhan;
                document.getElementById('edit_diagnosis').value = data.diagnosis;
            }

            function closeEditModal() {
                document.getElementById('editModal').style.display = 'none';
            }

            updateDashboard();
            
            setInterval(function() {
                location.reload();
            }, 60000);


        function printSickLetter(id) {
            window.open('suratsakit_list.php?id=' + id, '_blank');
        }
    </script>
    </body>
    </html>