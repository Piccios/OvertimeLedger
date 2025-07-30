<?php
require_once __DIR__ . '/../config.php';

/**
 * Verifica se l'utente è autenticato
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !isSessionExpired();
}

/**
 * Verifica se la sessione è scaduta
 * @return bool
 */
function isSessionExpired() {
    if (!isset($_SESSION['last_activity'])) {
        return true;
    }
    
    $timeout = SESSION_LIFETIME;
    if (time() - $_SESSION['last_activity'] > $timeout) {
        logout();
        return true;
    }
    
    $_SESSION['last_activity'] = time();
    return false;
}

/**
 * Restituisce l'ID dell'utente loggato
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Restituisce i dati dell'utente corrente
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT id, username, email, role FROM users WHERE id = ?');
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

/**
 * Forza il redirect alla pagina di login se l'utente non è autenticato
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /straordinari/login/login.php');
        exit;
    }
}

/**
 * Effettua il logout dell'utente
 */
function logout() {
    session_unset();
    session_destroy();
    header('Location: /straordinari/login/login.php');
    exit;
}

/**
 * Verifica se l'utente ha i permessi per accedere a una risorsa
 * @param string $resource
 * @return bool
 */
function hasPermission($resource) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Admin ha accesso a tutto
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Permessi specifici per ruolo
    $permissions = [
        'user' => ['view_own_data', 'edit_own_data', 'delete_own_data'],
        'manager' => ['view_own_data', 'edit_own_data', 'delete_own_data', 'view_team_data']
    ];
    
    return in_array($resource, $permissions[$user['role']] ?? []);
}

/**
 * Verifica se l'utente può accedere ai dati di un altro utente
 * @param int $target_user_id
 * @return bool
 */
function canAccessUserData($target_user_id) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Admin può accedere a tutto
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Manager può accedere ai dati del team
    if ($user['role'] === 'manager') {
        // Implementa logica per verificare se l'utente target è nel team
        return true; // Per ora semplificato
    }
    
    // User può accedere solo ai propri dati
    return $user['id'] == $target_user_id;
}
?> 