<?php
session_start();
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
                
                // Set flash message and redirect
                $_SESSION['flash_message'] = 'success';
                header('Location: index.php');
                exit;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM extra_hours WHERE id = ?");
                $stmt->execute([$id]);
                
                // Set flash message and redirect
                $_SESSION['flash_message'] = 'deleted';
                header('Location: index.php');
                exit;
                
            case 'edit':
                $id = $_POST['id'];
                $company_id = $_POST['company_id'];
                $date = $_POST['date'];
                $hours = $_POST['hours'];
                $description = $_POST['description'] ?? '';
                
                $stmt = $pdo->prepare("UPDATE extra_hours SET company_id = ?, date = ?, hours = ?, description = ? WHERE id = ?");
                $stmt->execute([$company_id, $date, $hours, $description, $id]);
                
                // Set flash message and redirect
                $_SESSION['flash_message'] = 'edited';
                header('Location: index.php');
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


?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestore Ore Straordinarie</title>
    <link rel="icon" type="image/svg+xml" href="vendor/src/imgs/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">


</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
        <div class="logo">
            <img src="vendor/src/imgs/logo.svg" alt="Logo" class="logo-img me-2" style="height: 40px;">
            <strong>
            <?= t('page_title', $current_lang) ?></strong>
        </div>
            <div class="d-flex align-items-center">
                <a href="manage_companies.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-building me-1"></i>
                    <?= t('manage_companies', $current_lang) ?>
                </a>
                
                <a href="?lang=<?= $current_lang === 'it' ? 'en' : 'it' ?>" class="btn language-selector">
                    <i class="fas fa-globe me-1"></i>
                    <?= $current_lang === 'it' ? '🇺🇸' : '🇮🇹' ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php 
        // Check for flash messages and clear them after displaying
        if (isset($_SESSION['flash_message'])) {
            $flash_message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']); // Clear the message
            
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
            <?php endif;
        } ?>

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
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label for="hours" class="form-label">
                                <?= t('hours', $current_lang) ?> *
                            </label>
                            <input type="number" name="hours" id="hours" class="form-control" step="0.5" min="0" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="description" class="form-label">
                                <?= t('description', $current_lang) ?> (<?= t('optional', $current_lang) ?>)
                            </label>
                            <input type="text" name="description" id="description" class="form-control">
                        </div>
                        
                        <div class="col-md-1 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Week and Monthly Summary Row -->
        <div class="row">
            <!-- Current Week Section -->
            <div class="col-md-6 mb-4">
                <div class="card slide-in-left h-100">
                    <div class="card-header">
                        <i class="fas fa-calendar-week me-2"></i>
                        <?= t('current_week', $current_lang) ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($week_data)): ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= t('no_overtime_week', $current_lang) ?>
                            </p>
                        <?php else: ?>
                            <div class="table-responsive week-table-container">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><?= t('company', $current_lang) ?></th>
                                            <th><?= t('date', $current_lang) ?></th>
                                            <th><?= t('hours', $current_lang) ?></th>
                                            <th><?= t('actions', $current_lang) ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($week_data as $record): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge company-badge" style="<?= getCompanyBadgeStyle($company_colors[$record['company_id']]) ?>">
                                                        <?= htmlspecialchars($record['company_name']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($record['date'])) ?></td>
                                                <td><strong><?= $record['hours'] ?></strong></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="editRecord(<?= $record['id'] ?>, '<?= $record['company_id'] ?>', '<?= $record['date'] ?>', <?= $record['hours'] ?>, '<?= htmlspecialchars($record['description'] ?? '') ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="deleteRecord(<?= $record['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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

            <!-- Monthly Summary -->
            <div class="col-md-6 mb-4">
                <div class="card scale-in h-100">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>
                        <?= t('monthly_summary', $current_lang) ?> - <?= $current_month_name ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthly_summary)): ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= t('no_data_month', $current_lang) ?>
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><?= t('company', $current_lang) ?></th>
                                            <th><?= t('total', $current_lang) ?></th>
                                            <th><?= t('summary', $current_lang) ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_hours = array_sum(array_column($monthly_summary, 'total_hours'));
                                        foreach ($monthly_summary as $summary): 
                                            $percentage = $total_hours > 0 ? round(($summary['total_hours'] / $total_hours) * 100, 1) : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="badge company-badge" style="<?= getCompanyBadgeStyle($summary['company_color']) ?>">
                                                        <?= htmlspecialchars($summary['company_name']) ?>
                                                    </span>
                                                </td>
                                                <td><strong><?= $summary['total_hours'] ?></strong></td>
                                                <td><?= $percentage ?>% <?= t('of_total', $current_lang) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <div class="summary-card">
                                    <h6><i class="fas fa-download me-2"></i><?= t('export_excel', $current_lang) ?></h6>
                                    <p class="mb-2 small"><?= t('monthly_summary', $current_lang) ?> - <?= $current_month_name ?></p>
                                    <a href="export_excel.php?month=<?= $current_month ?>" class="btn btn-export btn-sm">
                                        <i class="fas fa-file-excel me-2"></i>
                                        <?= t('export_excel', $current_lang) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        <?= t('edit_record', $current_lang) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_company_id" class="form-label">
                                <?= t('company', $current_lang) ?> *
                            </label>
                            <select name="company_id" id="edit_company_id" class="form-select" required>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_date" class="form-label">
                                <?= t('date', $current_lang) ?> *
                            </label>
                            <input type="date" name="date" id="edit_date" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_hours" class="form-label">
                                <?= t('hours', $current_lang) ?> *
                            </label>
                            <input type="number" name="hours" id="edit_hours" class="form-control" step="0.5" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">
                                <?= t('description', $current_lang) ?>
                            </label>
                            <input type="text" name="description" id="edit_description" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <?= t('cancel', $current_lang) ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <?= t('save_changes', $current_lang) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= t('confirm_delete', $current_lang) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><?= t('confirm_delete', $current_lang) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= t('cancel', $current_lang) ?>
                    </button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="submit" class="btn btn-primary">
                            <?= t('delete', $current_lang) ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit record function
        function editRecord(id, companyId, date, hours, description) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_company_id').value = companyId;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_hours').value = hours;
            document.getElementById('edit_description').value = description;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        // Delete record function
        function deleteRecord(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Scroll animations
        function animateOnScroll() {
            const elements = document.querySelectorAll('.fade-in-up, .slide-in-left, .scale-in');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            elements.forEach(element => {
                observer.observe(element);
            });
        }

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            animateOnScroll();
            
            // Set today's date as default
            document.getElementById('date').value = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html> 