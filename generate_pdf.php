<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('FPDF_FONTPATH', __DIR__ . '/fpdf/font/');
require_once(__DIR__ . '/fpdf/fpdf.php');

class PDF extends FPDF {
    protected $widths;
    protected $aligns;

    function SetWidths($w) {
        $this->widths = $w;
    }

    function SetAligns($a) {
        $this->aligns = $a;
    }

    function Row($data) {
        $nb = 0;
        for($i=0; $i<count($data); $i++)
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $h = 6*$nb;
        $this->CheckPageBreak($h);
        
        for($i=0; $i<count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 6, $data[$i], 0, $a);
            $this->SetXY($x+$w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r",'',$txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i<$nb) {
            $c = $s[$i];
            if($c=="\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep = $i;
            $l += isset($cw[ord($c)]) ? $cw[ord($c)] : 0;
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j)
                        $i++;
                }
                else
                    $i = $sep+1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }

    function Header() {
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,'REKAP DATA REKAM KESEHATAN',0,1,'C');
        $this->Ln(5);

        // Header Tabel
        $this->SetFont('Arial','B',10);
        $this->SetFillColor(28, 168, 131);
        $this->SetTextColor(255);
        
        $this->Cell(30,7,'Tanggal',1,0,'C',true);
        $this->Cell(50,7,'Nama',1,0,'C',true);
        $this->Cell(30,7,'NIS',1,0,'C',true);
        $this->Cell(60,7,'Keluhan',1,0,'C',true);
        $this->Cell(60,7,'Diagnosis',1,0,'C',true);
        $this->Cell(45,7,'Pertolongan',1,1,'C',true);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo(),0,0,'C');
    }
}

$conn = new mysqli("localhost", "root", "", "user_database");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$periode = isset($_POST['periode']) ? $_POST['periode'] : 'semua';
$tanggal_mulai = isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
$tanggal_selesai = isset($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;

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
    $pdf = new PDF('L','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','',9);
    
    // Set lebar kolom
    $pdf->SetWidths(array(30, 50, 30, 60, 60, 45));
    // Set alignment
    $pdf->SetAligns(array('C', 'L', 'C', 'L', 'L', 'L'));
    
    $pdf->SetFillColor(245,245,245);
    $pdf->SetTextColor(0);

    $fill = false;
    while($row = $result->fetch_assoc()) {
        $pdf->Row(array(
            date('d/m/Y', strtotime($row['tanggal'])),
            $row['nama'],
            $row['nis'],
            $row['keluhan'],
            $row['diagnosis'],
            $row['Pertolongan_Pertama']
        ));
        $fill = !$fill;
    }

    $pdf->Output('I', 'Rekap_Rekam_Kesehatan.pdf');
} catch (Exception $e) {
    die("Error creating PDF: " . $e->getMessage());
}

$conn->close();
?>