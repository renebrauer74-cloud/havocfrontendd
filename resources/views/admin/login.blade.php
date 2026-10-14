<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>HAVOC ADMIN | Login</title>
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
</head>
<body class="admin-login">

    <div class="login-box">
        <div class="logo-container">
            <div class="logo-box">HAVOC</div>
            <div class="logo-sub">ADMIN LOGIN</div>
        </div>

@if ($errors->any())
        <div class="status-message is-error">{{ $errors->first() }}</div>
@endif

        <form method="post" action="{{ route('admin.login.store') }}">
            @csrf
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" value="{{ old('username') }}">
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
