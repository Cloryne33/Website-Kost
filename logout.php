<?php
require_once 'includes/config.php';
startSession();
unset($_SESSION['user']);
$_SESSION['flash'] = ['type' => 'info', 'msg' => 'Anda berhasil logout. Sampai jumpa lagi!'];
header('Location: index.php');
exit;
