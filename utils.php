<?php
// Common utility functions

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

// Function to get current language
function getCurrentLanguage() {
    $current_lang = $_GET['lang'] ?? 'it';
    if (!in_array($current_lang, ['it', 'en'])) {
        $current_lang = 'it';
    }
    return $current_lang;
}

// Function to format date in Italian
function formatDateItalian($date) {
    $italian_days = [
        'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Gio',
        'Fri' => 'Ven', 'Sat' => 'Sab', 'Sun' => 'Dom'
    ];
    
    $day_name = date('D', strtotime($date));
    $day_italian = $italian_days[$day_name] ?? $day_name;
    
    return $day_italian . ' ' . date('d/m/Y', strtotime($date));
}

// Function to get Italian month name
function getItalianMonthName($date = null) {
    $italian_months = [
        'January' => 'Gennaio', 'February' => 'Febbraio', 'March' => 'Marzo',
        'April' => 'Aprile', 'May' => 'Maggio', 'June' => 'Giugno',
        'July' => 'Luglio', 'August' => 'Agosto', 'September' => 'Settembre',
        'October' => 'Ottobre', 'November' => 'Novembre', 'December' => 'Dicembre'
    ];
    
    $month = $date ? date('F', strtotime($date)) : date('F');
    return $italian_months[$month] ?? $month;
}

// Function to validate and sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to validate date format
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Function to validate hours (decimal number)
function isValidHours($hours) {
    return is_numeric($hours) && $hours >= 0 && $hours <= 24;
}

// Function to get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Function to set flash message
function setFlashMessage($message) {
    $_SESSION['flash_message'] = $message;
}

// Function to redirect with flash message
function redirectWithMessage($url, $message) {
    setFlashMessage($message);
    header('Location: ' . $url);
    exit;
}
?> 