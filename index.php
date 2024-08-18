<?php
date_default_timezone_set("Asia/Tokyo");

// Initialize variables
$success_message = null;
$error_message = [];
$pdo = null;
$escaped = [];


function dbConnect() {
    try {
        return new PDO('mysql:charset=UTF8;dbname=z;host=localhost', 'root', 'root');
    } catch (PDOException $e) {
        global $error_message;
        $error_message[] = $e->getMessage();
        return null;
    }
}

function validateInput($input_name, $error_message, &$escaped) {
    if (empty($_POST[$input_name])) {
        $error_message[] = $input_name === "username" ? "お名前を入力してください。" : "コメントを入力してください。";
    } else {
        $escaped[$input_name] = htmlspecialchars($_POST[$input_name], ENT_QUOTES, "UTF-8");
    }
}

function insertComment($pdo, $escaped) {
    $current_date = date("Y-m-d H:i:s");
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare("INSERT INTO `z-feeds` (username, comment, post_date) VALUES (:username, :comment, :current_date)");
        $statement->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
        $statement->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
        $statement->bindParam(':current_date', $current_date, PDO::PARAM_STR);
        $res = $statement->execute();
        $pdo->commit();
        return $res ? "コメントを書き込みました。" : "書き込みに失敗しました。";
    } catch (Exception $e) {
        $pdo->rollBack();
        return "書き込みに失敗しました。";
    }
}

function updateFeeds($pdo, $escaped, &$error_message, $mode) {
    $pdo->beginTransaction();
    try {
        if ($mode == "1"){
            $statement = $pdo->prepare("UPDATE `z-feeds` SET `upvote` = upvote + 1 WHERE id = :id");
        }else{
            $statement = $pdo->prepare("UPDATE `z-feeds` SET `downvote` = downvote + 1 WHERE id = :id");
        }
        
        
        $statement->bindParam(':id', $escaped["id"], PDO::PARAM_STR);
        $res = $statement->execute();
        $pdo->commit();
        return $res ? "投票を更新しました。" : "投票の更新に失敗しました。";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message[] = $e->getMessage();
        return "投票の更新に失敗しました。";
    }
}

function fetchMessages($pdo) {
    $sql = "SELECT * FROM `z-feeds` ORDER BY upvote DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function fetchCommentCount($pdo, $feed_id) {
    $sql = "SELECT COUNT(*) AS comment_count FROM `z-comments` WHERE feed_id = :feed_id";
    $statement = $pdo->prepare($sql);
    $statement->bindParam(':feed_id', $feed_id, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC)['comment_count'];
}

$pdo = dbConnect();

if (!empty($_POST["submitButton"])) {
    validateInput("username", $error_message, $escaped);
    validateInput("comment", $error_message, $escaped);

    if (empty($error_message)) {
        $success_message = insertComment($pdo, $escaped);
        if ($success_message === "コメントを書き込みました。") {
            header("Location: ./index.php");
            exit();
        } else {
            $error_message[] = $success_message;
        }
    }
}

if (!empty($_POST["upVoteButton"])) {
    $escaped["id"] = htmlspecialchars($_POST["id"], ENT_QUOTES, "UTF-8");
    $success_message = updateFeeds($pdo, $escaped, $error_message, "1");
    if ($success_message === "投票を更新しました。") {
        header("Location: ./index.php");
        exit();
    } else {
        $error_message[] = $success_message;
    }
}

if (!empty($_POST["downVoteButton"])) {
    $escaped["id"] = htmlspecialchars($_POST["id"], ENT_QUOTES, "UTF-8");
    $success_message = updateFeeds($pdo, $escaped, $error_message, "2");
    if ($success_message === "投票を更新しました。") {
        header("Location: ./index.php");
        exit();
    } else {
        $error_message[] = $success_message;
    }
}

$message_array = fetchMessages($pdo);

$pdo = null;
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1 class="title">Z</h1>
    <hr>
    <div class="boardWrapper">
        <!-- Success message -->
        <?php if (!empty($success_message)) : ?>
            <p class="success_message"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <!-- Validation errors -->
        <?php if (!empty($error_message)) : ?>
            <?php foreach ($error_message as $value) : ?>
                <div class="error_message">※<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <section>
            <?php if (!empty($message_array)) : ?>
                <?php foreach ($message_array as $value) : ?>
                    <article class="float3">
                        <a class="non-hyperlink" href="detail.php?<?php echo htmlspecialchars($value['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="wrapper">
                                <div class="nameArea">
                                    <span>名前：</span>
                                    <p class="username"><?php echo htmlspecialchars($value['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <time>：<?php echo date('Y/m/d H:i', strtotime($value['post_date'])); ?></time>
                                </div>
                                <p class="comment"><?php echo htmlspecialchars($value['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <form method="POST" action="">
                                <section>
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($value['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="submit" name="upVoteButton" value="↑"></button>
                                    <?php echo htmlspecialchars($value['upvote'], ENT_QUOTES, 'UTF-8'); ?>
                                    <input type="submit" name="downVoteButton" value="↓"></button>
                                    コメント数：<?php 
                                        $pdo = dbConnect();
                                        echo fetchCommentCount($pdo, $value['id']);
                                        $pdo = null;
                                    ?>
                                </section>
                            </form>
                        </a>
                    </article>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <form method="POST" action="" class="formWrapper">
                <div>
                    <input type="submit" value="書き込む" name="submitButton">
                    <label>名前：</label>
                    <input type="text" name="username">
                </div>
                <div>
                    <textarea name="comment" class="commentTextArea"></textarea>
                </div>
            </form>
        </section>
    </div>
</body>

</html>
