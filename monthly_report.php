<?php
require_once 'login/auth.php';
require_once 'config.php';

requireLogin();
$pdo = getDBConnection();
$user_id = getCurrentUserId();
$current_lang = $_GET['lang'] ?? 'it';

// Traduzioni (copia da index.php)
$translations = [
    'it' => [
        'page_title' => 'Resoconto Mensile',
        'monthly_summary' => 'Riepilogo Mensile',
        'monthly_earnings' => 'Guadagni Mensili',
        'gross_salary' => 'Lordo',
        'net_salary' => 'Netto',
        'hours' => 'Ore',
        'company' => 'Azienda',
        'companies_list' => 'Elenco Aziende',
        'overtime_type' => 'Tipo Straordinario',
        'weekday' => 'Feriale (+15%)',
        'holiday' => 'Festivo (+30%)',
        'night' => 'Notturno (+50%)',
        'no_data_month' => 'Nessun dato disponibile per questo mese.',
        'back_to_main' => 'Torna al Menu Principale',
        'export_excel' => 'Esporta Excel',
        'select_month' => 'Seleziona Mese',
    ],
    'en' => [
        'page_title' => 'Monthly Report',
        'monthly_summary' => 'Monthly Summary',
        'monthly_earnings' => 'Monthly Earnings',
        'gross_salary' => 'Gross Salary',
        'net_salary' => 'Net Salary',
        'hours' => 'Hours',
        'company' => 'Company',
        'companies_list' => 'Companies List',
        'overtime_type' => 'Overtime Type',
        'weekday' => 'Weekday (+15%)',
        'holiday' => 'Holiday (+30%)',
        'night' => 'Night (+50%)',
        'no_data_month' => 'No data available for this month.',
        'back_to_main' => 'Back to Main Menu',
        'export_excel' => 'Export Excel',
        'select_month' => 'Select Month',
    ]
];
function t($key, $lang = 'it') {
    global $translations;
    return $translations[$lang][$key] ?? $key;
}

define('PAGA_ORARIA_BASE', 9.72226);
define('ALIQUOTA_TRATTENUTE', 0.206);
$maggiorazioni = [
    'feriale' => 0.15,
    'festivo' => 0.30,
    'notturno' => 0.50
];
function calcolaGuadagnoLordo($ore, $tipo_straordinario) {
    global $maggiorazioni;
    $maggiorazione = $maggiorazioni[$tipo_straordinario] ?? 0.15;
    $paga_oraria_straordinario = PAGA_ORARIA_BASE * (1 + $maggiorazione);
    $lordo_straordinario = $paga_oraria_straordinario * $ore;
    return $lordo_straordinario;
}
function calcolaGuadagnoNetto($guadagno_lordo) {
    return $guadagno_lordo * (1 - ALIQUOTA_TRATTENUTE);
}

// Recupera aziende
$companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();
$company_colors = [];
foreach ($companies as $company) {
    $company_colors[$company['id']] = $company['color'] ?? '#6c757d';
}

// Recupera dati mensili
$current_month = $_GET['month'] ?? date('Y-m');
$stmt = $pdo->prepare("
    SELECT c.id as company_id, c.name as company_name, c.color as company_color, 
           eh.tipo_straordinario, 
           SUM(eh.hours) as total_hours
    FROM extra_hours eh 
    JOIN companies c ON eh.company_id = c.id 
    WHERE DATE_FORMAT(eh.date, '%Y-%m') = ?
    AND eh.user_id = ?
    GROUP BY c.id, c.name, c.color, eh.tipo_straordinario
    ORDER BY c.name, eh.tipo_straordinario
");
$stmt->execute([$current_month, $user_id]);
$monthly_data = $stmt->fetchAll();

$total_guadagno_lordo = 0;
$total_guadagno_netto = 0;
$monthly_summary = [];
foreach ($monthly_data as $data) {
    $company_id = $data['company_id'];
    $company_name = $data['company_name'];
    $company_color = $data['company_color'];
    $tipo_straordinario = $data['tipo_straordinario'];
    $ore = $data['total_hours'];
    $guadagno_lordo = calcolaGuadagnoLordo($ore, $tipo_straordinario);
    $guadagno_netto = calcolaGuadagnoNetto($guadagno_lordo);
    $total_guadagno_lordo += $guadagno_lordo;
    $total_guadagno_netto += $guadagno_netto;
    if (!isset($monthly_summary[$company_id])) {
        $monthly_summary[$company_id] = [
            'company_name' => $company_name,
            'company_color' => $company_color,
            'total_hours' => 0,
            'total_lordo' => 0,
            'total_netto' => 0,
            'details' => []
        ];
    }
    $monthly_summary[$company_id]['total_hours'] += $ore;
    $monthly_summary[$company_id]['total_lordo'] += $guadagno_lordo;
    $monthly_summary[$company_id]['total_netto'] += $guadagno_netto;
    $monthly_summary[$company_id]['details'][] = [
        'tipo' => $tipo_straordinario,
        'ore' => $ore,
        'lordo' => $guadagno_lordo,
        'netto' => $guadagno_netto
    ];
}
uasort($monthly_summary, function($a, $b) {
    return $b['total_lordo'] <=> $a['total_lordo'];
});

?><!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('page_title', $current_lang) ?></title>
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="logo">
                <img src="images/logo.svg" alt="Logo" class="logo-img me-2" width="32" height="32">
                <strong><?= t('page_title', $current_lang) ?></strong>
            </div>
            <div class="d-flex align-items-center">
                <a href="index.php?lang=<?= $current_lang ?>" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-home me-1"></i>
                    <?= t('back_to_main', $current_lang) ?>
                </a>
                <a href="?lang=<?= $current_lang === 'it' ? 'en' : 'it' ?>" class="btn language-selector me-2">
                    <i class="fas fa-globe me-1"></i>
                    <?= $current_lang === 'it' ? '🇺🇸' : '🇮🇹' ?>
                </a>
                <a href="login/logout.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="card slide-in-left h-100">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>
                        <?= t('monthly_summary', $current_lang) ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthly_summary)): ?>
                            <p class="text-muted"><?= t('no_data_month', $current_lang) ?></p>
                        <?php else: ?>
                            <!-- Totali mensili generali -->
                            <div class="row mb-4">
                                <div class="col-12 col-md-6 col-lg-4 mx-auto">
                                    <div class="card bg-dark shadow border-0 sticky-top">
                                        <div class="card-body text-center">
                                            <h4 class="card-title text-primary mb-3">
                                                <i class="fas fa-calculator me-2"></i>
                                                <?= t('monthly_earnings', $current_lang) ?>
                                            </h4>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <h3 class="text-success mb-0">€<?= number_format($total_guadagno_lordo, 2) ?></h3>
                                                    <small class="text-muted"><?= t('gross_salary', $current_lang) ?></small>
                                                </div>
                                                <div class="col-6">
                                                    <h3 class="text-primary mb-0">€<?= number_format($total_guadagno_netto, 2) ?></h3>
                                                    <small class="text-muted"><?= t('net_salary', $current_lang) ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dettaglio per azienda -->
                            <div class="row g-4">
                                <?php foreach ($monthly_summary as $summary): ?>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card shadow h-100 border-0">
                                            <div class="card-header text-white" style="background: <?= htmlspecialchars($summary['company_color']) ?>;">
                                                <strong><?= htmlspecialchars($summary['company_name']) ?></strong>
                                            </div>
                                            <div class="card-body text-white">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span><i class="fas fa-clock me-1"></i> <?= $summary['total_hours'] ?> <?= t('hours', $current_lang) ?></span>
                                                    <span class="text-success">€<?= number_format($summary['total_lordo'], 2) ?> <small><?= t('gross_salary', $current_lang) ?></small></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span><i class="fas fa-wallet me-1"></i> <?= t('net_salary', $current_lang )?></span>
                                                    <span class="text-primary">€<?= number_format($summary['total_netto'], 2) ?></span>
                                                </div>
                                                <hr>
                                                <div class="mb-2 fw-bold text-muted">Dettaglio:</div>
                                                <?php foreach ($summary['details'] as $detail): ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <?php
                                                        switch ($detail['tipo']) {
                                                            case 'feriale':
                                                                $badgeClass = 'bg-primary';
                                                                break;
                                                            case 'festivo':
                                                                $badgeClass = 'bg-warning text-dark';
                                                                break;
                                                            case 'notturno': 'bg-purple';
                                                                break;
                                                            default: 
                                                                $badgeClass = 'bg-secomndary';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $badgeClass ?> me-1" title="<?= t($detail['tipo'] === 'feriale' ? 'weekday' : ($detail['tipo'] === 'festivo' ? 'holiday' : 'night'), $current_lang) ?>">
                                                            <?= t($detail['tipo'] === 'feriale' ? 'weekday' : ($detail['tipo'] === 'festivo' ? 'holiday' : 'night'), $current_lang) ?>
                                                        </span>
                                                        <span><?= $detail['ore'] ?> <?= t('hours', $current_lang) ?></span>
                                                        <span class="text-success">€<?= number_format($detail['netto'], 2) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="mb-3 text-end">
                                        <a href="export.php" class="btn btn-export">
                                            <i class="fas fa-file-excel me-2"></i><?= t('export_excel', $current_lang) ?>
                                        </a>
                                    </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Aggiunge la classe 'animate' quando gli elementi con classi di animazione entrano in view
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate');
                    }
                });
            }, observerOptions);
            document.querySelectorAll('.fade-in-up, .slide-in-left, .slide-in-right, .scale-in').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html> 