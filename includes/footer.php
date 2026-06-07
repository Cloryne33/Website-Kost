<?php
$popupData = null;

if (isset($_SESSION['flash'])) {
    $popupData = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> <strong><?= SITE_NAME ?></strong>. All rights reserved.</p>
</footer>

<?php if ($popupData):
  $icon = $popupData['type'] === 'success' ? '✓' : ($popupData['type'] === 'error' ? '✕' : 'ℹ');
  $title = $popupData['type'] === 'success' ? 'Berhasil!' : ($popupData['type'] === 'error' ? 'Gagal!' : 'Info');
  $btnLabel = $popupData['type'] === 'info' ? 'OK' : 'Tutup';
?>
<div class="popup-overlay" id="popupOverlay" onclick="if(event.target===this){this.classList.remove('active');document.body.style.overflow=''}">
  <div class="popup-modal">
    <div class="popup-icon-wrap">
      <div class="popup-icon <?= e($popupData['type']) ?>"><?= $icon ?></div>
    </div>
    <h3><?= $title ?></h3>
    <p><?= $popupData['msg'] ?></p>
    <button class="btn-primary" onclick="var o=document.getElementById('popupOverlay');o.classList.remove('active');document.body.style.overflow=''"><?= $btnLabel ?></button>
  </div>
</div>
<script>(function(){var o=document.getElementById('popupOverlay');o.classList.add('active');document.body.style.overflow='hidden'})()</script>
<?php endif; ?>
