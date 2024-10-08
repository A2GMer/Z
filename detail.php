<?php
session_start();

date_default_timezone_set("Asia/Tokyo");
require_once('config.php');

function dbConnect() {
    try {
        return new PDO(DSN, DB_USER, DB_PASS);
    } catch (PDOException $e) {
        return null;
    }
}

function sanitizeInput($input) {
    return htmlspecialchars($input, ENT_QUOTES, "UTF-8");
}

function handlePost($pdo, $id) {
    $error_messages = [];
    $escaped = [];

    if (empty($_POST["username"])) {
        if (isset($_SESSION['id'])) {
            // ログインしていれば、ユーザ名
            $escaped['username'] = $_SESSION['username'];
        } else {
            $escaped['username'] = "名無しさん";
        }
    } else {
        $escaped['username'] = sanitizeInput($_POST["username"]);
    }

    if (empty($_POST["comment"])) {
        $error_messages[] = "コメントを入力してください。";
    } else {
        $escaped['comment'] = sanitizeInput($_POST["comment"]);
    }

    if (empty($_POST["commenterId"])) {
        $error_messages[] = "IDが取得できなかった。";
    } else {
        $escaped['commenterId'] = sanitizeInput($_POST["commenterId"]);
    }

    if (empty($error_messages)) {
        $current_date = date("Y-m-d H:i:s");

        $pdo->beginTransaction();
        try {
            if (isset($_SESSION['id'])) {
                $statement = $pdo->prepare("INSERT INTO `z-comments` (commenterId, username, comment, post_date, feed_id, isLogin) VALUES (:commenterId, :username, :comment, :current_date, :feed_id, 1)");
            }else{
                $statement = $pdo->prepare("INSERT INTO `z-comments` (commenterId, username, comment, post_date, feed_id, isLogin) VALUES (:commenterId, :username, :comment, :current_date, :feed_id, 0)");
            }
            $statement->bindParam(':commenterId', $escaped["commenterId"], PDO::PARAM_STR);
            $statement->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
            $statement->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
            $statement->bindParam(':current_date', $current_date, PDO::PARAM_STR);
            $statement->bindParam(':feed_id', $id, PDO::PARAM_STR);

            $res = $statement->execute();
            $pdo->commit();

            if ($res) {
                header("Location: ./detail.php?" . $id);
                exit();
            } else {
                $error_messages[] = "書き込みに失敗しました。";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_messages[] = "書き込み中にエラーが発生しました。";
        }
    }

    return $error_messages;
}

function fetchMessages($pdo, $id, $table, $column) {
    $statement = $pdo->prepare("SELECT * FROM `$table` WHERE $column = :id");
    $statement->bindParam(':id', $id, PDO::PARAM_STR);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

$data = explode(",", $_SERVER['QUERY_STRING']);
$id = $data[0];

$pdo = dbConnect();
if (!$pdo) {
    die("データベース接続に失敗しました。");
}

$error_messages = [];
if (!empty($_POST["submitButton"])) {
    $error_messages = handlePost($pdo, $id);
}

$feed_message = fetchMessages($pdo, $id, 'z-feeds', 'id');
$comments = fetchMessages($pdo, $id, 'z-comments', 'feed_id');

$pdo = null;

// IDを付与(IP/ホスト名/日付をハッシュ化した末尾6文字)
function getId(){
    // クライアントのIPアドレスを取得
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    // クライアントのホスト名を取得
    $hostName = gethostbyaddr($ipAddress);

    // 現在の日付を yyyymmdd 形式で取得
    $date = date('Ymd');

    // IPアドレス、ホスト名、日にちを連結して文字列を作成
    $stringToHash = $ipAddress . $hostName . $date;

    // ハッシュを生成（SHA-256を使用）
    $hash = hash('sha256', $stringToHash);

    // ハッシュ値の末尾6桁を取得
    $last6Chars = substr($hash, -6);

    // return $stringToHash;
    return $last6Chars;
}

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
    <h1 class="title"><a class="non-hyperlink" href="index.php">Z</a></h1>
    <div class="boardWrapper">
        <section>
            <?php if (!empty($feed_message)) : ?>
                <?php foreach ($feed_message as $value) : ?>
                    <article class="commentsButton" data-id="<?php echo $value['id'] ?>">
                        <div class="wrapper">
                            <div class="nameArea">
                                <span>名前：</span>
                                <p class="username"><?php echo $value['username']; ?></p>
                                <time>：<?php echo date('Y/m/d H:i', strtotime($value['post_date'])); ?></time>
                            </div>
                            <div class="titleArea">
                                <?php echo htmlspecialchars($value['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <p class="comment" id="comment"><?php echo $value['comment']; ?></p>
                        </div>
                    </article>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <section>
            <?php if (!empty($comments)) : ?>
                <?php foreach ($comments as $value) : ?>
                    <article>
                        <div class="wrapper">
                            <div class="nameArea">
                                <span>名前：</span>
                                <p class="username"><?php echo $value['username']; ?></p>
                                <time>：<?php echo date('Y/m/d H:i', strtotime($value['post_date'])); ?></time>
                                <span>：ID：</span>
                                <p><?php echo $value['commenterId']; ?></p>
                            </div>
                            <p class="comment"><?php echo $value['comment']; ?></p>
                        </div>
                    </article>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <form method="POST" action="" class="formWrapper">
            <div>
                <?php if (!empty($error_messages)) : ?>
                    <?php foreach ($error_messages as $message) : ?>
                        <div class="error_message">※<?php echo $message; ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <input type="submit" value="書き込む" name="submitButton">
                <label>名前：</label>
                <?php if (isset($_SESSION['id'])) : ?>
                <input type="text" name="username" value="<?php echo $_SESSION['username']; ?>" readonly>
                <?php else: ?>
                <input type="text" name="username">
                <?php endif; ?>
                <label>ID：<?php echo getId(); ?></label>
                <input type="hidden" name="commenterId" value=<?php echo getId(); ?> />
            </div>
            <div>
                <textarea name="comment" class="commentTextArea"></textarea>
            </div>
        </form>
    </div>
    <script src="scripts-detail.js"></script>
</body>

</html>
