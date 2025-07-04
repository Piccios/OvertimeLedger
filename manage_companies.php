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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --animation-duration: 15s;
            --animation-duration-slow: 25s;
            --transition-fast: 0.2s;
            --transition-normal: 0.3s;
        }

        /* Respect user's motion preferences */
        @media (prefers-reduced-motion: reduce) {
            :root {
                --animation-duration: 60s;
                --animation-duration-slow: 80s;
                --transition-fast: 0.1s;
                --transition-normal: 0.15s;
            }
            
            .shape {
                animation: none !important;
            }
        }

        body {
            background: linear-gradient(-45deg, #667eea, #764ba2, #667eea, #764ba2);
            background-size: 400% 400%;
            animation: gradientShift var(--animation-duration) ease infinite;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated gradient background */
        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        /* Geometric background elements */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 119, 198, 0.2) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }
        
        /* Floating geometric shapes - Optimized */
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            will-change: transform;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float var(--animation-duration-slow) infinite linear;
            will-change: transform;
            transform: translateZ(0); /* Force hardware acceleration */
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            left: 80%;
            animation-delay: -5s;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-delay: -10s;
        }
        
        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 70%;
            animation-delay: -15s;
            border-radius: 63% 37% 54% 46% / 55% 48% 52% 45%;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.5;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.8;
            }
        }
        
        .card {
            border: none;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 8px 32px 0 rgba(31, 38, 135, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
            will-change: transform;
            transform: translateZ(0);
        }
        
        .card-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px 0 rgba(102, 126, 234, 0.3);
            transition: transform var(--transition-fast) ease, box-shadow var(--transition-fast) ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-light {
            border-radius: 10px;
            transition: background-color var(--transition-fast) ease, transform var(--transition-fast) ease;
        }
        
        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            transition: border-color var(--transition-fast) ease, background-color var(--transition-fast) ease;
            will-change: auto;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-label {
            color: white;
            font-weight: 500;
        }
        
        /* Fix dropdown options */
        .form-select option {
            background: #fff;
            color: #333;
            padding: 0.5rem;
        }
        
        .form-select:not([multiple]):not([size]) {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
        }
        
        .company-card {
            transition: transform var(--transition-normal) ease, background-color var(--transition-normal) ease;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            will-change: transform;
        }
        
        .company-card:hover {
            transform: translateY(-5px) translateZ(0);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .company-badge {
            font-size: 0.9em;
            padding: 0.5em 1em;
        }
        
        /* Modal styling */
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: none;
            border-radius: 20px;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 20px 20px 0 0;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0 0 20px 20px;
        }
        
        /* Modal form controls - different styling for light background */
        .modal .form-control, .modal .form-select {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            color: #333;
        }
        
        .modal .form-control:focus, .modal .form-select:focus {
            background: rgba(0, 0, 0, 0.08);
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            color: #333;
        }
        
        .modal .form-control::placeholder {
            color: rgba(0, 0, 0, 0.5);
        }
        
        .modal .form-label {
            color: #333;
            font-weight: 500;
        }
        
        /* Modal dropdown styling */
        .modal .form-select option {
            background: #fff;
            color: #333;
        }
        
        .modal .form-select:not([multiple]):not([size]) {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23333333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
        }
        
        .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .input-group-text {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .alert {
            background: rgba(248, 215, 218, 0.9);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
        }
        
        /* Enhanced language switcher */
        .btn-check:checked + .btn-outline-light {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Floating geometric shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="text-white mb-0">
                        <i class="fas fa-building me-3"></i>
                        <?= t('manage_companies', $current_lang) ?>
                    </h1>
                </div>
            </div>
        </div>
        
        <!-- Language Switcher -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-end">
                    <div class="card bg-white bg-opacity-10 border-0">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="language" id="lang_it" value="it" <?= $current_lang === 'it' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-light btn-sm me-1 " for="lang_it">
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
        <div class="row-1 mb-3">
            <a href="index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>
                <?= t('back_to_main', $current_lang) ?>
            </a>
        </div>
        

        <!-- Error Message -->
        <?php if (isset($error)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form per l'aggiunta di nuove aziende -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus"></i> 
                            <?= t('add_new_company', $current_lang) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="add_company">
                            <div class="col-md-6">
                                <label for="name" class="form-label"><?= t('company_name', $current_lang) ?></label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label for="color" class="form-label"><?= t('color', $current_lang) ?></label>
                                <div class="input-group">
                                    <input type="color" name="color" id="color" class="form-control form-control-color" value="#6c757d" title="<?= t('color', $current_lang) ?>">
                                    <span class="input-group-text">
                                        <i class="fas fa-palette"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i><?= t('add', $current_lang) ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Elenco delle aziende -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> 
                            <?= t('companies_list', $current_lang) ?> (<?= count($companies) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($companies)): ?>
                            <p class="text-muted text-center"><?= t('no_companies', $current_lang) ?></p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($companies as $company): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card company-card h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <span class="badge company-badge" style="<?= getCompanyBadgeStyle($company['color']) ?>">
                                                        <?= htmlspecialchars($company['name']) ?>
                                                    </span>
                                                </div>
                                                <small class="text-muted d-block mb-3">
                                                    <?= $current_lang === 'it' ? 'Creata il' : 'Created on' ?> <?= date('d/m/Y', strtotime($company['created_at'])) ?>
                                                </small>
                                                <div class="btn-group w-100" role="group">
                                                    <button type="button" class="btn btn-outline-primary btn-sm edit-company-btn" 
                                                            data-id="<?= $company['id'] ?>"
                                                            data-name="<?= htmlspecialchars($company['name']) ?>"
                                                            data-color="<?= htmlspecialchars($company['color']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_company">
                                                        <input type="hidden" name="id" value="<?= $company['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                                onclick="return confirm('<?= t('confirm_delete_company', $current_lang) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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

    <!-- Modale per la modifica delle aziende -->
    <div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCompanyModalLabel">
                        <i class="fas fa-edit"></i> <?= t('edit_company', $current_lang) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editCompanyForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_company">
                        <input type="hidden" name="id" id="edit_company_id">
                        <div class="mb-3">
                            <label for="edit_company_name" class="form-label"><?= t('company_name', $current_lang) ?></label>
                            <input type="text" name="name" id="edit_company_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_company_color" class="form-label"><?= t('color', $current_lang) ?></label>
                            <div class="input-group">
                                <input type="color" name="color" id="edit_company_color" class="form-control form-control-color" title="<?= t('color', $current_lang) ?>">
                                <span class="input-group-text">
                                    <i class="fas fa-palette"></i>
                                </span>
                            </div>
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

    <!-- Notifiche di successo -->
    <?php if (isset($_GET['success'])): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="successToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Successo!</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?= t('company_added', $current_lang) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['edited'])): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="editToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-info text-white">
                <i class="fas fa-edit me-2"></i>
                <strong class="me-auto">Modificato!</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?= t('company_modified', $current_lang) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="deleteToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-dark">
                <i class="fas fa-trash me-2"></i>
                <strong class="me-auto">Eliminato!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?= t('company_deleted', $current_lang) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Performance optimizations
        (function() {
            // Reduce animation on low-end devices
            const isLowEndDevice = navigator.hardwareConcurrency <= 4 || 
                                  window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            
            if (isLowEndDevice) {
                document.documentElement.style.setProperty('--animation-duration', '40s');
                document.querySelector('.floating-shapes')?.remove();
            }
        })();

        // Hide notification after 4 seconds and clean URL
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = ['successToast', 'editToast', 'deleteToast'];
            let hasToast = false;
            
            toasts.forEach(function(toastId) {
                const toast = document.getElementById(toastId);
                if (toast) {
                    hasToast = true;
                    setTimeout(function() {
                        toast.classList.add('fade');
                        setTimeout(function() {
                            toast.remove();
                        }, 500);
                    }, 4000);
                }
            });
            
            // Clean URL parameters after showing notification
            if (hasToast) {
                const url = new URL(window.location);
                url.searchParams.delete('success');
                url.searchParams.delete('edited');
                url.searchParams.delete('deleted');
                window.history.replaceState({}, document.title, url.toString());
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

        // Company editing functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-company-btn')) {
                const button = e.target.closest('.edit-company-btn');
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const color = button.getAttribute('data-color');
                
                document.getElementById('edit_company_id').value = id;
                document.getElementById('edit_company_name').value = name;
                document.getElementById('edit_company_color').value = color;
                
                const editModal = new bootstrap.Modal(document.getElementById('editCompanyModal'));
                editModal.show();
            }
        });
    </script>
</body>
</html> 