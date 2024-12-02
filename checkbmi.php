<?php
session_start();

// Database Configuration
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
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

// Function to get student data
function getStudentData($conn, $search, $type) {
    try {
        if ($type === 'nis') {
            $stmt = $conn->prepare("SELECT nama_lengkap as nama, nis FROM users WHERE nis = ?");
            $stmt->bind_param("s", $search);
        } else {
            $search = "%$search%";
            $stmt = $conn->prepare("SELECT nama_lengkap as nama, nis FROM users WHERE nama_lengkap LIKE ?");
            $stmt->bind_param("s", $search);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode($data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Data tidak ditemukan']);
        }
        $stmt->close();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Handle AJAX requests for student data
if (isset($_GET['action']) && $_GET['action'] === 'getStudent') {
    $db = new Database();
    if (isset($_GET['nis'])) {
        getStudentData($db->conn, $_GET['nis'], 'nis');
        exit;
    } elseif (isset($_GET['nama'])) {
        getStudentData($db->conn, $_GET['nama'], 'nama');
        exit;
    }
}

class BMICalculator {
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
            return [
                "category" => "Berat Badan Kurang",
                "risk" => "Anda berada dalam kategori kekurangan berat badan. Hubungi dokter untuk informasi lebih lanjut tentang pola makan dan gizi yang seimbang."
            ];
        } elseif ($this->bmi >= 18.5 && $this->bmi < 25) {
            return [
                "category" => "Berat Badan Normal",
                "risk" => "Anda berada dalam kategori berat badan yang normal. Pertahankan pola makan sehat dan olahraga teratur."
            ];
        } elseif ($this->bmi >= 25 && $this->bmi < 30) {
            return [
                "category" => "Kelebihan Berat Badan",
                "risk" => "Anda berada dalam kategori kelebihan berat badan. Pertimbangkan untuk menurunkan berat badan dengan diet seimbang dan olahraga."
            ];
        } else {
            return [
                "category" => "Obesitas",
                "risk" => "Anda berada dalam kategori obesitas. Segera konsultasikan dengan dokter untuk mendapatkan penanganan yang tepat."
            ];
        }
    }
    
    public function saveToDatabase($data) {
        $sql = "INSERT INTO bmi_records (nis, nama, berat, tinggi, bmi, kategori, rekomendasi) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("ssddsss",
            $data['nis'],
            $data['nama'],
            $data['berat'],
            $data['tinggi'],
            $data['bmi'],
            $data['kategori'],
            $data['rekomendasi']
        );
        
        return $stmt->execute();
    }
    
    public function getBMIHistory() {
        $sql = "SELECT * FROM bmi_records ORDER BY tanggal_pemeriksaan DESC LIMIT 10";
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getBmi() {
        return $this->bmi;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bmiCalc = new BMICalculator();
    
    if (isset($_POST['weight']) && isset($_POST['height']) && isset($_POST['nis']) && isset($_POST['nama'])) {
        $bmiResult = $bmiCalc->calculateBMI($_POST['weight'], $_POST['height']);
        
        $data = [
            'nis' => $_POST['nis'],
            'nama' => $_POST['nama'],
            'berat' => $_POST['weight'],
            'tinggi' => $_POST['height'],
            'bmi' => $bmiCalc->getBmi(),
            'kategori' => $bmiResult['category'],
            'rekomendasi' => $bmiResult['risk']
        ];
        
        if ($bmiCalc->saveToDatabase($data)) {
            $_SESSION['message'] = "Perhitungan BMI berhasil disimpan!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Gagal menyimpan hasil perhitungan BMI!";
            $_SESSION['message_type'] = 'error';
        }
        
        $_SESSION['bmi_result'] = $bmiResult;
        $_SESSION['bmi_value'] = number_format($bmiCalc->getBmi(), 1);
    }
}

$bmiCalc = new BMICalculator();
$history = $bmiCalc->getBMIHistory();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator BMI</title>
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

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: var(--secondary-color);
        color: var(--primary-color);
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
    
    .btn-back {
    position: fixed;
    top: 20px;
    left: 20px;
    background: var(--primary-color);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 1000;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-back:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}
    
    </style>
</head>
<body>
<a href="dashboard.php" class="btn-back">
    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
</a>
    <div class="container">
        <header>
            <h1>Kalkulator BMI</h1>
        </header>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-section">
                <h2>Data Siswa</h2>
                <input type="text" name="nis" id="nis" placeholder="NIS" required>
                <input type="text" name="nama" id="nama" placeholder="Nama Lengkap" required>
            </div>

            <div class="form-section">
                <h2>Hitung BMI Anda</h2>
                <input type="number" name="weight" placeholder="Berat (kg)" required step="0.1" min="1" max="300">
                <input type="number" name="height" placeholder="Tinggi (cm)" required min="1" max="300">
                <p>BMI = Berat(kg) / (Tinggi(m))²</p>
            </div>

            <button type="submit" class="btn">Hitung BMI</button>
        </form>

        <?php if (isset($_SESSION['bmi_result'])): ?>
            <div class="results">
                <div class="message success">
                    <h3>Hasil BMI Anda: <?php echo $_SESSION['bmi_value']; ?></h3>
                    <p><strong>Kategori:</strong> <?php echo $_SESSION['bmi_result']['category']; ?></p>
                    <p><strong>Rekomendasi:</strong> <?php echo $_SESSION['bmi_result']['risk']; ?></p>
                </div>
            </div>
            <?php 
            unset($_SESSION['bmi_result']);
            unset($_SESSION['bmi_value']);
            ?>
        <?php endif; ?>

        <?php if (!empty($history)): ?>
        <div class="history-section">
            <h2>Riwayat BMI</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Berat (kg)</th>
                            <th>Tinggi (cm)</th>
                            <th>BMI</th>
                            <th>Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($record['tanggal_pemeriksaan'])); ?></td>
                            <td><?php echo $record['nis']; ?></td>
                            <td><?php echo $record['nama']; ?></td>
                            <td><?php echo $record['berat']; ?></td>
                            <td><?php echo $record['tinggi']; ?></td>
                            <td><?php echo number_format($record['bmi'], 1); ?></td>
                            <td><?php echo $record['kategori']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Kalkulator BMI. All rights reserved.</p>
    </footer>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const nisInput = document.getElementById('nis');
    const namaInput = document.getElementById('nama');
    let isUpdating = false;

    async function searchStudent(searchTerm, searchType) {
        if (!searchTerm) return;
        
        try {
            const response = await fetch(`?action=getStudent&${searchType}=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();
            
            if (data && !data.error) {
                if (searchType === 'nis' && data.nama) {
                    namaInput.value = data.nama;
                } else if (searchType === 'nama' && data.nis) {
                    nisInput.value = data.nis;
                }
            } else {
                // Reset field jika data tidak ditemukan
                if (searchType === 'nis') {
                    namaInput.value = '';
                } else {
                    nisInput.value = '';
                }
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    // Debounce function untuk mengurangi jumlah request
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    const debouncedSearch = debounce(searchStudent, 300);

    // Event listener untuk NIS
    nisInput.addEventListener('input', function(e) {
        const value = e.target.value.trim();
        if (value) {
            debouncedSearch(value, 'nis');
        } else {
            namaInput.value = ''; // Reset nama jika NIS kosong
        }
    });

    // Event listener untuk Nama
    namaInput.addEventListener('input', function(e) {
        const value = e.target.value.trim();
        if (value.length >= 3) {
            debouncedSearch(value, 'nama');
        }
    });
});
</script>

    