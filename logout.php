<?php
// logout.php
session_start();
session_unset(); // セッション変数の削除
session_destroy(); // セッションの破棄
header("Location: top.php"); // ログインページへリダイレクト
exit();
?>
