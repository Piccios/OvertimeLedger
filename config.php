<?php
// Configurazione del database
define('DB_HOST', 'localhost');
define('DB_NAME', 'straordinari');
define('DB_USER', 'root');  // Cambia questo con il tuo username MySQL
define('DB_PASS', '');      // Cambia questo con la tua password MySQL

// Creazione della connessione al database
function getDBConnection() {
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
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?> 