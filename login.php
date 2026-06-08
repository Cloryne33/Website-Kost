<?php
require_once 'includes/config.php';
require_once 'includes/google_config.php';
startSession();

if (getLoggedInUser()) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Email dan password wajib diisi.'];
        header('Location: login.php');
        exit;
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id'            => $user['id'],
            'name'          => $user['name'],
            'email'         => $user['email'],
            'phone'         => $user['phone'],
            'role'          => $user['role'],
            'avatar'        => $user['avatar'] ?? '',
            'auth_provider' => $user['auth_provider'] ?? 'email',
        ];
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Login berhasil! Selamat datang, <strong>'.e($user['name']).'</strong>.'];
        if ($user['role'] === 'admin') header('Location: admin/dashboard.php');
        else                           header('Location: kamar.php');
        exit;
    }

    $_SESSION['flash'] = ['type'=>'error','msg'=>'Email atau password salah.'];
    header('Location: login.php');
    exit;
}

$active = 'login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?= SITE_NAME ?></title>
  <link rel="icon" type="image/png" href="assets/images/logo-apik.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container section">
  <div class="auth-card">
    <h2>Masuk Akun</h2>
    <p class="text-muted">Login untuk melakukan booking kamar.</p>

    <form method="POST">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               placeholder="Masukkan email" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn-primary full-width">Login</button>
    </form>

    <div class="auth-link">
      Belum punya akun? <a href="register.php">Daftar di sini</a>
    </div>

    <div class="or-divider"><span>atau</span></div>

    <a href="<?= e(getGoogleAuthUrl()) ?>" class="btn-google full-width">
      <svg width="20" height="20" viewBox="0 0 48 48"><fill="#4285F4" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><fill="#34A853" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><fill="#FBBC05" d="M10.54 28.59A14.5 14.5 0 0 1 9.5 24c0-1.59.28-3.14.76-4.59l-7.98-6.19A23.99 23.99 0 0 0 0 24c0 3.77.87 7.35 2.56 10.54l7.98-5.95z"/><fill="#EA4335" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 5.95C6.51 42.62 14.62 48 24 48z"/></svg>
      Masuk dengan Google
    </a>

    <hr>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
