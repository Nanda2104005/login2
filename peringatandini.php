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
                WHEN m.status = 'Sakit' THEN 'severe'
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

/* Animated Medical Background */
.bg-animation {
    position: fixed;
    width: 100vw;
    height: 100vh;
    top: 0;
    left: 0;
    z-index: -1;
    background: linear-gradient(45deg, rgba(28, 168, 131, 0.1), rgba(255, 107, 107, 0.1));
    background-image: 
        radial-gradient(circle at 20% 20%, rgba(28, 168, 131, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 107, 107, 0.05) 0%, transparent 50%);
}

.container {
    max-width: 100%;
    padding: 2rem;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    height: 100vh;
    overflow: hidden;
}

/* Header Styles */
header {
    grid-column: 1 / -1;
    padding: 1rem 0;
    margin-bottom: 1rem;
    text-align: center; /* Center the header content */
}

header h1 {
    color: var(--primary-color);
    font-size: 2rem;
    font-weight: 700;
    display: inline-flex; /* Change to inline-flex */
    align-items: center;
    gap: 1rem;
    justify-content: center; /* Center the icon and text */
    margin: 0 auto; /* Center the entire header */
}

/* Dashboard Layout */
.dashboard {
    position: sticky;
    top: 0;
    height: fit-content;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding-right: 1rem;
}

.card {
    background: var(--glass-bg);
    border-radius: 20px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(28, 168, 131, 0.15);
}

.card i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.card.healthy i {
    color: var(--success-color);
}

.card.sick i {
    color: var(--danger-color);
}

.card h3 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

.card p {
    font-size: 2rem;
    font-weight: 700;
}

/* Alert Container - Horizontal Scroll */
.alert-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
    overflow-y: auto;
    padding: 1rem;
    max-height: calc(100vh - 150px);
}

.alert {
    background: var(--glass-bg);
    border-radius: 20px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
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
    border-bottom: 1px solid var(--glass-border);
    padding-bottom: 1rem;
}

.patient {
    font-size: 1.1rem;
    font-weight: 600;
}

.status-badge {
    padding: 0.4rem 1rem;
    border-radius: 12px;
    font-weight: 500;
    font-size: 0.9rem;
}

.alert.severe .status-badge {
    background: var(--danger-bg);
    color: var(--danger-color);
}

.alert.healthy .status-badge {
    background: var(--success-bg);
    color: var(--success-color);
}

.alert-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.info-item {
    background: rgba(255, 255, 255, 0.5);
    padding: 0.8rem;
    border-radius: 12px;
}

.info-label {
    font-size: 0.8rem;
    color: var(--text-color);
    opacity: 0.7;
    font-weight: 500;
}

/* Button Styles */
.button-container {
    display: flex;
    gap: 0.5rem;
    flex-wrap: nowrap;
}

.edit-button, .print-button {
    padding: 0.5rem 1rem;
    border-radius: 12px;
    border: none;
    color: white;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.edit-button {
    background: var(--primary-color);
}

.print-button {
    background: var(--accent-color);
}

.edit-button:hover, .print-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Back Button */
.btn-back {
    position: fixed;
    top: 20px;
    left: 20px;
    background: var(--accent-color);
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    z-index: 100;
}

.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: var(--glass-bg);
    border-radius: 20px;
    padding: 2rem;
    width: 90%;
    max-width: 600px;
    margin: 5vh auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    background: white;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .container {
        grid-template-columns: 1fr;
    }
    
    .dashboard {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .alert-container {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        max-height: none;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    
    header h1 {
        font-size: 1.5rem;
    }
    
    .alert-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .button-container {
        justify-content: flex-start;
    }
    
    .btn-back {
        top: auto;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
    }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: var(--secondary-color);
    border-radius: 5px;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}


    </style>
</head>
<body>
    <div class="bg-animation"></div>
    <div class="container">
        <header>
            <h1><i class="fas fa-heartbeat"></i> Sistem Peringatan Dini Kesehatan</h1>
        </header>

        <div class="dashboard">
            <div class="card healthy">
                <i class="fas fa-user-shield"></i>
                <h3>Siswa Sehat</h3>
                <p id="siswaSehat">0</p>
            </div>
            <div class="card sick">
                <i class="fas fa-procedures"></i>
                <h3>Siswa Sakit</h3>
                <p id="siswaSakit">0</p>
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
                        <div class="button-container">
                            <span class="status-badge"><?php echo htmlspecialchars($data['status']); ?></span>
                            <button class="edit-button" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($data)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <?php if ($data['status'] === 'Sakit'): ?>
                                <button class="print-button" onclick="printSuratIzin(<?php echo $data['id']; ?>)">
                                    <i class="fas fa-print"></i> Cetak Surat Izin
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

                <div class="button-container">
                    <button type="submit" class="edit-button">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <button type="button" class="print-button" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateDashboard() {
            const data = <?php echo json_encode($monitoringData); ?>;
            let siswaSehat = data.filter(d => d.status === 'Sehat').length;
            let siswaSakit = data.filter(d => d.status === 'Sakit').length;

            document.getElementById('siswaSehat').textContent = siswaSehat;
            document.getElementById('siswaSakit').textContent = siswaSakit;
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

        function printSuratIzin(id) {
            window.location.href = `generate2_pdf.php?id=${id}`;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Initialize dashboard
        updateDashboard();
        
        // Auto refresh every minute
        setInterval(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>