<?php
// Debug temporaneo per identificare problemi
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'security_headers.php';
require_once 'config.php';
require_once 'login/auth.php';

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
        'edit_records' => 'Modifica Record',
        'date' => 'Data',
        'description' => 'Descrizione',
        'actions' => 'Azioni',
        'edit' => 'Modifica',
        'delete' => 'Elimina',
        'confirm_delete' => 'Sei sicuro di voler eliminare questo record?',
        'edit_record' => 'Modifica Record',
        'cancel' => 'Annulla',
        'save_changes' => 'Salva Modifiche',
        'record_edited' => 'Record modificato con successo!',
        'record_deleted' => 'Record eliminato con successo!',
        'validation_error' => 'Errore di validazione. Controlla i dati inseriti.',
        'no_records_month' => 'Nessun record trovato per questo mese.',
        'show_details' => 'Mostra Dettagli',
        'hide_details' => 'Nascondi Dettagli',
        'filter_records' => 'Filtra Record',
        'filter_by_company' => 'Filtra per Azienda',
        'filter_by_type' => 'Filtra per Tipo',
        'filter_by_date' => 'Filtra per Data',
        'all_companies' => 'Tutte le Aziende',
        'all_types' => 'Tutti i Tipi',
        'all_dates' => 'Tutte le Date',
        'apply_filters' => 'Applica Filtri',
        'clear_filters' => 'Pulisci Filtri',
        'filtered_records' => 'Record Filtrati',
        'no_filtered_records' => 'Nessun record trovato con i filtri applicati.',
        'loading' => 'Caricamento...',
        'filter_error' => 'Errore durante l\'applicazione dei filtri. Riprova.',
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
        'edit_records' => 'Edit Records',
        'date' => 'Date',
        'description' => 'Description',
        'actions' => 'Actions',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'confirm_delete' => 'Are you sure you want to delete this record?',
        'edit_record' => 'Edit Record',
        'cancel' => 'Cancel',
        'save_changes' => 'Save Changes',
        'record_edited' => 'Record edited successfully!',
        'record_deleted' => 'Record deleted successfully!',
        'validation_error' => 'Validation error. Please check the entered data.',
        'no_records_month' => 'No records found for this month.',
        'show_details' => 'Show Details',
        'hide_details' => 'Hide Details',
        'filter_records' => 'Filter Records',
        'filter_by_company' => 'Filter by Company',
        'filter_by_type' => 'Filter by Type',
        'filter_by_date' => 'Filter by Date',
        'all_companies' => 'All Companies',
        'all_types' => 'All Types',
        'all_dates' => 'All Dates',
        'apply_filters' => 'Apply Filters',
        'clear_filters' => 'Clear Filters',
        'filtered_records' => 'Filtered Records',
        'no_filtered_records' => 'No records found with applied filters.',
        'loading' => 'Loading...',
        'filter_error' => 'Error applying filters. Please try again.',
    ]
];
function t($key, $lang = 'it') {
    global $translations;
    return $translations[$lang][$key] ?? $key;
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

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

// Gestione delle richieste POST per modifica/cancellazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'security_error';
        header('Location: monthly_report.php?month=' . $current_month . '&lang=' . $current_lang);
        exit;
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit':
                $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
                $company_id = filter_var($_POST['company_id'], FILTER_VALIDATE_INT);
                $date = sanitizeInput($_POST['date']);
                $hours = filter_var($_POST['hours'], FILTER_VALIDATE_FLOAT);
                $description = sanitizeInput($_POST['description'] ?? '');
                $tipo_straordinario = sanitizeInput($_POST['tipo_straordinario'] ?? 'feriale');
                
                // Validazione
                if (!$id || !$company_id || !$date || !$hours || $hours <= 0 || $hours > 24) {
                    $_SESSION['flash_message'] = 'validation_error';
                    header('Location: monthly_report.php?month=' . $current_month . '&lang=' . $current_lang);
                    exit;
                }
                
                // Verifica che la data sia valida
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $_SESSION['flash_message'] = 'validation_error';
                    header('Location: monthly_report.php?month=' . $current_month . '&lang=' . $current_lang);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE extra_hours SET company_id = ?, date = ?, hours = ?, description = ?, tipo_straordinario = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$company_id, $date, $hours, $description, $tipo_straordinario, $id, $user_id]);
                
                $_SESSION['flash_message'] = 'record_edited';
                header('Location: monthly_report.php?month=' . $current_month . '&lang=' . $current_lang);
                exit;
                
            case 'delete':
                $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
                
                if (!$id) {
                    $_SESSION['flash_message'] = 'validation_error';
                    header('Location: monthly_report.php?month=' . $current_month . '&lang=' . $current_lang);
                    exit;
                }
                
                $stmt = $pdo->prepare("DELETE FROM extra_hours WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                
                $_SESSION['flash_message'] = 'record_deleted';
                header('Location: monthly_report.php?month=' . $current_month . '&lang=' . $current_lang);
                exit;
        }
    }
}

// Gestione richieste AJAX per filtri
if (isset($_GET['ajax']) && $_GET['ajax'] === 'filter') {
    // Recupera i parametri di filtro
    $filter_company = $_GET['filter_company'] ?? '';
    $filter_type = $_GET['filter_type'] ?? '';
    $filter_date = $_GET['filter_date'] ?? '';
    
    // Recupera i record individuali del mese
    $stmt_individual = $pdo->prepare("
        SELECT eh.*, c.name as company_name, c.color as company_color
        FROM extra_hours eh 
        JOIN companies c ON eh.company_id = c.id 
        WHERE DATE_FORMAT(eh.date, '%Y-%m') = ?
        AND eh.user_id = ?
        ORDER BY eh.date DESC, c.name
    ");
    $stmt_individual->execute([$current_month, $user_id]);
    $individual_records = $stmt_individual->fetchAll();
    
    // Applica filtri ai record
    $filtered_records = $individual_records;
    if (!empty($filter_company)) {
        $filtered_records = array_filter($filtered_records, function($record) use ($filter_company) {
            return $record['company_id'] == $filter_company;
        });
    }
    if (!empty($filter_type)) {
        $filtered_records = array_filter($filtered_records, function($record) use ($filter_type) {
            return $record['tipo_straordinario'] == $filter_type;
        });
    }
    if (!empty($filter_date)) {
        $filtered_records = array_filter($filtered_records, function($record) use ($filter_date) {
            return $record['date'] == $filter_date;
        });
    }
    
    // Genera l'HTML della tabella
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th><?= t('date', $current_lang) ?></th>
                    <th><?= t('company', $current_lang) ?></th>
                    <th><?= t('hours', $current_lang) ?></th>
                    <th><?= t('overtime_type', $current_lang) ?></th>
                    <th><?= t('description', $current_lang) ?></th>
                    <th><?= t('actions', $current_lang) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filtered_records)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-search me-2"></i>
                            <?= t('no_filtered_records', $current_lang) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filtered_records as $record): 
                        $tipo_straordinario = $record['tipo_straordinario'] ?? 'feriale';
                        $guadagno_lordo = calcolaGuadagnoLordo($record['hours'], $tipo_straordinario);
                        $guadagno_netto = calcolaGuadagnoNetto($guadagno_lordo);
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($record['date'])) ?></td>
                        <td>
                            <span class="badge" style="background-color: <?= $record['company_color'] ?>; color: white;">
                                <?= htmlspecialchars($record['company_name']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $record['hours'] ?> ore<br>
                            <small class="text-success">€<?= number_format($guadagno_netto, 2) ?> netto</small>
                        </td>
                        <td>
                            <?php
                            switch ($tipo_straordinario) {
                                case 'feriale':
                                    $badgeClass = 'badge-feriale';
                                    break;
                                case 'festivo':
                                    $badgeClass = 'badge-festivo';
                                    break;
                                case 'notturno':
                                    $badgeClass = 'badge-purple';
                                    break;
                                default: 
                                    $badgeClass = 'badge-secondary';
                                    break;
                            }
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= t($tipo_straordinario === 'feriale' ? 'weekday' : ($tipo_straordinario === 'festivo' ? 'holiday' : 'night'), $current_lang) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($record['description'] ?: '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editRecord(<?= $record['id'] ?>, '<?= $record['company_id'] ?>', '<?= $record['date'] ?>', <?= $record['hours'] ?>, '<?= htmlspecialchars($record['description']) ?>', '<?= $tipo_straordinario ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= t('confirm_delete', $current_lang) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $table_html = ob_get_clean();
    
    // Prepara la risposta JSON
    $response = [
        'success' => true,
        'table_html' => $table_html,
        'filtered_count' => count($filtered_records),
        'total_count' => count($individual_records),
        'has_filters' => !empty($filter_company) || !empty($filter_type) || !empty($filter_date)
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Recupera aziende
$companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();
$company_colors = [];
foreach ($companies as $company) {
    $company_colors[$company['id']] = $company['color'] ?? '#6c757d';
}

// Debug per aziende
error_log("Companies count: " . count($companies));
if (empty($companies)) {
    error_log("No companies found");
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

// Debug temporaneo
error_log("Current month: " . $current_month);
error_log("User ID: " . $user_id);
error_log("Monthly data count: " . count($monthly_data));
if (empty($monthly_data)) {
    error_log("No monthly data found");
}

// Recupera i record individuali del mese per la modifica
$stmt_individual = $pdo->prepare("
    SELECT eh.*, c.name as company_name, c.color as company_color
    FROM extra_hours eh 
    JOIN companies c ON eh.company_id = c.id 
    WHERE DATE_FORMAT(eh.date, '%Y-%m') = ?
    AND eh.user_id = ?
    ORDER BY eh.date DESC, c.name
");
$stmt_individual->execute([$current_month, $user_id]);
$individual_records = $stmt_individual->fetchAll();

// Debug per record individuali
error_log("Individual records count: " . count($individual_records));
if (empty($individual_records)) {
    error_log("No individual records found");
}

// Gestione filtri
$filter_company = $_GET['filter_company'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

// Applica filtri ai record
$filtered_records = $individual_records;
if (!empty($filter_company)) {
    $filtered_records = array_filter($filtered_records, function($record) use ($filter_company) {
        return $record['company_id'] == $filter_company;
    });
}
if (!empty($filter_type)) {
    $filtered_records = array_filter($filtered_records, function($record) use ($filter_type) {
        return $record['tipo_straordinario'] == $filter_type;
    });
}
if (!empty($filter_date)) {
    $filtered_records = array_filter($filtered_records, function($record) use ($filter_date) {
        return $record['date'] == $filter_date;
    });
}

$total_guadagno_lordo = 0;
$total_guadagno_netto = 0;
$total_ore_straordinarie = 0;
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
    $total_ore_straordinarie += $ore;
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

// Debug per riepilogo mensile
error_log("Monthly summary count: " . count($monthly_summary));
error_log("Total guadagno lordo: " . $total_guadagno_lordo);
error_log("Total guadagno netto: " . $total_guadagno_netto);

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
                <div class="me-3">
                    <select class="form-select form-select-sm" onchange="window.location.href='?month=' + this.value + '&lang=<?= $current_lang ?>'">
                        <?php
                        // Genera opzioni per i mesi (corrente e precedenti 6 mesi)
                        for ($i = 0; $i <= 6; $i++) {
                            $month_date = date('Y-m', strtotime("-$i months"));
                            $month_display = date('F Y', strtotime("-$i months"));
                            $selected = ($month_date === $current_month) ? 'selected' : '';
                            echo "<option value=\"$month_date\" $selected>$month_display</option>";
                        }
                        ?>
                    </select>
                </div>
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
        <!-- Success/Error Messages -->
        <?php 
        if (isset($_SESSION['flash_message'])) {
            $flash_message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            
            if ($flash_message === 'record_edited'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= t('record_edited', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($flash_message === 'record_deleted'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-trash me-2"></i>
                    <?= t('record_deleted', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($flash_message === 'validation_error'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= t('validation_error', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif;
        } ?>
        
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
                                                <div class="col-4">
                                                    <h3 class="text-success mb-0">€<?= number_format($total_guadagno_lordo, 2) ?></h3>
                                                    <small class="text-muted"><?= t('gross_salary', $current_lang) ?></small>
                                                </div>
                                                <div class="col-4">
                                                    <h3 class="text-primary mb-0">€<?= number_format($total_guadagno_netto, 2) ?></h3>
                                                    <small class="text-muted"><?= t('net_salary', $current_lang) ?></small>
                                                </div>
                                                <div class="col-4">
                                                    <h3 class="text-warning mb-0"><?= number_format($total_ore_straordinarie, 1) ?></h3>
                                                    <small class="text-muted"><?= t('hours', $current_lang) ?></small>
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
                                                            case 'notturno':
                                                                $badgeClass = 'badge-purple';
                                                                break;
                                                            default: 
                                                                $badgeClass = 'bg-secondary';
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
        
        <!-- Sezione Modifica Record -->
        <?php if (!empty($individual_records)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card slide-in-left">
                        <div class="card-header">
                            <i class="fas fa-edit me-2"></i>
                            <?= t('edit_records', $current_lang) ?>
                        </div>
                        <div class="card-body">
                            <!-- Form di Filtro -->
                            <form method="GET" action="" class="mb-4 filter-form" id="filterForm">
                                <input type="hidden" name="month" value="<?= htmlspecialchars($current_month) ?>">
                                <input type="hidden" name="lang" value="<?= htmlspecialchars($current_lang) ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="filter_company" class="form-label"><?= t('filter_by_company', $current_lang) ?></label>
                                        <select name="filter_company" id="filter_company" class="form-select">
                                            <option value=""><?= t('all_companies', $current_lang) ?></option>
                                            <?php foreach ($companies as $company): ?>
                                                <option value="<?= $company['id'] ?>" <?= $filter_company == $company['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($company['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="filter_type" class="form-label"><?= t('filter_by_type', $current_lang) ?></label>
                                        <select name="filter_type" id="filter_type" class="form-select">
                                            <option value=""><?= t('all_types', $current_lang) ?></option>
                                            <option value="feriale" <?= $filter_type === 'feriale' ? 'selected' : '' ?>>
                                                <?= t('weekday', $current_lang) ?>
                                            </option>
                                            <option value="festivo" <?= $filter_type === 'festivo' ? 'selected' : '' ?>>
                                                <?= t('holiday', $current_lang) ?>
                                            </option>
                                            <option value="notturno" <?= $filter_type === 'notturno' ? 'selected' : '' ?>>
                                                <?= t('night', $current_lang) ?>
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="filter_date" class="form-label"><?= t('filter_by_date', $current_lang) ?></label>
                                        <input type="date" name="filter_date" id="filter_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
                                    </div>
                                    
                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="d-flex gap-2 w-100">
                                            <button type="button" class="btn btn-primary flex-fill" id="applyFiltersBtn">
                                                <i class="fas fa-filter me-1"></i>
                                                <?= t('apply_filters', $current_lang) ?>
                                            </button>
                                            <a href="?month=<?= htmlspecialchars($current_month) ?>&lang=<?= htmlspecialchars($current_lang) ?>" class="btn btn-outline-secondary" id="clearFiltersBtn">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Statistiche filtri -->
                            <div id="filterStats">
                                <?php if (!empty($filter_company) || !empty($filter_type) || !empty($filter_date)): ?>
                                    <div class="alert alert-info mb-3 filter-stats">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong><?= t('filtered_records', $current_lang) ?>:</strong> 
                                        <?= count($filtered_records) ?> <?= count($filtered_records) === 1 ? 'record' : 'records' ?> 
                                        (su <?= count($individual_records) ?> totali)
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div id="tableContainer">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?= t('date', $current_lang) ?></th>
                                                <th><?= t('company', $current_lang) ?></th>
                                                <th><?= t('hours', $current_lang) ?></th>
                                                <th><?= t('overtime_type', $current_lang) ?></th>
                                                <th><?= t('description', $current_lang) ?></th>
                                                <th><?= t('actions', $current_lang) ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($filtered_records)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        <i class="fas fa-search me-2"></i>
                                                        <?= t('no_filtered_records', $current_lang) ?>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($filtered_records as $record): 
                                                    $tipo_straordinario = $record['tipo_straordinario'] ?? 'feriale';
                                                    $guadagno_lordo = calcolaGuadagnoLordo($record['hours'], $tipo_straordinario);
                                                    $guadagno_netto = calcolaGuadagnoNetto($guadagno_lordo);
                                                ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($record['date'])) ?></td>
                                                    <td>
                                                        <span class="badge" style="background-color: <?= $record['company_color'] ?>; color: white;">
                                                            <?= htmlspecialchars($record['company_name']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= $record['hours'] ?> ore<br>
                                                        <small class="text-success">€<?= number_format($guadagno_netto, 2) ?> netto</small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        switch ($tipo_straordinario) {
                                                            case 'feriale':
                                                                $badgeClass = 'badge-feriale';
                                                                break;
                                                            case 'festivo':
                                                                $badgeClass = 'badge-festivo';
                                                                break;
                                                            case 'notturno':
                                                                $badgeClass = 'badge-purple';
                                                                break;
                                                            default: 
                                                                $badgeClass = 'badge-secondary';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $badgeClass ?>">
                                                            <?= t($tipo_straordinario === 'feriale' ? 'weekday' : ($tipo_straordinario === 'festivo' ? 'holiday' : 'night'), $current_lang) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($record['description'] ?: '-') ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editRecord(<?= $record['id'] ?>, '<?= $record['company_id'] ?>', '<?= $record['date'] ?>', <?= $record['hours'] ?>, '<?= htmlspecialchars($record['description']) ?>', '<?= $tipo_straordinario ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= t('confirm_delete', $current_lang) ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card slide-in-left">
                        <div class="card-header">
                            <i class="fas fa-edit me-2"></i>
                            <?= t('edit_records', $current_lang) ?>
                        </div>
                        <div class="card-body">
                            <p class="text-muted"><?= t('no_records_month', $current_lang) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= t('edit_record', $current_lang) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        
                        <div class="mb-3">
                            <label for="edit_company_id" class="form-label"><?= t('company', $current_lang) ?></label>
                            <select name="company_id" id="edit_company_id" class="form-select" required>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_date" class="form-label"><?= t('date', $current_lang) ?></label>
                            <input type="date" name="date" id="edit_date" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_hours" class="form-label"><?= t('hours', $current_lang) ?></label>
                            <input type="number" name="hours" id="edit_hours" class="form-control" step="0.5" min="0" max="24" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_tipo_straordinario" class="form-label"><?= t('overtime_type', $current_lang) ?></label>
                            <select name="tipo_straordinario" id="edit_tipo_straordinario" class="form-select" required>
                                <option value="feriale"><?= t('weekday', $current_lang) ?></option>
                                <option value="festivo"><?= t('holiday', $current_lang) ?></option>
                                <option value="notturno"><?= t('night', $current_lang) ?></option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label"><?= t('description', $current_lang) ?></label>
                            <input type="text" name="description" id="edit_description" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('cancel', $current_lang) ?></button>
                        <button type="submit" class="btn btn-primary"><?= t('save_changes', $current_lang) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Traduzioni per JavaScript
        const translations = {
            loading: <?= json_encode(t('loading', $current_lang)) ?>,
            filter_error: <?= json_encode(t('filter_error', $current_lang)) ?>,
            apply_filters: <?= json_encode(t('apply_filters', $current_lang)) ?>,
            filtered_records: <?= json_encode(t('filtered_records', $current_lang)) ?>,
            record: <?= json_encode($current_lang === 'it' ? 'record' : 'record') ?>,
            records: <?= json_encode($current_lang === 'it' ? 'records' : 'records') ?>,
            of_total: <?= json_encode($current_lang === 'it' ? 'su' : 'of') ?>
        };
        
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
        
        function editRecord(id, companyId, date, hours, description, tipoStraordinario) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_company_id').value = companyId;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_hours').value = hours;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_tipo_straordinario').value = tipoStraordinario || 'feriale';
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Funzionalità per i filtri
        document.addEventListener('DOMContentLoaded', function() {
            // Gestione filtri AJAX
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            const filterForm = document.getElementById('filterForm');
            const tableContainer = document.getElementById('tableContainer');
            const filterStats = document.getElementById('filterStats');
            
            // Applica filtri via AJAX
            applyFiltersBtn.addEventListener('click', function() {
                const formData = new FormData(filterForm);
                formData.append('ajax', 'filter');
                
                // Mostra loading
                applyFiltersBtn.disabled = true;
                applyFiltersBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>' + translations.loading;
                
                fetch(window.location.pathname + '?' + new URLSearchParams(formData), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Aggiorna la tabella
                        tableContainer.innerHTML = data.table_html;
                        
                        // Aggiorna le statistiche
                        if (data.has_filters) {
                            const recordText = data.filtered_count === 1 ? translations.record : translations.records;
                            filterStats.innerHTML = `
                                <div class="alert alert-info mb-3 filter-stats">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>${translations.filtered_records}:</strong> 
                                    ${data.filtered_count} ${recordText} 
                                    (${translations.of_total} ${data.total_count} totali)
                                </div>
                            `;
                        } else {
                            filterStats.innerHTML = '';
                        }
                        
                        // Aggiorna l'URL senza ricaricare la pagina
                        const url = new URL(window.location);
                        url.searchParams.set('filter_company', formData.get('filter_company') || '');
                        url.searchParams.set('filter_type', formData.get('filter_type') || '');
                        url.searchParams.set('filter_date', formData.get('filter_date') || '');
                        window.history.pushState({}, '', url);
                    } else {
                        // Gestione errore di filtro
                        alert(translations.filter_error);
                        // Ripristina il pulsante
                        applyFiltersBtn.disabled = false;
                        applyFiltersBtn.innerHTML = '<i class="fas fa-filter me-1"></i>' + translations.apply_filters;
                    }
                })
                .catch(error => {
                    console.error('Errore durante il filtro:', error);
                    alert(translations.filter_error);
                })
                .finally(() => {
                    // Ripristina il pulsante
                    applyFiltersBtn.disabled = false;
                    applyFiltersBtn.innerHTML = '<i class="fas fa-filter me-1"></i>' + translations.apply_filters;
                });
            });
            
            // Pulisci filtri
            clearFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Reset del form
                filterForm.reset();
                
                // Applica filtri vuoti
                const formData = new FormData(filterForm);
                formData.append('ajax', 'filter');
                
                fetch(window.location.pathname + '?' + new URLSearchParams(formData), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        tableContainer.innerHTML = data.table_html;
                        filterStats.innerHTML = '';
                        
                        // Aggiorna l'URL
                        const url = new URL(window.location);
                        url.searchParams.delete('filter_company');
                        url.searchParams.delete('filter_type');
                        url.searchParams.delete('filter_date');
                        window.history.pushState({}, '', url);
                    }
                })
                .catch(error => {
                    console.error('Errore durante la pulizia dei filtri:', error);
                    // Fallback: ricarica la pagina
                    window.location.href = clearFiltersBtn.href;
                });
            });
        });
    </script>
</body>
</html> 