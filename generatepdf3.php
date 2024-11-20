<?php
// Add at the top of your file
require('fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'Laporan Riwayat Pemeriksaan Kesehatan', 0, 1, 'C');
        $this->Ln(10);

        // Table headers
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(40, 10, 'Tanggal', 1, 0, 'C');
        $this->Cell(60, 10, 'BMI', 1, 0, 'C');
        $this->Cell(50, 10, 'Tekanan Darah', 1, 0, 'C');
        $this->Cell(40, 10, 'Gula Darah', 1, 1, 'C');
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

// Add export function to HealthAnalysis class
public function exportToPDF() {
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage('L', 'A4');
    $pdf->SetFont('Arial', '', 10);

    foreach($this->getAnalysisHistory() as $record) {
        $pdf->Cell(40, 10, date('d/m/Y H:i', strtotime($record['tanggal_pemeriksaan'])), 1, 0, 'C');
        $pdf->Cell(60, 10, number_format($record['bmi'], 1) . ' (' . $record['kategori_bmi'] . ')', 1, 0, 'C');
        $pdf->Cell(50, 10, $record['sistolik'] . '/' . $record['diastolik'] . ' - ' . $record['status_tekanan_darah'], 1, 0, 'C');
        $pdf->Cell(40, 10, $record['gula_darah'] . ' (' . $record['status_gula_darah'] . ')', 1, 1, 'C');
    }

    $pdf->Output('Riwayat_Kesehatan.pdf', 'D');
}

// Add this to handle PDF export
if(isset($_POST['export_pdf'])) {
    $health = new HealthAnalysis();
    $health->exportToPDF();
}