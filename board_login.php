<?php
session_start();
$message = '';
$mode = '';
//受信
$name = $_POST['name']??'';
$pass = $_POST['pass']??'';
$submit = $_POST['submit']??'';


//データベース接続
require_once __DIR__ . '/db.php';

//初期化処理
//$sql = 'DELETE FROM users';
//$stmt = $pdo->query($sql);


//すべて入力されて送信された場合
if(!empty($submit) && !empty($name) && !empty($pass)){
    //検索
    $sql = 'SELECT id, pass FROM users
            WHERE name=:name';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name',$name,PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch();

    if($result){
        //既存ユーザーの場合
        if(password_verify($pass,$result['pass'])){
            //パスワードが登録情報と一致する場合
            $_SESSION['name'] = $name;
            $_SESSION['user_id'] = $result['id'];
            header('Location: board_web.php');
            exit;
        }
        else{
            //一致しない場合
            $mode = 'error';
            $message = 'パスワードが違います。再度確認して入力してください';
        }
    }
    else{
        //新規会員の場合
        //登録処理
        $mode = 'add';
        $hash_pass = password_hash($pass,PASSWORD_DEFAULT);
        $sql = 'INSERT INTO users(name,pass)
                VALUES(:name,:pass)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name',$name,PDO::PARAM_STR);
        $stmt->bindParam(':pass',$hash_pass,PDO::PARAM_STR);
        $stmt->execute();
        $message = '会員登録が完了しました。再度入力してログインしてください';
    }
}
elseif(!empty($submit) && (empty($name) || empty($pass))){
    $mode = 'error';
    $message = 'ユーザーネームとパスワードを入力してください';
}

?>

<!DOCTYPE html>
<html lang='ja'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width,initial-scale=1.0'>
        <title>簡易掲示板ログインフォーム</title>
        <link rel='stylesheet' href='login_style.css'>
    </head>

    <body>
        <div class='login-box'>
            <h2>掲示板ログイン</h2>

            <?php
            if ($mode == 'error'){ ?>
                <p class='error-message'> <?php echo $message; ?> </p>
            <?php }
            elseif ($mode == 'add'){ ?>
                <p class='add-message'> <?php echo $message; ?> </p>
            <?php }
            ?>

            <form action='' method='post' class='login-form'>
                <input type='text' name='name' class='input' placeholder='ユーザーネーム'>
                <input type='password' name='pass' class='input' placeholder='パスワード'>
                <input type='submit' name='submit' class='login-btn' value='ログイン'>
            </form>
        </div>

    </body>
</html>