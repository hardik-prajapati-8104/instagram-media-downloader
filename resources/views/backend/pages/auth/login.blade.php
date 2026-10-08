<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: "Lexend Deca", sans-serif; background: linear-gradient(180deg,#fff7ed,#fff); min-height:100vh; display:grid; place-items:center; }
        .card { border:1px solid #fed7aa; border-radius:1rem; width:min(420px,92vw); }
        .btn-primary { background:#f97316; border-color:#f97316; } .btn-primary:hover { background:#ea580c; border-color:#ea580c; }
        .form-control:focus { border-color:#fb923c; box-shadow:0 0 0 .2rem rgba(249,115,22,.2); }
    </style>
</head>
<body>
    <div class="card p-4 shadow-sm">
        <h1 class="h4 fw-bold mb-1">Admin Login</h1>
        <p class="text-muted small mb-4">Instagram Media Downloader</p>
        @include('backend.layouts.partials.messages')
        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
            <div class="mb-3"><label class="form-label" for="password">Password</label><input class="form-control" id="password" name="password" type="password" required autocomplete="current-password"></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="remember" value="1"><label class="form-check-label" for="remember">Remember me</label></div>
            <button class="btn btn-primary w-100" type="submit">Sign in</button>
        </form>
    </div>
</body>
</html>
