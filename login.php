<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/constants.php';

$errors = [];

// Hvis allerede innlogget → gå til index
if (is_logged_in()) {
    redirect(BASE_URL . '/index.php');
}

if (is_post()) {
    $email      = post_string('email');
    $password   = post_string('password');
    $rememberMe = isset($_POST['remember']) && (string)$_POST['remember'] === '1';

    if ($email === '' || $password === '') {
        $errors[] = 'E-post og passord må fylles ut.';
    } else {
        if (login($email, $password, $rememberMe)) {
            redirect(BASE_URL . '/index.php');
        } else {
            // Bevisst generell feilmelding (ikke avsløre kontoeksistens)
            $errors[] = 'Ugyldig kombinasjon av e-post og passord, eller bruker er deaktivert.';
        }
    }
}

$pageTitle = 'Logg inn';
require_once __DIR__ . '/includes/layout_start.php';
?>

<h1>Logg inn</h1>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= h($e); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="form-card" autocomplete="on">

    <div class="form-group">
        <label for="email">E-post</label>
        <input type="email"
               name="email"
               id="email"
               value="<?= h($_POST['email'] ?? ''); ?>"
               autofocus
               required
               autocomplete="username"
        >
    </div>

    <div class="form-group">
        <label for="password">Passord</label>
        <input type="password"
               name="password"
               id="password"
               required
               autocomplete="current-password"
        >
    </div>

    <div class="form-group form-inline">
        <label class="checkbox">
            <input type="checkbox" name="remember" value="1" <?= (!empty($_POST['remember']) ? 'checked' : ''); ?>>
            Husk meg
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Logg inn</button>
    </div>

</form>

<?php require_once __DIR__ . '/includes/layout_slutt.php'; ?>
