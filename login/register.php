<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: text/html; charset=utf-8');

// Risposta di default
$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validazione base
    if (!$username || !$email || !$password) {
        $response['message'] = 'Tutti i campi sono obbligatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Email non valida.';
    } elseif (strlen($password) < 6) {
        $response['message'] = 'La password deve essere di almeno 6 caratteri.';
    } else {
        $pdo = getDBConnection();
        // Controllo username/email duplicati
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $response['message'] = 'Username o email già registrati.';
        } else {
            // Inserimento utente
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $success = $stmt->execute([$username, $email, $hash, 'user']);
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Registrazione avvenuta con successo! Ora puoi accedere.';
            } else {
                $response['message'] = 'Errore durante la registrazione.';
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