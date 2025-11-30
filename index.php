<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>🎬 電影院線上購票系統</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  font-family: "微軟正黑體", sans-serif;
  background: linear-gradient(180deg, #1c1c1c 0%, #3a3a3a 100%);
  color: white;
  text-align: center;
  min-height: 100vh;
}

/* 中間按鈕區 */
.container-box {
  display: flex;
  justify-content: center;
  gap: 40px;
  margin-top: 60px;
}
.box {
  background: white;
  color: black;
  border-radius: 12px;
  padding: 25px 30px;
  width: 260px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  transition: 0.3s;
}
.box:hover { transform: translateY(-5px); }

/* 跑馬燈 */
.marquee-container {
  margin-top: 80px;
  overflow: hidden;
  white-space: nowrap;
  background: #111;
  padding: 20px 0;
}
.marquee-track {
  display: inline-flex;
  animation: scroll 45s linear infinite;
}
.marquee-container:hover .marquee-track {
  animation-play-state: paused;
}

.marquee-track img {
  width: 200px;
  height: 300px;
  object-fit: cover;
  margin: 0 15px;
  border-radius: 10px;
  cursor: pointer;
  transition: transform 0.3s;
}
.marquee-track img:hover {
  transform: scale(1.1);
}

@keyframes scroll {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}

/* Modal 大圖 */
.modal-img {
  width: 100%;
  border-radius: 12px;
}
</style>
</head>

<body>

<h1 class="fw-bold mt-4">🎬 電影院線上購票系統</h1>
<p class="text-secondary">快速查詢場次、線上選座、即時購票，一站完成！</p>

<div class="container-box">
  <div class="box">
    <h5>🎟 購票者介面</h5>
    <p>立即查詢電影場次、選擇座位、線上付款。</p>
    <a href="user_search.php" class="btn btn-success w-100">進入購票介面</a>
  </div>

  <div class="box">
    <h5>🛠 管理員登入</h5>
    <p>後台維護電影、場次、影廳、訂單與付款資料。</p>
    <a href="admin/index.php" class="btn btn-primary w-100">進入管理後台</a>
  </div>
</div>

<!-- 🎞 電影圖片 + 點擊播放預告 -->
<div class="marquee-container mt-5">
  <div class="marquee-track">

    <!-- 海報 + 預告 -->
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251103043.jpg"
         data-trailer="https://youtu.be/hjcMMIPRlTY">

    <img src="https://www.vscinemas.com.tw/upload/film/film_20251104008.jpg"
         data-trailer="https://youtu.be/MGSGx36-TV4">

    <img src="https://www.vscinemas.com.tw/upload/film/film_20250702001.jpg"
         data-trailer="https://youtu.be/9UgBN-tUGDY">

    <!-- 以下無預告 → 自動顯示大圖 -->
    <img src="https://www.vscinemas.com.tw/upload/film/film_20250428011.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251027008.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251008015.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20250903048.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20250815002.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251009003.jpg">

    <!-- 重複一次無縫播放 -->
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251103043.jpg"
         data-trailer="https://youtu.be/hjcMMIPRlTY">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251104008.jpg"
         data-trailer="https://youtu.be/MGSGx36-TV4">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20250815002.jpg"
         data-trailer="https://youtu.be/9UgBN-tUGDY">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20250428011.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251027008.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251008015.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20250903048.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20250702001.jpg">
    <img src="https://www.vscinemas.com.tw/upload/film/film_20251009003.jpg">
  </div>
</div>

<!-- Modal：顯示大圖 -->
<div class="modal fade" id="imgModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark">
      <img id="modalImage" class="modal-img">
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// 點擊海報 → 播放影片 or 顯示大圖
document.querySelectorAll('.marquee-track img').forEach(img => {
  img.addEventListener('click', () => {
    const trailer = img.dataset.trailer;

    if (trailer) {
      // 📌 Redirect to YouTube 預告片
      window.open(trailer, "_blank");
    } else {
      // 📌 沒預告 → 顯示大圖
      document.getElementById("modalImage").src = img.src;
      var modal = new bootstrap.Modal(document.getElementById('imgModal'));
      modal.show();
    }
  });
});
</script>

<!-- 單行橫幅（Banner） -->
<style>
  .site-banner {
    width: 100%;
    background: linear-gradient(90deg, rgba(40,40,40,1), rgba(25,25,25,1));
    color: #fff;
    padding: 14px 10px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
    position: relative;
    margin-top: 40px;
  }
  .site-banner .container { display:flex; align-items:center; justify-content:center; gap:12px; }
  .site-banner .brand { font-weight:700; letter-spacing:0.3px; }
  .site-banner .credits { color: rgba(255,255,255,0.85); font-size:0.95rem; }
  @media (max-width:576px){
    .site-banner .container{ flex-direction:column; gap:6px; padding:6px 0; }
  }
</style>

<div class="site-banner">
  <div class="container">
    <div class="credits">&copy; 2025 電影院線上購票系統</div>
    <div class="brand">製作人：熊亮凱 + chatGPT</div>
  </div>
</div>

</body>
</html>
