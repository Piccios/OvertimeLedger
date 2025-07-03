<?php
require_once 'config.php';

// Database connection
$pdo = getDBConnection();

// Current month data
$current_month = date('Y-m');
$stmt = $pdo->prepare("
    SELECT 
        eh.date,
        c.name as company_name,
        eh.hours,
        eh.description,
        eh.created_at
    FROM extra_hours eh 
    JOIN companies c ON eh.company_id = c.id 
    WHERE DATE_FORMAT(eh.date, '%Y-%m') = ?
    ORDER BY eh.date ASC, c.name ASC
");
$stmt->execute([$current_month]);
$monthly_data = $stmt->fetchAll();

// Monthly summary by company
$stmt = $pdo->prepare("
    SELECT c.name as company_name, SUM(eh.hours) as total_hours
    FROM extra_hours eh 
    JOIN companies c ON eh.company_id = c.id 
    WHERE DATE_FORMAT(eh.date, '%Y-%m') = ?
    GROUP BY c.id, c.name
    ORDER BY total_hours DESC
");
$stmt->execute([$current_month]);
$monthly_summary = $stmt->fetchAll();

// Italian months
$italian_months = [
    'January' => 'Gennaio', 'February' => 'Febbraio', 'March' => 'Marzo',
    'April' => 'Aprile', 'May' => 'Maggio', 'June' => 'Giugno',
    'July' => 'Luglio', 'August' => 'Agosto', 'September' => 'Settembre',
    'October' => 'Ottobre', 'November' => 'Novembre', 'December' => 'Dicembre'
];

$current_month_name = $italian_months[date('F')];
$current_year = date('Y');

// Create HTML file that will be interpreted by Excel
$html_content = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background-color: #667eea; color: white; text-align: center; padding: 10px; font-size: 18px; font-weight: bold; }
        .subheader { background-color: #f0f0f0; padding: 8px; font-size: 14px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { background-color: #667eea; color: white; padding: 8px; border: 1px solid #ccc; font-weight: bold; }
        td { padding: 6px; border: 1px solid #ccc; }
        .total-row { background-color: #f0f0f0; font-weight: bold; }
        .summary-header { background-color: #e6e6fa; padding: 8px; font-weight: bold; margin-top: 20px; }
        .company-defenda { background-color: #1e3a8a; color: white; }
        .company-euroansa { background-color: #3b82f6; color: white; }
        .company-ilv { background-color: #f59e0b; color: white; }
    </style>
</head>
<body>
    <div class="header">REPORT ORE STRAORDINARIE</div>
    <div class="subheader">' . $current_month_name . ' ' . $current_year . '</div>
    
    <table>
        <tr>
            <th>Data</th>
            <th>Azienda</th>
            <th>Ore</th>
            <th>Descrizione</th>
            <th>Data Registrazione</th>
        </tr>';

$total_hours = 0;
foreach ($monthly_data as $record) {
    $company_class = '';
    switch(strtolower($record['company_name'])) {
        case 'defenda':
            $company_class = 'company-defenda';
            break;
        case 'euroansa':
            $company_class = 'company-euroansa';
            break;
        case 'italian luxury villas':
            $company_class = 'company-ilv';
            break;
    }
    
    $html_content .= '
        <tr>
            <td>' . date('d/m/Y', strtotime($record['date'])) . '</td>
            <td class="' . $company_class . '">' . htmlspecialchars($record['company_name']) . '</td>
            <td style="text-align: center;">' . $record['hours'] . '</td>
            <td>' . htmlspecialchars($record['description'] ?: '-') . '</td>
            <td>' . date('d/m/Y H:i', strtotime($record['created_at'])) . '</td>
        </tr>';
    $total_hours += $record['hours'];
}

$html_content .= '
        <tr class="total-row">
            <td colspan="2"><strong>TOTALE</strong></td>
            <td style="text-align: center;"><strong>' . $total_hours . '</strong></td>
            <td colspan="2"></td>
        </tr>
    </table>
    
    <div class="summary-header">RIEPILOGO PER AZIENDA</div>
    <table>
        <tr>
            <th>Azienda</th>
            <th>Ore Totali</th>
            <th>Percentuale</th>
        </tr>';

foreach ($monthly_summary as $summary) {
    $percentage = round(($summary['total_hours'] / $total_hours) * 100, 1);
    $company_class = '';
    switch(strtolower($summary['company_name'])) {
        case 'defenda':
            $company_class = 'company-defenda';
            break;
        case 'euroansa':
            $company_class = 'company-euroansa';
            break;
        case 'italian luxury villas':
            $company_class = 'company-ilv';
            break;
    }
    
    $html_content .= '
        <tr>
            <td class="' . $company_class . '">' . htmlspecialchars($summary['company_name']) . '</td>
            <td style="text-align: center;">' . $summary['total_hours'] . '</td>
            <td style="text-align: center;">' . $percentage . '%</td>
        </tr>';
}

$html_content .= '
    </table>
</body>
</html>';

// Set headers for Excel file download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Ore_Straordinarie_' . $current_month_name . '_' . $current_year . '.xls"');
header('Cache-Control: max-age=0');

// Output HTML content that Excel will interpret
echo $html_content;
exit;
?> 