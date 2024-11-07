<!-- suratsakit_list.php -->
<?php
session_start();
require_once('fpdf/fpdf.php');
$conn = mysqli_connect("localhost", "root", "", "user_database");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Get sick students only
$query = "SELECT * FROM monitoringkesehatan WHERE status = 'Sakit' ORDER BY nama ASC";
$result = mysqli_query($conn, $query);
$sickStudents = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Siswa Sakit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f0f9f6;
            padding: 2rem;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #1ca883;
            color: white;
        }
        .btn-print {
            background-color: #1ca883;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-print:hover {
            background-color: #158a6d;
        }
        .btn-back {
            background-color: #64748b;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="peringatandini.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <h2>Daftar Siswa Sakit</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Suhu</th>
                    <th>Keluhan</th>
                    <th>Diagnosis</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sickStudents as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['nama']); ?></td>
                    <td><?php echo htmlspecialchars($student['kelas']); ?></td>
                    <td><?php echo htmlspecialchars($student['suhu']); ?>°C</td>
                    <td><?php echo htmlspecialchars($student['keluhan']); ?></td>
                    <td><?php echo htmlspecialchars($student['diagnosis']); ?></td>
                    <td>
                        <a href="generate_pdf.php?id=<?php echo $student['id']; ?>" class="btn-print" target="_blank">
                            <i class="fas fa-file-pdf"></i> Cetak Surat
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<!-- generate_pdf.php -->
<?php
session_start();
require_once('fpdf/fpdf.php');
$conn = mysqli_connect("localhost", "root", "", "user_database");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM monitoringkesehatan WHERE id = '$id' AND status = 'Sakit'";
    $result = mysqli_query($conn, $query);
    $student = mysqli_fetch_assoc($result);

    if ($student) {
        class PDF extends FPDF {
            function Header() {
                $this->SetFont('Arial', 'B', 16);
                $this->Cell(0, 10, 'SURAT IZIN SAKIT', 0, 1, 'C');
                $this->Ln(10);
            }
        }

        $pdf = new PDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);

        // Add current date
        $pdf->Cell(0, 10, 'Tanggal: ' . date('d/m/Y'), 0, 1, 'R');
        $pdf->Ln(10);

        // Student details
        $pdf->Cell(40, 10, 'Nama', 0);
        $pdf->Cell(5, 10, ':', 0);
        $pdf->Cell(0, 10, $student['nama'], 0, 1);

        $pdf->Cell(40, 10, 'Kelas', 0);
        $pdf->Cell(5, 10, ':', 0);
        $pdf->Cell(0, 10, $student['kelas'], 0, 1);

        $pdf->Cell(40, 10, 'Suhu', 0);
        $pdf->Cell(5, 10, ':', 0);
        $pdf->Cell(0, 10, $student['suhu'] . '°C', 0, 1);

        $pdf->Cell(40, 10, 'Keluhan', 0);
        $pdf->Cell(5, 10, ':', 0);
        $pdf->Cell(0, 10, $student['keluhan'], 0, 1);

        $pdf->Cell(40, 10, 'Diagnosis', 0);
        $pdf->Cell(5, 10, ':', 0);
        $pdf->Cell(0, 10, $student['diagnosis'], 0, 1);

        $pdf->Ln(10);
        $pdf->MultiCell(0, 10, 'Dengan surat ini, saya memohon izin untuk tidak mengikuti kegiatan belajar mengajar di sekolah selama dalam proses pemulihan. Terima kasih atas perhatian dan pengertiannya.', 0, 'J');

        $pdf->Ln(20);
        $pdf->Cell(0, 10, 'Hormat saya,', 0, 1);
        $pdf->Ln(15);
        $pdf->Cell(0, 10, $student['nama'], 0, 1);

        // Output PDF
        $pdf->Output('Surat_Izin_Sakit_' . $student['nama'] . '.pdf', 'I');
    }
}
?>