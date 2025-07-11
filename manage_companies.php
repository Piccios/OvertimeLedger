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
            /* Modern neon theme variables */
            --primary-pastel: #39ff14;
            --secondary-pastel: #00ffff;
            --accent-pastel: #ff00ff;
            --light-pastel: #39ff14;
            --dark-text: #ffffff;
            --shadow-soft: rgba(57, 255, 20, 0.3);
            
            --bg-primary: #000000;
            --bg-secondary: #0a0a0a;
            --bg-tertiary: #111111;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --border-color: #39ff14;
            --shadow-color: rgba(57, 255, 20, 0.4);
            
            /* Neon glow effects */
            --neon-green: #39ff14;
            --neon-cyan: #00ffff;
            --neon-pink: #ff00ff;
            --neon-blue: #0080ff;
            --neon-yellow: #ffff00;
            
            /* Glass effect */
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            
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
            background: var(--bg-primary);
            min-height: 100vh;
            font-family: 'Inter', 'Roboto', 'Helvetica Neue', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
            transition: var(--transition-smooth);
            font-weight: 400;
            letter-spacing: 0.01em;
            
            /* Animated background */
            background: linear-gradient(45deg, #000000, #0a0a0a, #000000);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .card {
            background: var(--glass-bg);
            border: 1px solid #000000;
            border-radius: 16px;
            box-shadow: 
                0 0 20px rgba(57, 255, 20, 0.3),
                0 0 40px rgba(57, 255, 20, 0.1),
                inset 0 0 20px rgba(57, 255, 20, 0.05);
            transition: var(--transition-smooth);
            overflow: hidden;
            position: relative;
            backdrop-filter: blur(20px);
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 0 30px rgba(57, 255, 20, 0.5),
                0 0 60px rgba(57, 255, 20, 0.2),
                0 0 90px rgba(57, 255, 20, 0.1),
                inset 0 0 30px rgba(57, 255, 20, 0.1);
            animation: neonPulse 2s ease-in-out infinite;
        }

        @keyframes neonPulse {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(57, 255, 20, 0.4);
            }
            50% { 
                box-shadow: 0 0 40px rgba(57, 255, 20, 0.8);
            }
        }

        .card-header {
            background: var(--neon-green);
            color: #000;
            border: none;
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 16px 16px 0 0;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 20px rgba(57, 255, 20, 0.3);
        }

        .card-body {
            padding: 2rem;
        }

        .btn-primary {
            background: var(--neon-green);
            color: #000;
            font-weight: 600;
            border: 1px solid #000000;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 0 20px rgba(57, 255, 20, 0.4),
                inset 0 0 20px rgba(57, 255, 20, 0.1);
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }

        .btn-primary:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 
                0 0 30px rgba(57, 255, 20, 0.6),
                0 0 60px rgba(57, 255, 20, 0.3),
                inset 0 0 30px rgba(57, 255, 20, 0.2);
            color: #000;
        }

        .btn-outline-secondary {
            border: 2px solid #000000;
            color: var(--neon-cyan);
            background: transparent;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition-smooth);
            box-shadow: 
                0 0 15px rgba(0, 255, 255, 0.3),
                inset 0 0 15px rgba(0, 255, 255, 0.05);
        }

        .btn-outline-secondary:hover {
            background: var(--neon-cyan);
            color: #000;
            transform: translateY(-2px) scale(1.02);
            box-shadow: 
                0 0 25px rgba(0, 255, 255, 0.5),
                0 0 50px rgba(0, 255, 255, 0.2),
                inset 0 0 25px rgba(0, 255, 255, 0.1);
        }

        .form-control, .form-select {
            background: var(--glass-bg);
            border: 2px solid #000000;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: var(--transition-smooth);
            color: var(--text-primary);
            box-shadow: 
                0 0 10px rgba(57, 255, 20, 0.2),
                inset 0 0 10px rgba(57, 255, 20, 0.05);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--neon-cyan);
            box-shadow: 
                0 0 20px rgba(0, 255, 255, 0.4),
                0 0 40px rgba(0, 255, 255, 0.2),
                inset 0 0 20px rgba(0, 255, 255, 0.1);
            outline: none;
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .table {
            background: var(--glass-bg);
            border: 1px solid #000000;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 
                0 0 15px rgba(57, 255, 20, 0.3),
                inset 0 0 15px rgba(57, 255, 20, 0.05);
        }

        /* Fix table border-radius for proper corner display */
        .table-responsive {
            border-radius: 16px;
            overflow: hidden;
        }

        /* Remove individual th border-radius for homogeneous header row */
        .table th {
            border-radius: 0 !important;
        }

        /* Only apply border-radius to the header row as a whole */
        .table thead {
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            overflow: hidden;
        }

        .table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 16px;
        }

        .table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 16px;
        }

        .table th {
            background: var(--neon-green);
            color: #000;
            font-weight: 700;
            border: none;
            padding: 1rem;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }

        .table td {
            background: var(--glass-bg);
            border: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
            padding: 1rem;
            /* transition: var(--transition-smooth); */
            color: var(--text-primary);
        }

        .table tbody tr:hover {
            background: rgba(57, 255, 20, 0.1);
            /* transform: scale(1.02); */
            box-shadow: 0 0 20px rgba(57, 255, 20, 0.2);
        }

        .badge {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
        }

        .navbar {
            background: var(--glass-bg);
            border-bottom: 1px solid #000000;
            box-shadow: 
                0 0 20px rgba(57, 255, 20, 0.3),
                0 0 40px rgba(57, 255, 20, 0.1);
            border-radius: 0 0 20px 20px;
            backdrop-filter: blur(20px);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--neon-green) !important;
            text-shadow: 0 0 10px rgba(57, 255, 20, 0.5);
        }

        .language-selector {
            background: var(--neon-cyan);
            color: #000;
            font-weight: 600;
            border: 1px solid #000000;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            box-shadow: 
                0 0 15px rgba(0, 255, 255, 0.4),
                inset 0 0 15px rgba(0, 255, 255, 0.1);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--neon-green);
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(57, 255, 20, 0.5);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--neon-cyan);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.6);
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
            /* transition: var(--transition-smooth); */
            /* box-shadow: 0 0 10px rgba(57, 255, 20, 0.3); */
        }

        /* Modal styles */
        .modal-content {
            background: var(--glass-bg);
            border: 1px solid #000000;
            border-radius: 24px;
            box-shadow: 
                0 0 30px rgba(57, 255, 20, 0.4),
                0 0 60px rgba(57, 255, 20, 0.2),
                inset 0 0 30px rgba(57, 255, 20, 0.05);
            backdrop-filter: blur(20px);
        }

        .modal-header {
            background: var(--neon-green);
            color: #000;
            border-bottom: 1px solid #000000;
            border-radius: 24px 24px 0 0;
        }

        .modal-footer {
            border-top: 1px solid #000000;
            border-radius: 0 0 24px 24px;
        }

        /* Toast notifications */
        .toast {
            background: var(--glass-bg);
            border: 1px solid #000000;
            border-radius: 16px;
            box-shadow: 
                0 0 20px rgba(57, 255, 20, 0.3),
                inset 0 0 20px rgba(57, 255, 20, 0.05);
        }

        .toast-header {
            background: var(--neon-green);
            color: #000;
            border-bottom: 1px solid #000000;
            border-radius: 16px 16px 0 0;
        }

        /* Summary cards */
        .summary-card {
            background: var(--glass-bg);
            border: 1px solid #000000;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition-smooth);
            box-shadow: 
                0 0 15px rgba(0, 255, 255, 0.3),
                inset 0 0 15px rgba(0, 255, 255, 0.05);
        }

        .summary-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 
                0 0 25px rgba(0, 255, 255, 0.4),
                0 0 50px rgba(0, 255, 255, 0.2),
                inset 0 0 25px rgba(0, 255, 255, 0.1);
        }

        .summary-card h5 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .summary-card p {
            color: var(--text-primary);
            opacity: 0.8;
            margin-bottom: 0;
        }

        /* Ensure all paragraph text is properly colored */
        p {
            color: var(--text-primary);
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        /* Ensure all heading text is properly colored */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
        }

        /* Export button */
        .btn-export {
            background: var(--neon-cyan);
            color: #000;
            font-weight: 600;
            border: 1px solid #000000;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            transition: var(--transition-smooth);
            box-shadow: 
                0 0 15px rgba(0, 255, 255, 0.4),
                inset 0 0 15px rgba(0, 255, 255, 0.1);
        }

        .btn-export:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 
                0 0 25px rgba(0, 255, 255, 0.6),
                0 0 50px rgba(0, 255, 255, 0.3),
                inset 0 0 25px rgba(0, 255, 255, 0.2);
            color: #000;
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--neon-green);
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
            box-shadow: 0 0 10px rgba(57, 255, 20, 0.5);
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Dropdown menu */
        .dropdown-menu {
            background: var(--glass-bg);
            border: 1px solid #000000;
            box-shadow: 
                0 0 20px rgba(57, 255, 20, 0.3),
                inset 0 0 20px rgba(57, 255, 20, 0.05);
            backdrop-filter: blur(20px);
        }

        .dropdown-item {
            color: var(--text-primary);
            transition: var(--transition-smooth);
        }

        .dropdown-item:hover {
            background: rgba(57, 255, 20, 0.1);
            color: var(--neon-green);
        }

        /* Alert styles */
        .alert {
            background: var(--glass-bg);
            border: 1px solid #000000;
            color: var(--text-primary);
            box-shadow: 
                0 0 15px rgba(57, 255, 20, 0.3),
                inset 0 0 15px rgba(57, 255, 20, 0.05);
        }

        .alert-success {
            background: rgba(57, 255, 20, 0.1);
            border-color: #000000;
            box-shadow: 
                0 0 15px rgba(57, 255, 20, 0.3),
                inset 0 0 15px rgba(57, 255, 20, 0.05);
        }

        .alert-info {
            background: rgba(0, 255, 255, 0.1);
            border-color: #000000;
            box-shadow: 
                0 0 15px rgba(0, 255, 255, 0.3),
                inset 0 0 15px rgba(0, 255, 255, 0.05);
        }

        .alert-danger {
            background: rgba(255, 0, 0, 0.1);
            border-color: #000000;
            box-shadow: 
                0 0 15px rgba(255, 0, 0, 0.3),
                inset 0 0 15px rgba(255, 0, 0, 0.05);
        }

        /* Accessibility improvements */
        .btn:focus, .form-control:focus, .form-select:focus {
            outline: 2px solid var(--neon-cyan);
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

        /* Glow text effect */
        .glow-text {
            text-shadow: 0 0 10px var(--neon-green);
        }

        /* Color picker styling */
        input[type="color"] {
            background: var(--glass-bg);
            border: 2px solid #000000;
            border-radius: 8px;
            padding: 0.5rem;
            cursor: pointer;
            box-shadow: 
                0 0 10px rgba(57, 255, 20, 0.2),
                inset 0 0 10px rgba(57, 255, 20, 0.05);
        }

        input[type="color"]:hover {
            box-shadow: 
                0 0 15px rgba(57, 255, 20, 0.4),
                inset 0 0 15px rgba(57, 255, 20, 0.1);
        }

        /* Action buttons */
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff0000, #ff4444);
            color: #fff;
            font-weight: 600;
            border: 1px solid #000000;
            box-shadow: 
                0 0 15px rgba(255, 0, 0, 0.4),
                inset 0 0 15px rgba(255, 0, 0, 0.1);
        }

        .btn-danger:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 
                0 0 25px rgba(255, 0, 0, 0.6),
                0 0 50px rgba(255, 0, 0, 0.3),
                inset 0 0 25px rgba(255, 0, 0, 0.2);
            color: #fff;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffaa00, #ffcc44);
            color: #000;
            font-weight: 600;
            border: 1px solid #000000;
            box-shadow: 
                0 0 15px rgba(255, 170, 0, 0.4),
                inset 0 0 15px rgba(255, 170, 0, 0.1);
        }

        .btn-warning:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 
                0 0 25px rgba(255, 170, 0, 0.6),
                0 0 50px rgba(255, 170, 0, 0.3),
                inset 0 0 25px rgba(255, 170, 0, 0.2);
            color: #000;
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