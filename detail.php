<?php
date_default_timezone_set("Asia/Tokyo");
$data= explode(",",$_SERVER['QUERY_STRING']);
$id = $data[0];

//データベース接続
try {
    $pdo = new PDO('mysql:charset=UTF8;dbname=z;host=localhost', 'root', 'root');
} catch (PDOException $e) {
    //接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

//送信して受け取ったデータは$_POSTの中に自動的に入る。
//投稿データがあるときだけログを表示する。
if (!empty($_POST["submitButton"])) {

    //表示名の入力チェック
    if (empty($_POST["username"])) {
        $error_message[] = "お名前を入力してください。";
    } else {
        $escaped['username'] = htmlspecialchars($_POST["username"], ENT_QUOTES, "UTF-8");
    }

    //コメントの入力チェック
    if (empty($_POST["comment"])) {
        $error_message[] = "コメントを入力してください。";
    } else {
        $escaped['comment'] = htmlspecialchars($_POST["comment"], ENT_QUOTES, "UTF-8");
    }

    //エラーメッセージが何もないときだけデータ保存できる
    if (empty($error_message)) {
        //ここからDB追加のときに追加
        $current_date = date("Y-m-d H:i:s");

        //トランザクション開始
        $pdo->beginTransaction();

        try {

            //SQL作成
            $statment = $pdo->prepare("INSERT INTO `z-comments` (username, comment, post_date, feed_id) VALUES (:username, :comment, :current_date, :feed_id)");

            //値をセット
            $statment->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
            $statment->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
            $statment->bindParam(':current_date', $current_date, PDO::PARAM_STR);
            $statment->bindParam(':feed_id', $id, PDO::PARAM_STR);

            //SQLクエリの実行
            $res = $statment->execute();

            //ここまでエラーなくできたらコミット
            $res = $pdo->commit();
        } catch (Exception $e) {
            //エラーが発生したときはロールバック(処理取り消し)
            $pdo->rollBack();
        }

        if ($res) {
            $success_message = "コメントを書き込みました。";
        } else {
            $error_message[] = "書き込みに失敗しました。";
        }

        $statment = null;

        // POST処理の最後にリダイレクト処理
        header("Location:./detail.php?".$id);
        exit();
    }
}


//DBからコメントデータを取得する
$sql1 = "SELECT username, comment, post_date FROM `z-comments` Where feed_id=$id";
$message_array1 = $pdo->query($sql1);

$sql2 = "SELECT username, comment, post_date FROM `z-feed` Where id=$id";
$message_array2 = $pdo->query($sql2);


//DB接続を閉じる
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
    <h1 class="title"><a class="non-hyperlink" href="index.php">Z</a></h1>
    <div class="boardWrapper">
        <section>
            <?php if (!empty($message_array2)) : ?>
                <?php foreach ($message_array2 as $value) : ?>
                    <article>
                        <div class="wrapper">
                            <div class="nameArea">
                                <span>名前：</span>
                                <p class="username"><?php echo $value['username'] ?></p>
                                <time>：<?php echo date('Y/m/d H:i', strtotime($value['post_date'])); ?></time>
                            </div>
                            <p class="comment"><?php echo $value['comment']; ?></p>
                        </div>
                    </article>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <section>
            <?php if (!empty($message_array1)) : ?>
                <?php foreach ($message_array1 as $value) : ?>
                    <article>
                        <div class="wrapper">
                            <div class="nameArea">
                                <span>名前：</span>
                                <p class="username"><?php echo $value['username'] ?></p>
                                <time>：<?php echo date('Y/m/d H:i', strtotime($value['post_date'])); ?></time>
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
            <!-- バリデーションチェック時 -->
            <?php if (!empty($error_message)) : ?>
            <?php foreach ($error_message as $value) : ?>
            <div class="error_message">※<?php echo $value; ?></div>
            <?php endforeach; ?>
            <?php endif; ?>
            <input type="submit" value="書き込む" name="submitButton">
            <label>名前：</label>
            <input type="text" name="username">
        </div>
        <div>
            <textarea name="comment" class="commentTextArea"></textarea>
        </div>
    </form>
    </div>
</body>

</html>