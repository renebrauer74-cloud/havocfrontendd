<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Havoc\Auth;

if (is_post()) {
    verify_csrf();
    Auth::logout();
}

redirect('admin/login.php');
