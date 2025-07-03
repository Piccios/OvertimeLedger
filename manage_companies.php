<?php
require_once 'config.php';

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
        .company-card {
            transition: transform 0.2s;
        }
        .company-card:hover {
            transform: translateY(-2px);
        }
        .company-badge {
            font-size: 0.9em;
            padding: 0.5em 1em;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="text-white mb-0">
                        <i class="fas fa-building me-3"></i>
                        Manage Companies
                    </h1>
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Main Menu
                    </a>
                </div>
            </div>
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
                            Add New Company
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="add_company">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Company Name</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label for="color" class="form-label">Color</label>
                                <div class="input-group">
                                    <input type="color" name="color" id="color" class="form-control form-control-color" value="#6c757d" title="Scegli colore">
                                    <span class="input-group-text">
                                        <i class="fas fa-palette"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>
                                    Add
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
                            Companies List (<?= count($companies) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($companies)): ?>
                            <p class="text-muted text-center">No companies present in the system.</p>
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
                                                    Creata il <?= date('d/m/Y', strtotime($company['created_at'])) ?>
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
                                                                onclick="return confirm('Are you sure you want to delete this company?')">
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
                        <i class="fas fa-edit"></i> Edit Company
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editCompanyForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_company">
                        <input type="hidden" name="id" id="edit_company_id">
                        <div class="mb-3">
                            <label for="edit_company_name" class="form-label">Company Name</label>
                            <input type="text" name="name" id="edit_company_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_company_color" class="form-label">Color</label>
                            <div class="input-group">
                                <input type="color" name="color" id="edit_company_color" class="form-control form-control-color" title="Scegli colore">
                                <span class="input-group-text">
                                    <i class="fas fa-palette"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save
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
                Company added successfully!
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
                Company modified successfully!
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
                Company deleted successfully!
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Nascondi la notifica dopo 4 secondi e pulisci l'URL
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
            
            // Pulisci i parametri dell'URL dopo aver mostrato la notifica
            if (hasToast) {
                const url = new URL(window.location);
                url.searchParams.delete('success');
                url.searchParams.delete('edited');
                url.searchParams.delete('deleted');
                window.history.replaceState({}, document.title, url.toString());
            }
        });

        // Funzionalità per la modifica delle aziende
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