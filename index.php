<?php
date_default_timezone_set("Asia/Tokyo");

// Initialize variables
$current_date = null;
$message = [];
$message_array = [];
$success_message = null;
$error_message = [];
$escaped = [];
$pdo = null;
$statement = null;
$res = null;

// Database connection
try {
    $pdo = new PDO('mysql:charset=UTF8;dbname=z;host=localhost', 'root', 'root');
} catch (PDOException $e) {
    $error_message[] = $e->getMessage();
}

// Handle form submission
if (!empty($_POST["submitButton"])) {
    // Validate username
    if (empty($_POST["username"])) {
        $error_message[] = "お名前を入力してください。";
    } else {
        $escaped['username'] = htmlspecialchars($_POST["username"], ENT_QUOTES, "UTF-8");
    }

    // Validate comment
    if (empty($_POST["comment"])) {
        $error_message[] = "コメントを入力してください。";
    } else {
        $escaped['comment'] = htmlspecialchars($_POST["comment"], ENT_QUOTES, "UTF-8");
    }

    // If no validation errors, save data
    if (empty($error_message)) {
        $current_date = date("Y-m-d H:i:s");

        $pdo->beginTransaction();
        try {
            // Prepare SQL statement
            $statement = $pdo->prepare("INSERT INTO `z-feeds` (username, comment, post_date) VALUES (:username, :comment, :current_date)");

            // Bind parameters
            $statement->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
            $statement->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
            $statement->bindParam(':current_date', $current_date, PDO::PARAM_STR);

            // Execute SQL query
            $res = $statement->execute();

            // Commit transaction if successful
            $pdo->commit();
        } catch (Exception $e) {
            // Rollback on error
            $pdo->rollBack();
        }

        if ($res) {
            $success_message = "コメントを書き込みました。";
        } else {
            $error_message[] = "書き込みに失敗しました。";
        }

        $statement = null;

        // Redirect after POST processing
        header("Location:./index.php");
        exit();
    }
}

// Fetch comment data from database
$sql = "SELECT * FROM `z-feeds` ORDER BY upvote DESC";
$message_array = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Close database connection
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
            <p class="success_message"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <!-- Validation errors -->
        <?php if (!empty($error_message)) : ?>
            <?php foreach ($error_message as $value) : ?>
                <div class="error_message">※<?php echo $value; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <section>
            <?php if (!empty($message_array)) : ?>
                <?php foreach ($message_array as $value) : ?>
                    <article class="float3">
                        <a class="non-hyperlink" href="detail.php?<?php echo $value['id']; ?>">
                            <div class="wrapper">
                                <div class="nameArea">
                                    <span>名前：</span>
                                    <p class="username"><?php echo htmlspecialchars($value['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <time>：<?php echo date('Y/m/d H:i', strtotime($value['post_date'])); ?></time>
                                </div>
                                <p class="comment"><?php echo htmlspecialchars($value['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </a>
                        <form method="POST" action="">
                            <section>
                                <button type="submit" name="upVoteButton">↑</button>
                                <?php echo $value['upvote']; ?>
                                <button type="submit" name="downVoteButton">↓</button>
                            </section>
                        </form>
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