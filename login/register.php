<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: text/html; charset=utf-8');

// Risposta di default
$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $response['message'] = 'Errore di sicurezza. Ricarica la pagina e riprova.';
    } else {
        // Sanitizza input
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validazione input
        if (!$username || !$email || !$password) {
            $response['message'] = 'Tutti i campi sono obbligatori.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $response['message'] = 'Username può contenere solo lettere, numeri e underscore.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $response['message'] = 'Username deve essere tra 3 e 50 caratteri.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Email non valida.';
        } elseif (strlen($email) > 255) {
            $response['message'] = 'Email troppo lunga.';
        } else {
            // Validazione password
            $password_validation = validatePassword($password);
            if (!$password_validation['valid']) {
                $response['message'] = $password_validation['message'];
            } else {
                $pdo = getDBConnection();
                
                // Controllo username/email duplicati
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
                $stmt->execute([$username, $email]);
                if ($stmt->fetchColumn() > 0) {
                    $response['message'] = 'Username o email già registrati.';
                } else {
                    // Inserimento utente con validazione aggiuntiva
                    try {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
                        $success = $stmt->execute([$username, $email, $hash, 'user']);
                        
                        if ($success) {
                            $response['success'] = true;
                            $response['message'] = 'Registrazione avvenuta con successo! Ora puoi accedere.';
                            
                            // Log della registrazione
                            $client_ip = getClientIP();
                            recordLoginAttempt($client_ip, $username, true, 'register');
                        } else {
                            $response['message'] = 'Errore durante la registrazione.';
                        }
                    } catch (PDOException $e) {
                        error_log("Registration error: " . $e->getMessage());
                        $response['message'] = 'Errore durante la registrazione. Riprova più tardi.';
                    }
                }
            }
        }
    }
} else {
    $response['message'] = 'Metodo non consentito.';
}

if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
) {
    // Risposta JSON per AJAX
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Output semplice per ora (puoi migliorare con redirect o JSON/AJAX)
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Registrazione</title></head><body>';
echo '<div style="max-width:400px;margin:40px auto;font-family:sans-serif;text-align:center;">';
echo '<h2>Registrazione</h2>';
echo '<p>' . htmlspecialchars($response['message']) . '</p>';
if ($response['success']) {
    echo '<a href="login.php">Vai al login</a>';
} else {
    echo '<a href="javascript:history.back()">Torna indietro</a>';
}
echo '</div></body></html>'; 