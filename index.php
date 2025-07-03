<?php
require_once 'config.php';
require_once 'translations.php';

$pdo = getDBConnection();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $company_id = $_POST['company_id'];
                $date = $_POST['date'];
                $hours = $_POST['hours'];
                $description = $_POST['description'] ?? '';
                
                $stmt = $pdo->prepare("INSERT INTO extra_hours (company_id, date, hours, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE hours = ?, description = ?");
                $stmt->execute([$company_id, $date, $hours, $description, $hours, $description]);
                
                // Redirect to avoid form resubmission
                header('Location: index.php?success=1');
                exit;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM extra_hours WHERE id = ?");
                $stmt->execute([$id]);
                
                // Redirect to avoid form resubmission
                header('Location: index.php?deleted=1');
                exit;
                
            case 'edit':
                $id = $_POST['id'];
                $company_id = $_POST['company_id'];
                $date = $_POST['date'];
                $hours = $_POST['hours'];
                $description = $_POST['description'] ?? '';
                
                $stmt = $pdo->prepare("UPDATE extra_hours SET company_id = ?, date = ?, hours = ?, description = ? WHERE id = ?");
                $stmt->execute([$company_id, $date, $hours, $description, $id]);
                
                // Redirect to avoid form resubmission
                header('Location: index.php?edited=1');
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
    ORDER BY eh.date DESC, c.name
");
$stmt->execute([$current_week_start, $current_week_end]);
$week_data = $stmt->fetchAll();

// Retrieve monthly summary
$current_month = date('Y-m');
$stmt = $pdo->prepare("
    SELECT c.id as company_id, c.name as company_name, c.color as company_color, SUM(eh.hours) as total_hours
    FROM extra_hours eh 
    JOIN companies c ON eh.company_id = c.id 
    WHERE DATE_FORMAT(eh.date, '%Y-%m') = ?
    GROUP BY c.id, c.name, c.color
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

// Italian days
$italian_days = [
    'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Gio',
    'Fri' => 'Ven', 'Sat' => 'Sab', 'Sun' => 'Dom'
];

$current_month_name = $italian_months[date('F')];

// Function to get company badge style
function getCompanyBadgeStyle($color) {
    return "background-color: {$color}; color: " . (isColorDark($color) ? 'white' : 'black') . ";";
}

// Function to determine if a color is dark (to choose appropriate text color)
function isColorDark($color) {
    $hex = str_replace('#', '', $color);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Calculate brightness using luminance formula
    $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    
    return $brightness < 128;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestore Ore Straordinarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .badge {
            font-size: 0.8em;
        }
        .company-badge {
            font-size: 0.7em;
            padding: 0.3em 0.6em;
        }
        
        /* Colori specifici per le aziende */
        .badge-defenda {
            background-color: #1e3a8a !important;
            color: white !important;
        }
        
        .badge-euroansa {
            background-color: #3b82f6 !important;
            color: white !important;
        }
        
        .badge-ilv {
            background-color: #f59e0b !important;
            color: white !important;
        }
        
        /* Colori specifici per il testo delle aziende */
        .text-defenda {
            color: #1e3a8a !important;
        }
        
        .text-euroansa {
            color: #3b82f6 !important;
        }
        
        .text-ilv {
            color: #f59e0b !important;
        }
        
        /* Bordi specifici per le aziende */
        .border-defenda {
            border-color: #1e3a8a !important;
        }
        
        .border-euroansa {
            border-color: #3b82f6 !important;
        }
        
        .border-ilv {
            border-color: #f59e0b !important;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        
        <div class="row">
            <div class="col-12">
                
                <h1 class="text-white text-center mb-4">
                    <i class="fas fa-clock"></i> OvertimeLedger
                </h1>
                <!-- Language Switcher -->
                <div class="d-flex justify-content-end mb-4">
                    <div class="card bg-white bg-opacity-10 border-0">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="language" id="lang_it" value="it" <?= $current_lang === 'it' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-light btn-sm" for="lang_it">
                                            🇮🇹 <?= t('italian', $current_lang) ?>
                                        </label>
                                        <input type="radio" class="btn-check" name="language" id="lang_en" value="en" <?= $current_lang === 'en' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-light btn-sm" for="lang_en">
                                            🇺🇸 <?= t('english', $current_lang) ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Link per la gestione delle aziende -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="manage_companies.php" class="btn btn-outline-light">
                    <i class="fas fa-building me-2"></i>
                    Company Management
                </a>
            </div>
        </div>

        <!-- Form per l'aggiunta di nuove voci -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between text-center">
                        <h5 class="mb-0"><i class="fas fa-plus"></i> <?= t('add_overtime', $current_lang) ?></h5>
                        <h6><?= t('required_fields', $current_lang) ?></h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="add">
                            <div class="col-md-3">
                                <label for="company_id" class="form-label"><?= t('company', $current_lang) ?> *</label>
                                <select name="company_id" id="company_id" class="form-select" required>
                                    <option value=""><?= t('select_company', $current_lang) ?></option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date" class="form-label"><?= t('date', $current_lang) ?> *</label>
                                <input type="date" name="date" id="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label for="hours" class="form-label"><?= t('hours', $current_lang) ?> *</label>
                                <input type="number" name="hours" id="hours" class="form-control" step="0.5" min="0" placeholder="2.5" required>
                            </div>
                            <div class="col-md-3">
                                <label for="description" class="form-label"><?= t('description', $current_lang) ?></label>
                                <input type="text" name="description" id="description" class="form-control" placeholder="<?= t('optional', $current_lang) ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riepilogo della settimana corrente -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-week"></i> 
                            <?= t('current_week', $current_lang) ?> (<?= date('d', strtotime($current_week_start)) ?> <?= $italian_months[date('F', strtotime($current_week_start))] ?> - <?= date('d', strtotime($current_week_end)) ?> <?= $italian_months[date('F', strtotime($current_week_end))] ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($week_data)): ?>
                            <p class="text-muted text-center"><?= t('no_overtime_week', $current_lang) ?></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= t('date', $current_lang) ?></th>
                                            <th><?= t('company', $current_lang) ?></th>
                                            <th><?= t('hours', $current_lang) ?></th>
                                            <th><?= t('description', $current_lang) ?></th>
                                            <th><?= t('actions', $current_lang) ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($week_data as $record): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= $italian_days[date('D', strtotime($record['date']))] ?>, <?= date('d', strtotime($record['date'])) ?> <?= $italian_months[date('F', strtotime($record['date']))] ?></strong>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $company_color = $company_colors[$record['company_id']] ?? '#6c757d';
                                                    ?>
                                                    <span class="badge company-badge" style="<?= getCompanyBadgeStyle($company_color) ?>">
                                                        <?= htmlspecialchars($record['company_name']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?= $record['hours'] ?>h
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($record['description'] ?: '-') ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary me-1 edit-btn" 
                                                            data-id="<?= $record['id'] ?>"
                                                            data-company-id="<?= $record['company_id'] ?>"
                                                            data-company="<?= htmlspecialchars($record['company_name']) ?>"
                                                            data-date="<?= $record['date'] ?>"
                                                            data-hours="<?= $record['hours'] ?>"
                                                            data-description="<?= htmlspecialchars($record['description'] ?? '') ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro?')">
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

        <!-- Riepilogo mensile -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar"></i> 
                            <?= t('monthly_summary', $current_lang) ?> (<?= $current_month_name ?> <?= date('Y') ?>)
                            <a href="export_excel.php" class="btn btn-success btn-sm float-end">
                                <i class="fas fa-download"></i> <?= t('export_excel', $current_lang) ?>
                            </a>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthly_summary)): ?>
                            <p class="text-muted text-center"><?= t('no_data_month', $current_lang) ?></p>
                        <?php else: ?>
                            <!-- Total monthly hours -->
                            <?php 
                            $total_monthly_hours = array_sum(array_column($monthly_summary, 'total_hours'));
                            ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h4 class="mb-2">
                                                <i class="fas fa-chart-line me-2"></i>
                                                <?= t('total_monthly_hours', $current_lang) ?>
                                            </h4>
                                            <h1 class="display-4 mb-0"><?= $total_monthly_hours ?>h</h1>
                                            <small class="opacity-75"><?= $current_month_name ?> <?= date('Y') ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Company breakdown -->
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-building me-2"></i>
                                <?= t('summary_by_company', $current_lang) ?>
                            </h6>
                            <div class="row">
                                <?php foreach ($monthly_summary as $summary): ?>
                                    <?php 
                                    $company_color = $summary['company_color'] ?? '#6c757d';
                                    ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card" style="border-color: <?= $company_color ?>; border-width: 2px;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title"><?= htmlspecialchars($summary['company_name']) ?></h6>
                                                <h3 style="color: <?= $company_color ?>;"><?= $summary['total_hours'] ?>h</h3>
                                                <small class="text-muted">
                                                    <?= round(($summary['total_hours'] / $total_monthly_hours) * 100, 1) ?>% <?= t('of_total', $current_lang) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale per la modifica dei record -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit"></i> <?= t('edit_record', $current_lang) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editForm">
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
                            <input type="number" name="hours" id="edit_hours" class="form-control" step="0.5" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label"><?= t('edit_description_optional', $current_lang) ?></label>
                            <input type="text" name="description" id="edit_description" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('cancel', $current_lang) ?></button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= t('save_changes', $current_lang) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Notifica di successo -->
    <?php if (isset($_GET['success'])): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="successToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?= t('record_added', $current_lang) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notifica di eliminazione -->
    <?php if (isset($_GET['deleted'])): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="deleteToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-dark">
                <i class="fas fa-trash me-2"></i>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?= t('record_deleted', $current_lang) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notifica di modifica -->
    <?php if (isset($_GET['edited'])): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="editToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-info text-white">
                <i class="fas fa-edit me-2"></i>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?= t('record_edited', $current_lang) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <style>
        /* Animazione delle notifiche */
        .toast.show {
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast.fade-out {
            animation: slideOutRight 0.5s ease-in forwards;
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        /* Stili delle notifiche */
        .toast {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .toast-header {
            border-radius: 10px 10px 0 0;
            border-bottom: none;
        }
        
        .toast-body {
            padding: 1rem;
            font-weight: 500;
        }
        
        /* Animazione per l'icona di successo */
        .toast.show .fas.fa-check-circle {
            animation: bounceIn 0.6s ease-out;
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>

    <script>
        // Hide notification after 4 seconds and clean URL
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = ['successToast', 'deleteToast', 'editToast'];
            let hasToast = false;
            
            toasts.forEach(function(toastId) {
                const toast = document.getElementById(toastId);
                if (toast) {
                    hasToast = true;
                    setTimeout(function() {
                        toast.classList.add('fade-out');
                        setTimeout(function() {
                            toast.remove();
                        }, 500);
                    }, 4000);
                }
            });
            
            // Clean URL parameters after showing notification
            if (hasToast) {
                // Remove URL parameters without reloading the page
                const url = new URL(window.location);
                url.searchParams.delete('success');
                url.searchParams.delete('deleted');
                url.searchParams.delete('edited');
                window.history.replaceState({}, document.title, url.toString());
            }
        });

        // Event listeners for record editing
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-btn')) {
                const button = e.target.closest('.edit-btn');
                const id = button.getAttribute('data-id');
                const companyId = button.getAttribute('data-company-id');
                const date = button.getAttribute('data-date');
                const hours = button.getAttribute('data-hours');
                const description = button.getAttribute('data-description');
                
                // Populate edit form
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_company_id').value = companyId;
                document.getElementById('edit_date').value = date;
                document.getElementById('edit_hours').value = hours;
                document.getElementById('edit_description').value = description;
                
                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            }
        });

        // Language switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const languageRadios = document.querySelectorAll('input[name="language"]');
            
            languageRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    const selectedLang = this.value;
                    const currentUrl = new URL(window.location);
                    currentUrl.searchParams.set('lang', selectedLang);
                    window.location.href = currentUrl.toString();
                });
            });
        });
    </script>
</body>
</html> 