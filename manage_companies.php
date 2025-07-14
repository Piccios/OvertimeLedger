<?php
require_once 'config.php';
require_once 'translations.php';

$pdo = getDBConnection();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_company':
                $name = trim($_POST['name']);
                $color = $_POST['color'] ?? '#6c757d';
                
                if (!empty($name)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO companies (name, color) VALUES (?, ?)");
                        $stmt->execute([$name, $color]);
                        
                        header('Location: manage_companies.php?success=1');
                        exit;
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) { // Duplicate entry
                            $error = 'Company name already exists.';
                        } else {
                            $error = 'Error adding company.';
                        }
                    }
                } else {
                    $error = 'Company name is required.';
                }
                break;
                
            case 'edit_company':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $color = $_POST['color'] ?? '#6c757d';
                
                if (!empty($name)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE companies SET name = ?, color = ? WHERE id = ?");
                        $stmt->execute([$name, $color, $id]);
                        
                        header('Location: manage_companies.php?edited=1');
                        exit;
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error = 'Company name already exists.';
                        } else {
                            $error = 'Error updating company.';
                        }
                    }
                } else {
                    $error = 'Company name is required.';
                }
                break;
                
            case 'delete_company':
                $id = $_POST['id'];
                
                // Check if company has any records
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM extra_hours WHERE company_id = ?");
                $stmt->execute([$id]);
                $has_records = $stmt->fetchColumn() > 0;
                
                if ($has_records) {
                    $error = 'Cannot delete a company that has overtime records. Delete all records first.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    header('Location: manage_companies.php?deleted=1');
                    exit;
                }
                break;
        }
    }
}

// Retrieve all companies
$companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();


?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Aziende - Gestore Ore Straordinarie</title>
    <link rel="icon" type="image/svg+xml" href="vendor/src/imgs/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    </style>
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
                <a href="index.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left me-1"></i>
                    <?= t('back_to_main', $current_lang) ?>
                </a>
                
                <a href="?lang=<?= $current_lang === 'it' ? 'en' : 'it' ?>" class="btn language-selector">
                    <i class="fas fa-globe me-1"></i>
                    <?= $current_lang === 'it' ? '🇮🇹' : '🇺🇸' ?>
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
                    <?= t('company_added', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($flash_message === 'edited'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-edit me-2"></i>
                    <?= t('company_modified', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($flash_message === 'deleted'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-trash me-2"></i>
                    <?= t('company_deleted', $current_lang) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif;
        } ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Company Form -->
        <div class="card fade-in-up mb-4">
            <div class="card-header">
                <i class="fas fa-plus me-2"></i>
                <?= t('add_new_company', $current_lang) ?>
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
                    <p class="text-muted mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= t('no_companies', $current_lang) ?>
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
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
                                        <td>
                                            <span class="badge company-badge" style="<?= getCompanyBadgeStyle($company['color']) ?>">
                                                <?= htmlspecialchars($company['name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="color-preview me-2" style="width: 30px; height: 30px; background-color: <?= $company['color'] ?>; border-radius: 6px;"></div>
                                                <span class="text-muted"><?= $company['color'] ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary me-1" onclick="editCompany(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>', '<?= $company['color'] ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="deleteCompany(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>')">
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

    <!-- Edit Company Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        <?= t('edit_company', $current_lang) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_company">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">
                                <?= t('company_name', $current_lang) ?> *
                            </label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_color" class="form-label">
                                <?= t('color', $current_lang) ?>
                            </label>
                            <input type="color" name="color" id="edit_color" class="form-control color-picker">
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
                        <?= t('confirm_delete_company', $current_lang) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><?= t('confirm_delete_company', $current_lang) ?></p>
                    <p class="text-muted" id="delete_company_name"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= t('cancel', $current_lang) ?>
                    </button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="delete_company">
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
        // Edit company function
        function editCompany(id, name, color) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_color').value = color;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        // Delete company function
        function deleteCompany(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_company_name').textContent = name;
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
        });
    </script>
</body>
</html> 