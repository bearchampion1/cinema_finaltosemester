<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/html; charset=utf-8');

$movies = [];
try {
    $stmt = $pdo->query("SELECT * FROM `movie` ORDER BY `上映日` DESC");
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

function poster_for(array $m) {
    if (!empty($m['IMG_URL'])) return $m['IMG_URL'];
    return 'https://via.placeholder.com/400x600?text=No+Poster';
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>🖼 電影一覽</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#111; color:#fff; font-family: "微軟正黑體"; }
.card { background:#1c1c1c; }
.card .card-text { color:#bbb; }
.movie-poster { width:100%; height:auto; object-fit:contain; cursor:pointer; }
.modal-img { width:100%; border-radius:12px; }
.card-title.mb-2 { color:#ffd700; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🎬 好秀電影院</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">🏠 首頁</a></li>
        <li class="nav-item"><a class="nav-link" href="user_search.php">🎬 查詢場次</a></li>
        <li class="nav-item"><a class="nav-link active" href="movie_direction.php">🖼 電影一覽</a></li>
        <li class="nav-item"><a class="nav-link" href="order_check.php">🎟 購票紀錄</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h2 class="fw-bold mb-4" style="color:#ffd700;">🖼 電影一覽</h2>
  <div class="row">
    <?php if (empty($movies)): ?>
      <div class="col-12 text-center text-secondary">目前沒有電影資料。</div>
    <?php else: foreach ($movies as $m): ?>
      <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
        <div class="card h-100 shadow">
          <img src="<?= htmlspecialchars(poster_for($m)) ?>" alt="<?= htmlspecialchars($m['片名']) ?>" class="card-img-top movie-poster" data-poster="<?= htmlspecialchars(poster_for($m)) ?>">
          <div class="card-body">
            <h5 class="card-title mb-2"><?= htmlspecialchars($m['片名']) ?></h5>
            <div class="card-text">上映日：<?= htmlspecialchars($m['上映日']) ?></div>
            <div class="card-text">片長：<?= htmlspecialchars($m['片長']) ?>分鐘</div>
            <div class="card-text">分級：<?= htmlspecialchars($m['分級']) ?></div>
            <div class="card-text">語言：<?= htmlspecialchars($m['語言']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Modal：顯示大圖（共用） -->
<div class="modal fade" id="imgModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark">
      <img id="modalImage" class="modal-img">
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 海報點擊放大
Document.prototype.ready = function(fn){ if(document.readyState !== 'loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
document.ready(() => {
  document.querySelectorAll('.movie-poster').forEach(img => {
    img.addEventListener('click', () => {
      document.getElementById('modalImage').src = img.dataset.poster;
      var modal = new bootstrap.Modal(document.getElementById('imgModal'));
      modal.show();
    });
  });
});
</script>
</body>
</html>
