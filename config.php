<?php
$dsn = "mysql:host=localhost;dbname=projecttosemesterend;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}
?>