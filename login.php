<?php

require_once('config.php');

session_start();
//メールアドレスのバリデーション
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
  echo '入力された値が不正です。';
  return false;
}
//DB内でPOSTされたメールアドレスを検索
try {
  $pdo = new PDO(DSN, DB_USER, DB_PASS);
  $stmt = $pdo->prepare('select * from `z-userdata` where `email` = :email');
  $stmt->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  
} catch (\Exception $e) {
  echo $e->getMessage() . PHP_EOL;
}

//emailがDB内に存在しているか確認
if (!isset($row['email'])) {
  echo 'メールアドレス又はパスワードが間違っています。';
  return false;
}
//パスワード確認後sessionにメールアドレスを渡す
if (password_verify($_POST['password'], $row['password'])) {
  session_regenerate_id(true); //session_idを新しく生成し、置き換える
  session_start();
  $_SESSION['id'] = $row['id'];
  $_SESSION['username'] = $row['username'];
?>
<script type="text/javascript">
  window.location.href = 'index.php';
</script>
<?php
} else {
  echo 'メールアドレス又はパスワードが間違っています。';
  return false;
}