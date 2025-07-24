<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

/**
 * Verifica se l'utente è autenticato
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Restituisce l'ID dell'utente loggato
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
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
    header('Location: /login/login.php');
    exit;
} 