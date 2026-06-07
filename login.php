<?php
require_once 'includes/config.php';
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
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role'  => $user['role'],
        ];
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Login berhasil! Selamat datang  , <strong>'.e($user['name']).'</strong>.'];
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

    <hr>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
