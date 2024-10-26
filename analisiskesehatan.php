<?php
session_start();

// Konfigurasi Database
class Database {
    private $host = "localhost";
    private $username = "root";  // Sesuaikan dengan username database Anda
    private $password = "";      // Sesuaikan dengan password database Anda
    private $database = "user_database";
    public $conn;

    public function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            if ($this->conn->connect_error) {
                throw new Exception("Koneksi database gagal: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}

class HealthAnalysis {
    private $db;
    private $bmi;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function calculateBMI($weight, $height) {
        $heightInMeters = $height / 100;
        $this->bmi = $weight / ($heightInMeters * $heightInMeters);
        return $this->getBMICategory();
    }
    
    private function getBMICategory() {
        if ($this->bmi < 18.5) {
            return ["category" => "Berat Badan Kurang", "risk" => "Risiko kesehatan rendah"];
        } elseif ($this->bmi >= 18.5 && $this->bmi < 25) {
            return ["category" => "Berat Badan Normal", "risk" => "Risiko kesehatan minimal"];
        } elseif ($this->bmi >= 25 && $this->bmi < 30) {
            return ["category" => "Kelebihan Berat Badan", "risk" => "Risiko kesehatan meningkat"];
        } else {
            return ["category" => "Obesitas", "risk" => "Risiko kesehatan tinggi"];
        }
    }
    
    public function analyzeBP($systolic, $diastolic) {
        if ($systolic < 120 && $diastolic < 80) {
            return "Normal";
        } elseif ($systolic >= 120 && $systolic < 130 && $diastolic < 80) {
            return "Pre-hipertensi";
        } else {
            return "Hipertensi";
        }
    }
    
    public function analyzeBloodSugar($value, $type) {
        if ($type === "puasa") {
            if ($value < 100) return "Normal";
            elseif ($value >= 100 && $value < 126) return "Pra-diabetes";
            else return "Diabetes";
        } else {
            if ($value < 140) return "Normal";
            elseif ($value >= 140 && $value < 200) return "Pra-diabetes";
            else return "Diabetes";
        }
    }
    
    public function saveToDatabase($data) {
        $sql = "INSERT INTO analisiskesehatan (
            berat, tinggi, bmi, kategori_bmi, risiko_bmi, 
            sistolik, diastolik, status_tekanan_darah, 
            gula_darah, tipe_gula_darah, status_gula_darah
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param(
            "didssiiisis",
            $data['berat'],
            $data['tinggi'],
            $data['bmi'],
            $data['kategori_bmi'],
            $data['risiko_bmi'],
            $data['sistolik'],
            $data['diastolik'],
            $data['status_tekanan_darah'],
            $data['gula_darah'],
            $data['tipe_gula_darah'],
            $data['status_gula_darah']
        );
        
        return $stmt->execute();
    }
    
    public function getAnalysisHistory() {
        $sql = "SELECT * FROM analisiskesehatan ORDER BY tanggal_pemeriksaan DESC LIMIT 10";
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getBmi() {
        return $this->bmi;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $health = new HealthAnalysis();
    $result = [];
    
    // Calculate all health metrics
    if (isset($_POST['weight']) && isset($_POST['height'])) {
        $bmiResult = $health->calculateBMI($_POST['weight'], $_POST['height']);
        $result['bmi'] = $bmiResult;
    }
    
    if (isset($_POST['systolic']) && isset($_POST['diastolic'])) {
        $bpResult = $health->analyzeBP($_POST['systolic'], $_POST['diastolic']);
        $result['bp'] = $bpResult;
    }
    
    if (isset($_POST['blood_sugar']) && isset($_POST['sugar_type'])) {
        $sugarResult = $health->analyzeBloodSugar($_POST['blood_sugar'], $_POST['sugar_type']);
        $result['sugar'] = $sugarResult;
    }
    
    // Prepare data for database
    $data = [
        'berat' => $_POST['weight'],
        'tinggi' => $_POST['height'],
        'bmi' => $health->getBmi(),
        'kategori_bmi' => $bmiResult['category'],
        'risiko_bmi' => $bmiResult['risk'],
        'sistolik' => $_POST['systolic'],
        'diastolik' => $_POST['diastolic'],
        'status_tekanan_darah' => $bpResult,
        'gula_darah' => $_POST['blood_sugar'],
        'tipe_gula_darah' => $_POST['sugar_type'],
        'status_gula_darah' => $sugarResult
    ];
    
    // Save to database
    if ($health->saveToDatabase($data)) {
        $_SESSION['message'] = "Data berhasil disimpan!";
        $result['save_status'] = true;
    } else {
        $_SESSION['message'] = "Gagal menyimpan data!";
        $result['save_status'] = false;
    }
    
    $_SESSION['health_results'] = $result;
}

// Get analysis history
$health = new HealthAnalysis();
$history = $health->getAnalysisHistory();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Analisis Kesehatan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="health-style.css">
</head>
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

h2 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: var(--secondary-color);
    border-radius: 15px;
}

input[type="number"],
input[type="text"],
select {
    width: 100%;
    padding: 0.8rem;
    margin-bottom: 1rem;
    border: 1px solid #ddd;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
}

select {
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
    display: block;
    width: 100%;
    max-width: 200px;
    margin: 2rem auto;
    text-align: center;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

.results {
    margin-top: 2rem;
}

.message {
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 10px;
    background-color: var(--secondary-color);
}

.message h3 {
    color: var(--primary-color);
    margin-top: 0;
}

.success {
    border-left: 5px solid var(--primary-color);
}

.error {
    background-color: #fde2e2;
    color: var(--danger-color);
    border-left: 5px solid var(--danger-color);
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
        margin: 1rem;
        padding: 1rem;
    }
    
    h1 {
        font-size: 2rem;
    }
    
    .form-section {
        padding: 1rem;
    }
}
</style>
<body>
    <div class="container">
        <header>
            <h1>Sistem Analisis Kesehatan</h1>
        </header>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo isset($_SESSION['health_results']['save_status']) && $_SESSION['health_results']['save_status'] ? 'success' : 'error'; ?>">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-section">
                <h2>Kalkulator BMI</h2>
                <input type="number" name="weight" placeholder="Berat (kg)" required step="0.1">
                <input type="number" name="height" placeholder="Tinggi (cm)" required>
            </div>

            <div class="form-section">
                <h2>Tekanan Darah</h2>
                <input type="number" name="systolic" placeholder="Sistolik (mmHg)" required>
                <input type="number" name="diastolic" placeholder="Diastolik (mmHg)" required>
            </div>

            <div class="form-section">
                <h2>Gula Darah</h2>
                <input type="number" name="blood_sugar" placeholder="Nilai Gula Darah (mg/dL)" required>
                <select name="sugar_type" required>
                    <option value="puasa">Puasa</option>
                    <option value="sewaktu">Sewaktu</option>
                </select>
            </div>

            <button type="submit" class="btn">Analisis Kesehatan</button>
        </form>

        <?php if (isset($_SESSION['health_results'])): ?>
            <div class="results">
                <?php foreach ($_SESSION['health_results'] as $type => $value): ?>
                    <?php if ($type !== 'save_status'): ?>
                        <div class="message success">
                            <?php if ($type === 'bmi'): ?>
                                <h3>Hasil BMI:</h3>
                                <p>Kategori: <?php echo $value['category']; ?></p>
                                <p>Risiko: <?php echo $value['risk']; ?></p>
                            <?php elseif ($type === 'bp'): ?>
                                <h3>Tekanan Darah:</h3>
                                <p>Status: <?php echo $value; ?></p>
                            <?php elseif ($type === 'sugar'): ?>
                                <h3>Gula Darah:</h3>
                                <p>Status: <?php echo $value; ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($history)): ?>
        <div class="history-section">
            <h2>Riwayat Pemeriksaan Terakhir</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>BMI</th>
                            <th>Tekanan Darah</th>
                            <th>Gula Darah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($record['tanggal_pemeriksaan'])); ?></td>
                            <td><?php echo number_format($record['bmi'], 1) . ' (' . $record['kategori_bmi'] . ')'; ?></td>
                            <td><?php echo $record['sistolik'] . '/' . $record['diastolik'] . ' - ' . $record['status_tekanan_darah']; ?></td>
                            <td><?php echo $record['gula_darah'] . ' (' . $record['status_gula_darah'] . ')'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Sistem Analisis Kesehatan. All rights reserved.</p>
    </footer>
</body>
</html>