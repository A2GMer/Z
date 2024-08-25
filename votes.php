<?php
require_once('config.php');

// データベース接続関数
function dbConnect() {
    try {
        return new PDO(DSN, DB_USER, DB_PASS);
    } catch (PDOException $e) {
        global $error_message;
        $error_message[] = $e->getMessage();
        return null;
    }
}

// コメント数取得関数
function fetchCommentCount($pdo, $feed_id) {
    try {
        $sql = "SELECT COUNT(*) AS comment_count FROM `z-comments` WHERE feed_id = :feed_id";
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':feed_id', $feed_id, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetch(PDO::FETCH_ASSOC)['comment_count'];
        
        
    } catch (Exception $e) {
        echo 'Connection failed: ' . $e->getMessage();
    }
}

// いいね数取得関数
function fetchUpvoteCount($pdo, $feed_id) {
    try {
        $sql = "SELECT upvote FROM `z-feeds` WHERE id = :feed_id";
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':feed_id', $feed_id, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetch(PDO::FETCH_ASSOC)['upvote'];
        
        
    } catch (Exception $e) {
        echo 'Connection failed: ' . $e->getMessage();
    }
}

// 投票更新関数
function updateFeeds($pdo, $id, $mode) {
    global $error_message;

    try {
        $column = $mode == "1" ? "upvote" : "downvote";
        $statement = $pdo->prepare("UPDATE `z-feeds` SET `$column` = $column + 1 WHERE id = :id");
        $statement->bindParam(':id', $id, PDO::PARAM_INT);

        $pdo->beginTransaction();
        $res = $statement->execute();
        $pdo->commit();
        
        return $res ? fetchUpvoteCount($pdo, $id) : "取得不可";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message[] = $e->getMessage();
        return "投票の更新に失敗しました。";
    }
}

// PDO接続
$pdo = dbConnect();
$data = explode(",", $_SERVER['QUERY_STRING']);
// feedのID
$id = $data[0];

$success_message = updateFeeds($pdo, $id, "1");

echo $success_message;
return $success_message;

?>