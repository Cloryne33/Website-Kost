<?php
$user = getLoggedInUser();
$active = $active ?? '';
$base   = $base   ?? '';
?>
<header class="navbar">
  <div class="container nav-content">
    <a href="<?= $base ?>index.php" class="logo-wrap">
      <img src="<?= $base ?>assets/images/logo-apik.png" alt="Apik Singgah Sini" class="logo-img">
      <div class="logo-text">
        <span class="brand-main">Apik</span>
        <span class="brand-sub">Singgah Sini</span>
      </div>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
      <span></span><span></span><span></span>
    </button>

    <nav id="navMenu">
      <a href="<?= $base ?>index.php"  <?= $active==='beranda' ?'class="nav-active"':'' ?>>Beranda</a>
      <a href="<?= $base ?>kamar.php"  <?= $active==='kamar'   ?'class="nav-active"':'' ?>>Kamar</a>
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="<?= $base ?>admin/dashboard.php" <?= $active==='admin'?'class="nav-active"':'' ?>>Dashboard</a>
        <?php endif; ?>
        <span class="nav-user">Halo, <strong><?= e($user['name']) ?></strong></span>
        <a href="<?= $base ?>logout.php">Logout</a>
      <?php else: ?>
        <a href="<?= $base ?>login.php"    <?= $active==='login'   ?'class="nav-active"':'' ?>>Login</a>
        <a href="<?= $base ?>register.php" <?= $active==='register'?'class="nav-active"':'' ?>>Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
document.getElementById('navToggle').addEventListener('click', function() {
  document.getElementById('navMenu').classList.toggle('nav-open');
  this.classList.toggle('active');
});
</script>
