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
    <title>Gestione Aziende - Gestore Ore Straordinarie</title>
    <link rel="icon" href="./vendor/src/imgs/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="./vendor/src/imgs/favicon.svg" sizes="any" type="image/svg+xml">
    <link rel="shortcut icon" href="./vendor/src/imgs/favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-pastel: #FFEDF3;
            --secondary-pastel: #ADEED9;
            --accent-pastel: #56DFCF;
            --light-pastel: #0ABAB5;
            --dark-text: #2c3e50;
            --shadow-soft: rgba(0, 0, 0, 0.1);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Scroll animations */
        @media (prefers-reduced-motion: no-preference) {
            .fade-in-up {
                opacity: 0;
                transform: translateY(30px);
                transition: var(--transition-smooth);
            }
            
            .fade-in-up.animate {
                opacity: 1;
                transform: translateY(0);
            }
            
            .slide-in-left {
                opacity: 0;
                transform: translateX(-30px);
                transition: var(--transition-smooth);
            }
            
            .slide-in-left.animate {
                opacity: 1;
                transform: translateX(0);
            }
            
            .scale-in {
                opacity: 0;
                transform: scale(0.9);
                transition: var(--transition-smooth);
            }
            
            .scale-in.animate {
                opacity: 1;
                transform: scale(1);
            }
        }

        body {
            background: var(--light-pastel);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .card {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 
                0 4px 6px var(--shadow-soft),
                0 10px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition-smooth);
            overflow: hidden;
            position: relative;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 8px 12px var(--shadow-soft),
                0 20px 30px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: var(--primary-pastel);
            color: var(--dark-text);
            border: none;
            padding: 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 2rem;
        }

        .btn-primary {
            background: var(--primary-pastel);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition-smooth);
            color: var(--dark-text);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(248, 181, 211, 0.4);
            color: var(--dark-text);
        }

        .btn-outline-secondary {
            border: 2px solid var(--accent-pastel);
            color: var(--dark-text);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition-smooth);
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: var(--accent-pastel);
            transform: translateY(-1px);
            color: var(--dark-text);
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: var(--transition-smooth);
            background: white;
            color: var(--dark-text);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-pastel);
            box-shadow: 0 0 0 0.2rem rgba(248, 181, 211, 0.25);
            outline: none;
        }

        .form-label {
            color: var(--dark-text);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 4px var(--shadow-soft);
        }

        .table th {
            background: var(--secondary-pastel);
            color: var(--dark-text);
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table td {
            background: white;
            border: none;
            border-bottom: 1px solid #f8f9fa;
            padding: 1rem;
            transition: var(--transition-smooth);
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        .badge {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px var(--shadow-soft);
            border-radius: 0 0 20px 20px;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--dark-text) !important;
        }

        .language-selector {
            background: var(--accent-pastel);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: var(--dark-text);
            font-weight: 500;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-pastel);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #f4a6c7;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }

        /* Company badge styles */
        .company-badge {
            font-size: 0.8em;
            padding: 0.4em 0.8em;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition-smooth);
        }

        .company-badge:hover {
            transform: scale(1.05);
        }

        /* Modal styles */
        .modal-content {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px var(--shadow-soft);
        }

        .modal-header {
            border-bottom: 1px solid #f8f9fa;
            border-radius: 24px 24px 0 0;
            background: var(--light-pastel);
        }

        .modal-footer {
            border-top: 1px solid #f8f9fa;
            border-radius: 0 0 24px 24px;
        }

        /* Toast notifications */
        .toast {
            background: white;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px var(--shadow-soft);
        }

        .toast-header {
            background: var(--light-pastel);
            border-bottom: 1px solid #f8f9fa;
            border-radius: 16px 16px 0 0;
        }

        /* Color picker styling */
        .color-picker {
            width: 50px;
            height: 50px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .color-picker:hover {
            transform: scale(1.1);
        }

        /* Company card */
        .company-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition-smooth);
            box-shadow: 0 2px 8px var(--shadow-soft);
            border: 2px solid transparent;
        }

        .company-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-soft);
        }

        /* Accessibility improvements */
        .btn:focus, .form-control:focus, .form-select:focus {
            outline: 2px solid var(--primary-pastel);
            outline-offset: 2px;
        }

        /* Print styles */
        @media print {
            .btn, .language-selector {
                display: none !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="logo">
                    <strong><img src="./vendor/src/imgs/favicon.svg" alt="logo" class="src">
                    <?= t('page_title', $current_lang) ?></strong>
            </div>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left me-1"></i>
                    <?= t('back_to_main', $current_lang) ?>
                </a>
                
                <div class="dropdown">
                    <button class="btn language-selector dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-globe me-1"></i>
                        <?= $current_lang === 'it' ? '🇮🇹' : '🇺🇸' ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?lang=it">🇮🇹 <?= t('italian', $current_lang) ?></a></li>
                        <li><a class="dropdown-item" href="?lang=en">🇺🇸 <?= t('english', $current_lang) ?></a></li>
                    </ul>
                </div>
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