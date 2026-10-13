<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Havoc\Auth;

if (Auth::check()) {
    redirect('admin/');
}

$error = null;
if (is_post()) {
    verify_csrf();
    if (Auth::attempt(input('username'), (string) ($_POST['password'] ?? ''))) {
        redirect('admin/');
    }
    sleep(1); // bremst Brute-Force-Versuche
    $error = 'Login fehlgeschlagen.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>HAVOC ADMIN | Login</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="admin-login">

    <div class="login-box">
        <div class="logo-container">
            <div class="logo-box">HAVOC</div>
            <div class="logo-sub">ADMIN LOGIN</div>
        </div>

<?php if ($error): ?>
        <div class="status-message is-error"><?= e($error) ?></div>
<?php endif; ?>

        <form method="post" action="<?= e(url('admin/login.php')) ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" value="<?= e(input('username')) ?>">
            </div>
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="submit-btn">ENTER BACKEND</button>
        </form>
    </div>

</body>
</html>
