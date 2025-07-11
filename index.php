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

        /* Stile per placeholder */
        .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--neon-cyan);
            background: var(--glass-bg);
            box-shadow: 
                0 0 20px rgba(0, 255, 255, 0.4),
                0 0 40px rgba(0, 255, 255, 0.2),
                inset 0 0 20px rgba(0, 255, 255, 0.1);
            outline: none;
            color: var(--text-primary);
        }

        /* Stile per le opzioni delle select */
        .form-select option {
            background: var(--bg-primary);
            color: var(--text-primary);
            border: none;
        }

        .form-select option:hover {
            background: rgba(57, 255, 20, 0.1);
            color: var(--neon-green);
        }

        /* Stile per il dropdown delle select */
        .form-select {
            /* Rimuovo l'icona personalizzata per evitare duplicazioni */
            background-image: none;
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

        /* Tabella settimana corrente con altezza fissa */
        .week-table-container {
            max-height: 450px;
            overflow-y: auto;
            border-radius: 16px;
            scrollbar-width: none;
        }

        .week-table-container .table {
            margin-bottom: 0;
        }

        .week-table-container .table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: var(--neon-green);
        }

        .week-table-container .table thead th {
            border-bottom: 2px solid #000000;
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
            /* transition: var(--transition-smooth);
            box-shadow: 0 0 10px rgba(57, 255, 20, 0.3); */
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
            border-radius: 16px;
            box-shadow: 
                0 0 20px rgba(57, 255, 20, 0.3),
                0 0 40px rgba(57, 255, 20, 0.1),
                inset 0 0 20px rgba(57, 255, 20, 0.05);
            backdrop-filter: blur(20px);
            overflow: hidden;
        }

        .dropdown-item {
            color: var(--text-primary);
            transition: var(--transition-smooth);
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: rgba(57, 255, 20, 0.1);
            color: var(--neon-green);
            transform: translateX(5px);
            box-shadow: 0 0 10px rgba(57, 255, 20, 0.2);
        }

        .dropdown-item:active {
            background: rgba(57, 255, 20, 0.2);
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
                <a href="manage_companies.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-building me-1"></i>
                    <?= t('manage_companies', $current_lang) ?>
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
                                            <th><?= t('total_monthly_hours', $current_lang) ?></th>
                                            <th><?= t('summary_by_company', $current_lang) ?></th>
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
                                <?= t('edit_description_optional', $current_lang) ?>
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