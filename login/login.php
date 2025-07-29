<?php
require_once __DIR__ . '/auth.php';

// Se già loggato, vai alla home
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Credenziali non valide.';
        }
    } else {
        $error = 'Inserisci username e password.';
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
        'register' => 'Register',
        'new_user' => 'New user?'
    ]
];

function t($key, $lang = 'it')
{
    global $translations;
    return $translations[$lang][$key] ?? $key;
}
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
                                <?= htmlspecialchars(t($error === 'Credenziali non valide.' ? 'invalid_credentials' : 'enter_credentials', $current_lang)) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="fade-in-up">
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>
                                    <?= t('username', $current_lang) ?>
                                </label>
                                <input type="text" name="username" id="username" class="form-control" required
                                    autofocus>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-key me-2"></i>
                                    <?= t('password', $current_lang) ?>
                                </label>
                                <input type="password" name="password" id="password" class="form-control" required>
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
                        <div class="mb-3">
                            <label for="reg_username" class="form-label">
                                <i class="fas fa-user me-2"></i><?= t('username', $current_lang) ?>
                            </label>
                            <input type="text" name="username" id="reg_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="reg_email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <input type="email" name="email" id="reg_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="reg_password" class="form-label">
                                <i class="fas fa-key me-2"></i><?= t('password', $current_lang) ?>
                            </label>
                            <input type="password" name="password" id="reg_password" class="form-control" required>
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