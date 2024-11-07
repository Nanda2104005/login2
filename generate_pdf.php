<?php
// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definisikan path yang benar ke fpdf
define('FPDF_FONTPATH', __DIR__ . '/fpdf/font/');

// Include FPDF
require_once(__DIR__ . '/fpdf/fpdf.php');

// Koneksi database
$conn = new mysqli("localhost", "root", "", "user_database");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,'REKAP DATA REKAM KESEHATAN',0,1,'C');
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo(),0,0,'C');
    }
}

// Terima parameter periode
$periode = isset($_POST['periode']) ? $_POST['periode'] : 'semua';
$tanggal_mulai = isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
$tanggal_selesai = isset($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;

// Build query berdasarkan periode
$query = "SELECT * FROM rekam_kesehatan WHERE 1=1";

switch($periode) {
    case 'hari':
        $query .= " AND DATE(tanggal) = CURDATE()";
        break;
    case 'minggu':
        $query .= " AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'bulan':
        $query .= " AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
        break;
    case 'tahun':
        $query .= " AND YEAR(tanggal) = YEAR(CURDATE())";
        break;
    case 'custom':
        if($tanggal_mulai && $tanggal_selesai) {
            $query .= " AND tanggal BETWEEN '$tanggal_mulai' AND '$tanggal_selesai'";
        }
        break;
}

$query .= " ORDER BY tanggal DESC";
$result = $conn->query($query);

if (!$result) {
    die("Error in query: " . $conn->error);
}

try {
    // Buat PDF
    $pdf = new PDF('L','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','',10);

    // Header Tabel
    $pdf->SetFillColor(28, 168, 131);
    $pdf->SetTextColor(255);
    $pdf->SetFont('Arial','B',10);

    $pdf->Cell(30,7,'Tanggal',1,0,'C',true);
    $pdf->Cell(40,7,'Nama',1,0,'C',true);
    $pdf->Cell(25,7,'NIS',1,0,'C',true);
    $pdf->Cell(60,7,'Keluhan',1,0,'C',true);
    $pdf->Cell(60,7,'Diagnosis',1,0,'C',true);
    $pdf->Cell(60,7,'Pertolongan Pertama',1,1,'C',true);

    // Isi Tabel
    $pdf->SetFillColor(245,245,245);
    $pdf->SetTextColor(0);
    $pdf->SetFont('Arial','',9);

    $fill = false;
    while($row = $result->fetch_assoc()) {
        $pdf->Cell(30,6,date('d/m/Y', strtotime($row['tanggal'])),1,0,'C',$fill);
        $pdf->Cell(40,6,$row['nama'],1,0,'L',$fill);
        $pdf->Cell(25,6,$row['nis'],1,0,'C',$fill);
        $pdf->Cell(60,6,$row['keluhan'],1,0,'L',$fill);
        $pdf->Cell(60,6,$row['diagnosis'],1,0,'L',$fill);
        $pdf->Cell(60,6,$row['Pertolongan_Pertama'],1,1,'L',$fill);
        $fill = !$fill;
    }

    // Output PDF
    $pdf->Output('I', 'Rekap_Rekam_Kesehatan.pdf');
} catch (Exception $e) {
    die("Error creating PDF: " . $e->getMessage());
}

// Tutup koneksi
$conn->close();
?>