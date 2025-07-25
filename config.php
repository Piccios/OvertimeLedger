<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'straordinari');
define('DB_USER', 'root');
define('DB_PASS', '');

// Creating database connection
function getDBConnection() {
    static $pdo = null;
    
    // Use singleton pattern to avoid multiple connections
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    return $pdo;
}

// Simple language function
function getCurrentLanguage() {
    $current_lang = $_GET['lang'] ?? 'it';
    if (!in_array($current_lang, ['it', 'en'])) {
        $current_lang = 'it';
    }
    return $current_lang;
}

// Get current language
$current_lang = getCurrentLanguage();
?> 