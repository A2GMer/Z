<?php
date_default_timezone_set("Asia/Tokyo");

// 初期化
$success_message = null;
$error_message = [];
$pdo = null;
$escaped = [];

// データベース接続関数
function dbConnect() {
    $dsn = 'mysql:charset=UTF8;dbname=z;host=localhost';
    $username = 'root';
    $password = 'root';
    
    try {
        return new PDO($dsn, $username, $password);
    } catch (PDOException $e) {
        global $error_message;
        $error_message[] = $e->getMessage();
        return null;
    }
}

// 投稿メッセージ取得関数
function fetchMessages($pdo) {
    $sql = "SELECT * FROM `z-feeds` ORDER BY upvote DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


// PDO接続
$pdo = dbConnect();

$records = fetchMessages($pdo);
// 結果をJSON形式で返す
header('Content-Type: application/json');
echo json_encode($records);
?>
