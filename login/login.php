<?php
require_once __DIR__ . '/../security_headers.php';
require_once __DIR__ . '/auth.php';

// Se già loggato, vai alla home
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$client_ip = getClientIP();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica rate limiting
    if (!checkRateLimit($client_ip, 'login')) {
        $error = 'Troppi tentativi di accesso. Riprova tra 15 minuti.';
    } else {
        // Sanitizza input
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Verifica CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Errore di sicurezza. Ricarica la pagina e riprova.';
            recordLoginAttempt($client_ip, $username, false, 'login');
        } elseif ($username && $password) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare('SELECT id, password_hash, username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login riuscito
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time();
                
                recordLoginAttempt($client_ip, $username, true, 'login');
                
                header('Location: ../index.php');
                exit;
            } else {
                // Login fallito
                $error = 'Credenziali non valide.';
                recordLoginAttempt($client_ip, $username, false, 'login');
            }
        } else {
            $error = 'Inserisci username e password.';
        }
    }
}

// Define current language
$current_lang = $_GET['lang'] ?? 'it';

// Simple translations
$translations = [
    'it' => [
        'page_title' => 'Gestore Ore Straordinarie',
        'login' => 'Accedi',
        'username' => 'Nome utente',
        'password' => 'Password',
        'invalid_credentials' => 'Credenziali non valide.',
        'enter_credentials' => 'Inserisci username e password.',
        'too_many_attempts' => 'Troppi tentativi di accesso. Riprova tra 15 minuti.',
        'security_error' => 'Errore di sicurezza. Ricarica la pagina e riprova.',
        'register' => 'Registrati',
        'new_user' => 'Nuovo utente?'
    ],
    'en' => [
        'page_title' => 'Overtime Hours Manager',
        'login' => 'Login',
        'username' => 'Username',
        'password' => 'Password',
        'invalid_credentials' => 'Invalid credentials.',
        'enter_credentials' => 'Enter username and password.',
        'too_many_attempts' => 'Too many login attempts. Try again in 15 minutes.',
        'security_error' => 'Security error. Reload the page and try again.',
        'register' => 'Register',
        'new_user' => 'New user?'
    ]
];

function t($key, $lang = 'it')
{
    global $translations;
    return $translations[$lang][$key] ?? $key;
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('page_title', $current_lang) ?></title>
    <link rel="icon" type="image/svg+xml" href="../images/favicon.svg">
    <link rel="icon" type="image/png" href="../images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../style/styles.css" rel="stylesheet">
    <!-- Security headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="logo">
                <img src="../images/logo.svg" alt="Logo" class="logo-img me-2" width="32" height="32">
                <strong><?= t('page_title', $current_lang) ?></strong>
            </div>
            <div class="d-flex align-items-center">
                <!-- Language selector -->
                <a href="?lang=<?= $current_lang === 'it' ? 'en' : 'it' ?>" class="btn language-selector">
                    <i class="fas fa-globe me-1"></i>
                    <?= $current_lang === 'it' ? '🇺🇸' : '🇮🇹' ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: calc(100vh - 80px);">
            <div class="col-md-6 col-lg-5">
                <div class="card fade-in-up">
                    <div class="card-header">
                        <i class="fas fa-lock me-2"></i>
                        <?= t('login', $current_lang) ?>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars(t($error === 'Credenziali non valide.' ? 'invalid_credentials' : 
                                    ($error === 'Troppi tentativi di accesso. Riprova tra 15 minuti.' ? 'too_many_attempts' : 
                                    ($error === 'Errore di sicurezza. Ricarica la pagina e riprova.' ? 'security_error' : 'enter_credentials')), $current_lang)) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="fade-in-up">
                            <!-- CSRF Protection -->
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>
                                    <?= t('username', $current_lang) ?>
                                </label>
                                <input type="text" name="username" id="username" class="form-control" required
                                    autofocus maxlength="50" pattern="[a-zA-Z0-9_]+" 
                                    title="Solo lettere, numeri e underscore">
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-key me-2"></i>
                                    <?= t('password', $current_lang) ?>
                                </label>
                                <input type="password" name="password" id="password" class="form-control" required
                                    maxlength="128">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-4">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                <?= t('login', $current_lang) ?>
                            </button>

                            <div class="text-center">
                                <label for="register" class="form-label">
                                    <?= t('new_user', $current_lang) ?>
                                </label>
                                <button type="button" class="btn btn-primary w-100 mt-3" data-bs-toggle="modal"
                                    data-bs-target="#registerModal">
                                    <i class="fas fa-user-plus me-2"></i>
                                    <?= t('register', $current_lang) ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registrazione -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">
                        <i class="fas fa-user-plus me-2"></i><?= t('register', $current_lang) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="registerForm" method="POST" action="register.php">
                    <div class="modal-body">
                        <div id="register-message" class="alert d-none" role="alert"></div>
                        <!-- CSRF Protection -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <div class="mb-3">
                            <label for="reg_username" class="form-label">
                                <i class="fas fa-user me-2"></i><?= t('username', $current_lang) ?>
                            </label>
                            <input type="text" name="username" id="reg_username" class="form-control" required
                                maxlength="50" pattern="[a-zA-Z0-9_]+" title="Solo lettere, numeri e underscore">
                        </div>
                        <div class="mb-3">
                            <label for="reg_email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <input type="email" name="email" id="reg_email" class="form-control" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="reg_password" class="form-label">
                                <i class="fas fa-key me-2"></i><?= t('password', $current_lang) ?>
                            </label>
                            <input type="password" name="password" id="reg_password" class="form-control" required
                                minlength="<?= PASSWORD_MIN_LENGTH ?>" maxlength="128">
                            <small class="form-text text-muted">
                                Minimo <?= PASSWORD_MIN_LENGTH ?> caratteri, con maiuscole, minuscole, numeri e caratteri speciali
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i><?= t('register', $current_lang) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animate elements on page load
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in-up');
            elements.forEach(element => {
                element.classList.add('animate');
            });

            // Gestione submit AJAX registrazione
            const regForm = document.getElementById('registerForm');
            const regMsg = document.getElementById('register-message');
            regForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                regMsg.classList.add('d-none');
                regMsg.classList.remove('alert-danger', 'alert-success');
                const formData = new FormData(regForm);
                const data = Object.fromEntries(formData.entries());
                try {
                    const resp = await fetch('register.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    const result = await resp.json();
                    regMsg.textContent = result.message;
                    regMsg.classList.remove('d-none');
                    if(result.success) {
                        regMsg.classList.add('alert-success');
                        regForm.reset();
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('registerModal'));
                            modal.hide();
                        }, 1500);
                    } else {
                        regMsg.classList.add('alert-danger');
                    }
                } catch (err) {
                    regMsg.textContent = 'Errore di comunicazione con il server.';
                    regMsg.classList.remove('d-none');
                    regMsg.classList.add('alert-danger');
                }
            });
        });
    </script>
</body>

</html>