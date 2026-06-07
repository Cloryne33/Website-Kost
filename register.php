<?php
require_once 'includes/config.php';
startSession();

if (getLoggedInUser()) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$name || !$email || !$phone || !$password) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Semua data wajib diisi.'];
        header('Location: register.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Password minimal 6 karakter.'];
        header('Location: register.php');
        exit;
    }

    $db  = getDB();
    $chk = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $chk->execute([$email]);
    if ($chk->fetch()) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Email sudah terdaftar. Gunakan email lain.'];
        header('Location: register.php');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins  = $db->prepare('INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?,?)');
    $ins->execute([$name, $email, $phone, $hash, 'user']);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Registrasi berhasil! Silakan login.'];
    header('Location: login.php');
    exit;
}

$active = 'register';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container section">
  <div class="auth-card">
    <h2>Buat Akun</h2>
    <p class="text-muted">Daftar untuk mulai booking kamar dengan mudah.</p>

    <form method="POST">
      <div class="form-group">
        <label for="name">Nama Lengkap</label>
        <input type="text" id="name" name="name"
               value="<?= e($_POST['name'] ?? '') ?>"
               placeholder="Masukkan nama lengkap" required>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>"
               placeholder="Masukkan email" required>
      </div>
      <div class="form-group">
        <label for="phone">No. WhatsApp</label>
        <input type="text" id="phone" name="phone"
               value="<?= e($_POST['phone'] ?? '') ?>"
               placeholder="Contoh: 6285212345678" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Minimal 6 karakter" required>
      </div>
      <button type="submit" class="btn-primary full-width">Daftar Sekarang</button>
    </form>

    <div class="auth-link">
      Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
