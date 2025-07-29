<?php
session_start();
require_once 'login/auth.php';
require_once 'config.php';

// Verifica che l'utente sia loggato
requireLogin();

$pdo = getDBConnection();
$user_id = getCurrentUserId();

// Define current language
$current_lang = $_GET['lang'] ?? 'it';

// Simple translations
$translations = [
    'it' => [
        'page_title' => 'Gestore Ore Straordinarie',
        'add_overtime' => 'Aggiungi Ore Straordinarie',
        'company' => 'Azienda',
        'select_company' => 'Seleziona Azienda',
        'date' => 'Data',
        'hours' => 'Ore',
        'description' => 'Descrizione',
        'save' => 'Salva',
        'current_week' => 'Settimana Corrente',
        'no_overtime_week' => 'Nessuna ora straordinaria registrata questa settimana.',
        'actions' => 'Azioni',
        'edit' => 'Modifica',
        'delete' => 'Elimina',
        'monthly_summary' => 'Riepilogo Mensile',
        'manage_companies' => 'Gestione Aziende',
        'add_company' => 'Aggiungi Azienda',
        'company_name' => 'Nome Azienda',
        'color' => 'Colore',
        'companies_list' => 'Elenco Aziende',
        'back_to_main' => 'Torna al Menu Principale',
        'record_added' => 'Record aggiunto con successo!',
        'record_deleted' => 'Record eliminato con successo!',
        'record_edited' => 'Record modificato con successo!',
        'company_added' => 'Azienda aggiunta con successo!',
        'company_deleted' => 'Azienda eliminata con successo!',
        'no_data_month' => 'Nessun dato disponibile per questo mese.',
        'no_companies' => 'Nessuna azienda registrata.',
        'confirm_delete' => 'Sei sicuro di voler eliminare questo record?',
        'confirm_delete_company' => 'Sei sicuro di voler eliminare questa azienda?',
        'edit_record' => 'Modifica Record',
        'cancel' => 'Annulla',
        'save_changes' => 'Salva Modifiche',
        'dashboard' => 'Dashboard',
        'statistics' => 'Statistiche',
        'export_excel' => 'Esporta Excel',
        'register' => 'Registrati',
        'overtime_type' => 'Tipo Straordinario',
        'weekday' => 'Feriale (+15%)',
        'holiday' => 'Festivo (+30%)',
        'night' => 'Notturno (+50%)',
        'monthly_earnings' => 'Guadagni Mensili',
        'gross_salary' => 'Lordo',
        'net_salary' => 'Netto'
    ],
    'en' => [
        'page_title' => 'Overtime Hours Manager',
        'add_overtime' => 'Add Overtime Hours',
        'company' => 'Company',
        'select_company' => 'Select Company',
        'date' => 'Date',
        'hours' => 'Hours',
        'description' => 'Description',
        'save' => 'Save',
        'current_week' => 'Current Week',
        'no_overtime_week' => 'No overtime hours recorded this week.',
        'actions' => 'Actions',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'monthly_summary' => 'Monthly Summary',
        'manage_companies' => 'Manage Companies',
        'add_company' => 'Add Company',
        'company_name' => 'Company Name',
        'color' => 'Color',
        'companies_list' => 'Companies List',
        'back_to_main' => 'Back to Main Menu',
        'record_added' => 'Record added successfully!',
        'record_deleted' => 'Record deleted successfully!',
        'record_edited' => 'Record edited successfully!',
        'company_added' => 'Company added successfully!',
        'company_deleted' => 'Company deleted successfully!',
        'no_data_month' => 'No data available for this month.',
        'no_companies' => 'No companies registered.',
        'confirm_delete' => 'Are you sure you want to delete this record?',
        'confirm_delete_company' => 'Are you sure you want to delete this company?',
        'edit_record' => 'Edit Record',
        'cancel' => 'Cancel',
        'save_changes' => 'Save Changes',
        'dashboard' => 'Dashboard',
        'statistics' => 'Statistics',
        'export_excel' => 'Export Excel',
        'register' => 'Register',
        'overtime_type' => 'Overtime Type',
        'weekday' => 'Weekday (+15%)',
        'holiday' => 'Holiday (+30%)',
        'night' => 'Night (+50%)',
        'monthly_earnings' => 'Monthly Earnings',
        'gross_salary' => 'Gross Salary',
        'net_salary' => 'Net Salary'
    ]
];

function t($key, $lang = 'it') {
    global $translations;
    return $translations[$lang][$key] ?? $key;
}

// Costanti per il calcolo del guadagno
define('PAGA_ORARIA_BASE', 9.72226);
define('ALIQUOTA_TRATTENUTE', 0.206);

// Maggiorazioni per tipo di straordinario
$maggiorazioni = [
    'feriale' => 0.15,  // +15%
    'festivo' => 0.30,  // +30%
    'notturno' => 0.50  // +50%
];

// Funzione per calcolare il guadagno lordo
function calcolaGuadagnoLordo($ore, $tipo_straordinario) {
    global $maggiorazioni;
    
    $maggiorazione = $maggiorazioni[$tipo_straordinario] ?? 0.15;
    $paga_oraria_straordinario = PAGA_ORARIA_BASE * (1 + $maggiorazione);
    $lordo_straordinario = $paga_oraria_straordinario * $ore;
    
    return $lordo_straordinario;
}

// Funzione per calcolare il guadagno netto
function calcolaGuadagnoNetto($guadagno_lordo) {
    return $guadagno_lordo * (1 - ALIQUOTA_TRATTENUTE);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $company_id = $_POST['company_id'];
                $date = $_POST['date'];
                $hours = $_POST['hours'];
                $description = $_POST['description'] ?? '';
                $tipo_straordinario = $_POST['tipo_straordinario'] ?? 'feriale';
                
                $stmt = $pdo->prepare("INSERT INTO extra_hours (company_id, date, hours, description, tipo_straordinario, user_id) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE hours = ?, description = ?, tipo_straordinario = ?");
                $stmt->execute([$company_id, $date, $hours, $description, $tipo_straordinario, $user_id, $hours, $description, $tipo_straordinario]);
                
                $_SESSION['flash_message'] = 'success';
                header('Location: index.php');
                exit;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM extra_hours WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                
                $_SESSION['flash_message'] = 'deleted';
                header('Location: index.php');
                exit;
                
            case 'edit':
                $id = $_POST['id'];
                $company_id = $_POST['company_id'];
                $date = $_POST['date'];
                $hours = $_POST['hours'];
                $description = $_POST['description'] ?? '';
                $tipo_straordinario = $_POST['tipo_straordinario'] ?? 'feriale';
                
                $stmt = $pdo->prepare("UPDATE extra_hours SET company_id = ?, date = ?, hours = ?, description = ?, tipo_straordinario = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$company_id, $date, $hours, $description, $tipo_straordinario, $id, $user_id]);
                
                $_SESSION['flash_message'] = 'edited';
                header('Location: index.php');
                exit;
                
            case 'add_company':
                $name = trim($_POST['name']);
                $color = $_POST['color'] ?? '#6c757d';
                
                if (!empty($name)) {
                    $stmt = $pdo->prepare("INSERT INTO companies (name, color) VALUES (?, ?)");
                    $stmt->execute([$name, $color]);
                    $_SESSION['flash_message'] = 'company_added';
                }
                header('Location: index.php?tab=companies');
                exit;
                
            case 'delete_company':
                $id = $_POST['id'];
                
                // Check if company has any records
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM extra_hours WHERE company_id = ?");
                $stmt->execute([$id]);
                $has_records = $stmt->fetchColumn() > 0;
                
                if (!$has_records) {
                    $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash_message'] = 'company_deleted';
                }
                header('Location: index.php?tab=companies');
                exit;
        }
    }
}

// Retrieve companies
$companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();

// Create mapping for company colors
$company_colors = [];
foreach ($companies as $company) {
    $company_colors[$company['id']] = $company['color'] ?? '#6c757d';
}

// Retrieve current week data
$current_week_start = date('Y-m-d', strtotime('monday this week'));
$current_week_end = date('Y-m-d', strtotime('sunday this week'));

$stmt = $pdo->prepare("
    SELECT eh.*, c.name as company_name 
    FROM extra_hours eh 
    JOIN companies c ON eh.company_id = c.id 
    WHERE eh.date BETWEEN ? AND ? 
    AND eh.user_id = ?
    ORDER BY eh.date DESC, c.name
");
$stmt->execute([$current_week_start, $current_week_end, $user_id]);
$week_data = $stmt->fetchAll();

// Retrieve monthly summary with overtime type
$current_month = date('Y-m');
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

// Calcola i guadagni totali mensili
$total_guadagno_lordo = 0;
$total_guadagno_netto = 0;
$monthly_summary = [];

foreach ($monthly_data as $data) {
    $company_id = $data['company_id'];
    $company_name = $data['company_name'];
    $company_color = $data['company_color'];
    $tipo_straordinario = $data['tipo_straordinario'];
    $ore = $data['total_hours'];
    
    // Calcola i guadagni per questo gruppo
    $guadagno_lordo = calcolaGuadagnoLordo($ore, $tipo_straordinario);
    $guadagno_netto = calcolaGuadagnoNetto($guadagno_lordo);
    
    $total_guadagno_lordo += $guadagno_lordo;
    $total_guadagno_netto += $guadagno_netto;
    
    // Raggruppa per azienda per la visualizzazione
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

// Ordina per totale lordo decrescente
uasort($monthly_summary, function($a, $b) {
    return $b['total_lordo'] <=> $a['total_lordo'];
});

$current_tab = $_GET['tab'] ?? 'main';
?>

<!DOCTYPE html>
<html lang="it">
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="logo">
                <img src="images/logo.svg" alt="Logo" class="logo-img me-2" width="32" height="32">
                <strong><?= t('page_title', $current_lang) ?></strong>
                <?php if($current_tab === 'main'): ?>
                    
                <?php elseif($current_tab === 'companies'): ?>
                
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center">
                <?php if($current_tab === 'main'): ?>
                    <!-- Main page: show companies management button -->
                    <a href="?tab=companies&lang=<?= $current_lang ?>" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-building me-1"></i>
                        <?= t('manage_companies', $current_lang) ?>
                    </a>
                <?php elseif($current_tab === 'companies'): ?>
                    <!-- Companies page: show back to main button -->
                    <a href="?tab=main&lang=<?= $current_lang ?>" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-home me-1"></i>
                        <?= t('back_to_main', $current_lang) ?>
                    </a>
                <?php endif; ?>
                <a href="monthly_report.php?lang=<?= $current_lang ?>" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-chart-bar me-1"></i>
                    <?= t('monthly_summary', $current_lang) ?>
                </a>
                
                <!-- Language selector and logout button -->
                <a href="login/logout.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt p-1"></i> 
                    
                </a>
                <a href="?tab=<?= $current_tab ?>&lang=<?= $current_lang === 'it' ? 'en' : 'it' ?>" class="btn language-selector me-2">
                    <i class="fas fa-globe me-1"></i>
                    <?= $current_lang === 'it' ? '🇺🇸' : '🇮🇹' ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php 
        if (isset($_SESSION['flash_message'])) {
            $flash_message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            
            if ($flash_message === 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= t('record_added', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($flash_message === 'deleted'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-trash me-2"></i>
                    <?= t('record_deleted', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($flash_message === 'edited'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-edit me-2"></i>
                    <?= t('record_edited', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($flash_message === 'company_added'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= t('company_added', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($flash_message === 'company_deleted'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-trash me-2"></i>
                    <?= t('company_deleted', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif;
        } ?>

        <?php if ($current_tab === 'main'): ?>
            <!-- Add Overtime Form -->
            <div class="card fade-in-up mb-4">
                <div class="card-header">
                    <i class="fas fa-plus me-2"></i>
                    <?= t('add_overtime', $current_lang) ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="company_id" class="form-label">
                                    <?= t('company', $current_lang) ?> *
                                </label>
                                <select name="company_id" id="company_id" class="form-select" required>
                                    <option value=""><?= t('select_company', $current_lang) ?></option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="date" class="form-label">
                                    <?= t('date', $current_lang) ?> *
                                </label>
                                <input type="date" name="date" id="date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="hours" class="form-label">
                                    <?= t('hours', $current_lang) ?> *
                                </label>
                                <input type="number" name="hours" id="hours" class="form-control" step="0.5" min="0" max="24" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="tipo_straordinario" class="form-label">
                                    <?= t('overtime_type', $current_lang) ?> *
                                </label>
                                <select name="tipo_straordinario" id="tipo_straordinario" class="form-select" required>
                                    <option value="feriale"><?= t('weekday', $current_lang) ?></option>
                                    <option value="festivo"><?= t('holiday', $current_lang) ?></option>
                                    <option value="notturno"><?= t('night', $current_lang) ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">
                                    <?= t('description', $current_lang) ?>
                                </label>
                                <input type="text" name="description" id="description" class="form-control">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            <?= t('save', $current_lang) ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <!-- Current Week Data -->
                    <div class="card slide-in-left h-100">
                        <div class="card-header">
                            <i class="fas fa-calendar-week me-2"></i>
                            <?= t('current_week', $current_lang) ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($week_data)): ?>
                                <p class="text-muted"><?= t('no_overtime_week', $current_lang) ?></p>
                            <?php else: ?>
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
                                            <?php foreach ($week_data as $record): 
                                                $tipo_straordinario = $record['tipo_straordinario'] ?? 'feriale';
                                                $guadagno_lordo = calcolaGuadagnoLordo($record['hours'], $tipo_straordinario);
                                                $guadagno_netto = calcolaGuadagnoNetto($guadagno_lordo);
                                            ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($record['date'])) ?></td>
                                                    <td>
                                                        <span class="badge" style="background-color: <?= $company_colors[$record['company_id']] ?>; color: white;">
                                                            <?= htmlspecialchars($record['company_name']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= $record['hours'] ?> ore<br>
                                                        <small class="text-success">€<?= number_format($guadagno_netto, 2) ?> netto</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= $tipo_straordinario ?>">
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
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= t('confirm_delete', $current_lang) ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>


        <?php elseif ($current_tab === 'companies'): ?>
            <!-- Add Company Form -->
            <div class="card fade-in-up mb-4">
                <div class="card-header">
                    <i class="fas fa-plus me-2"></i>
                    <?= t('add_company', $current_lang) ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_company">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    <?= t('company_name', $current_lang) ?> *
                                </label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="color" class="form-label">
                                    <?= t('color', $current_lang) ?>
                                </label>
                                <input type="color" name="color" id="color" class="form-control color-picker" value="#6c757d">
                            </div>
                            
                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Companies List -->
            <div class="card slide-in-left">
                <div class="card-header">
                    <i class="fas fa-building me-2"></i>
                    <?= t('companies_list', $current_lang) ?>
                </div>
                <div class="card-body">
                    <?php if (empty($companies)): ?>
                        <p class="text-muted"><?= t('no_companies', $current_lang) ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= t('company_name', $current_lang) ?></th>
                                        <th><?= t('color', $current_lang) ?></th>
                                        <th><?= t('actions', $current_lang) ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($company['name']) ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?= $company['color'] ?>; color: white;">
                                                    <?= $company['color'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_company">
                                                    <input type="hidden" name="id" value="<?= $company['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="editCompany(<?= $company['id'] ?>, '<?= $company['name'] ?>', '<?= $company['color'] ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= t('confirm_delete_company', $current_lang) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
        function editRecord(id, companyId, date, hours, description, tipoStraordinario) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_company_id').value = companyId;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_hours').value = hours;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_tipo_straordinario').value = tipoStraordinario || 'feriale';
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Add animation classes when elements come into view
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
            
            // Observe all elements with animation classes
            document.querySelectorAll('.fade-in-up, .slide-in-left, .slide-in-right, .scale-in').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html> 