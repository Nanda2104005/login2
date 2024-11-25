<?php
require('fpdf/fpdf.php');

class SuratIzinPDF extends FPDF {
    function Header() {
        // Add logo if needed
        // $this->Image('p.png', 10, 6, 30);
        
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        
        // Title
        $this->Cell(0, 10, 'SMA MUHAMMADIYAH 3 JEMBER', 0, 1, 'C');
        
        // Address
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Jl. Mastrip No 3 Jember', 0, 1, 'C');
        $this->Cell(0, 5, 'Telp. (0331) 123456', 0, 1, 'C');
        
        // Line break
        $this->Ln(10);
        
        // Add a line
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }
}

function generateSuratIzin($data) {
    $pdf = new SuratIzinPDF();
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'SURAT IZIN SAKIT', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 5, 'Nomor: ' . date('dmY') . '/SK/' . $data['nis'], 0, 1, 'C');
    $pdf->Ln(10);
    
    // Address
    $pdf->Cell(0, 5, 'Kepada Yth.', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Wali Kelas ' . $data['kelas'], 0, 1, 'L');
    $pdf->Cell(0, 5, 'SMA Muhammadiyah 3 Jember', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Di tempat', 0, 1, 'L');
    $pdf->Ln(10);
    
    // Content
    $pdf->Cell(0, 5, 'Yang bertanda tangan di bawah ini:', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Student details
    $pdf->Cell(30, 5, 'Nama', 0, 0, 'L');
    $pdf->Cell(5, 5, ':', 0, 0, 'L');
    $pdf->Cell(0, 5, $data['nama'], 0, 1, 'L');
    
    $pdf->Cell(30, 5, 'NIS', 0, 0, 'L');
    $pdf->Cell(5, 5, ':', 0, 0, 'L');
    $pdf->Cell(0, 5, $data['nis'], 0, 1, 'L');
    
    $pdf->Cell(30, 5, 'Kelas', 0, 0, 'L');
    $pdf->Cell(5, 5, ':', 0, 0, 'L');
    $pdf->Cell(0, 5, $data['kelas'], 0, 1, 'L');
    $pdf->Ln(10);
    
    // Health condition
    $pdf->MultiCell(0, 5, 'Dengan ini memohon izin untuk tidak mengikuti kegiatan belajar-mengajar pada hari ini, dikarenakan kondisi kesehatan yang kurang baik. Siswa yang bersangkutan mengalami gejala sebagai berikut:', 0, 'J');
    $pdf->Ln(5);
    
    // Fixed temperature display by using chr(176) for the degree symbol
    $pdf->Cell(5, 5, '-', 0, 0, 'L');
    $pdf->Cell(40, 5, 'Suhu Tubuh', 0, 0, 'L');
    $pdf->Cell(5, 5, ':', 0, 0, 'L');
    $pdf->Cell(0, 5, $data['suhu'] . chr(176) . 'C', 0, 1, 'L');
    
    $pdf->Cell(5, 5, '-', 0, 0, 'L');
    $pdf->Cell(40, 5, 'Keluhan', 0, 0, 'L');
    $pdf->Cell(5, 5, ':', 0, 0, 'L');
    $pdf->Cell(0, 5, $data['keluhan'], 0, 1, 'L');
    
    $pdf->Cell(5, 5, '-', 0, 0, 'L');
    $pdf->Cell(40, 5, 'Diagnosis', 0, 0, 'L');
    $pdf->Cell(5, 5, ':', 0, 0, 'L');
    $pdf->Cell(0, 5, $data['diagnosis'], 0, 1, 'L');
    $pdf->Ln(10);
    
    // Closing
    $pdf->MultiCell(0, 5, 'Demikian surat izin ini kami buat dengan sebenarnya. Atas perhatian dan pengertiannya, kami ucapkan terima kasih.', 0, 'J');
    $pdf->Ln(10);
    
    // Date
    $pdf->Cell(0, 5, 'Jember, ' . date('d F Y'), 0, 1, 'R');
    $pdf->Ln(5);
    
    // Signature section with two columns
    $pdf->SetFont('Arial', '', 11);
    
    // Left side - UKS Officer signature
    $pdf->Cell(95, 5, 'Mengetahui,', 0, 0, 'C');
    // Right side - Parent signature
    $pdf->Cell(95, 5, 'Hormat Kami,', 0, 1, 'C');
    $pdf->Ln(20);  // Space for signature
    
    // Add dotted line for signatures
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(95, 5, '________________', 0, 0, 'C');
    $pdf->Cell(95, 5, '________________', 0, 1, 'C');
    
    // Add titles under the lines
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(95, 5, '(Petugas UKS)', 0, 0, 'C');
    $pdf->Cell(95, 5, 'Orang Tua/Wali Siswa', 0, 1, 'C');
    
    return $pdf;
}

// Handle PDF generation
if (isset($_GET['id'])) {
    // Database connection
    $conn = mysqli_connect("localhost", "root", "", "user_database");
    
    if (!$conn) {
        die("Koneksi gagal: " . mysqli_connect_error());
    }
    
    // Set character set to UTF-8
    mysqli_set_charset($conn, "utf8");
    
    $id = $_GET['id'];
    
    // Get student data
    $query = "SELECT * FROM monitoringkesehatan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    if ($data && $data['status'] === 'Sakit') {
        $pdf = generateSuratIzin($data);
        $pdf->Output('Surat_Izin_' . $data['nis'] . '.pdf', 'D');
    } else {
        echo "Data tidak ditemukan atau siswa tidak dalam status sakit";
    }
    
    mysqli_close($conn);
}
?>