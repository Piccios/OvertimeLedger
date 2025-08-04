<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'vendor/autoload.php';
require_once 'config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$pdo = getDBConnection();

// Recupera mese dal parametro URL o usa mese corrente
$current_month = $_GET['month'] ?? date('Y-m');

$stmt = $pdo->prepare("
    SELECT c.name as company_name, c.color as company_color, eh.date, eh.hours, eh.description
    FROM extra_hours eh
    JOIN companies c ON eh.company_id = c.id
    WHERE DATE_FORMAT(eh.date, '%Y-%m') = ?
    ORDER BY c.name, eh.date
");
$stmt->execute([$current_month]);
$records = $stmt->fetchAll();

// Crea foglio Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Straordinari Mese');

// Header
$headers = ['Azienda', 'Data', 'Ore', 'Descrizione'];
$sheet->fromArray($headers, null, 'A1');
$sheet->getStyle('A1:D1')->getFont()->setBold(true);
$sheet->getStyle('A1:D1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('39ff14');

// Scrivi dati e colora righe
$row = 2;
$company_totals = [];
foreach ($records as $rec) {
    $sheet->setCellValue("A$row", $rec['company_name']);
    $sheet->setCellValue("B$row", date('d/m/Y', strtotime($rec['date'])));
    $sheet->setCellValue("C$row", $rec['hours']);
    $sheet->setCellValue("D$row", $rec['description']);
    // Colora la riga con il colore dell'azienda
    $sheet->getStyle("A$row:D$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ltrim($rec['company_color'], '#'));
    // Somma ore per azienda
    if (!isset($company_totals[$rec['company_name']])) {
        $company_totals[$rec['company_name']] = 0;
    }
    $company_totals[$rec['company_name']] += $rec['hours'];
    $row++;
}

// Riga vuota
$row++;
$sheet->setCellValue("A$row", 'Totali per azienda:');
$sheet->getStyle("A$row")->getFont()->setBold(true);
$row++;
$total_generale = 0;
foreach ($company_totals as $company => $tot) {
    $sheet->setCellValue("A$row", $company);
    $sheet->setCellValue("C$row", $tot);
    $sheet->getStyle("A$row:C$row")->getFont()->setBold(true);
    $total_generale += $tot;
    $row++;
}
// Totale generale
$sheet->setCellValue("A$row", 'Totale generale:');
$sheet->setCellValue("C$row", $total_generale);
$sheet->getStyle("A$row:C$row")->getFont()->setBold(true)->getColor()->setRGB('bc13fe');

// Autosize colonne
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output
$filename = 'straordinari_' . $current_month . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
ob_end_clean(); // Pulisce tutto l'output buffer PRIMA di inviare il file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
flush();
exit; 