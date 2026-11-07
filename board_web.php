<?php
session_start();
//不正アクセス処理
if(!isset($_SESSION['user_id'])){
    header('Location: board_login.php');
    exit;
}

$mode = '';
//ログイン引継ぎ
$user_id  = $_SESSION['user_id'];
$name = $_SESSION['name'];

//ログアウト
$logout = $_POST['logout']??'';

//新規投稿フォーム
$edit_id = $_POST['edit_id']??'';
$comment = $_POST['comment']??'';
$submit = $_POST['submit']??'';

//削除フォーム
$delete_id=$_POST['delete_id']??'';
$delete=$_POST['delete']??'';

//編集フォーム
$edit_pass = $_POST['edit_pass']??'';
$edit_num=$_POST['edit_num']??'';
$edit = $_POST['edit']??'';

//表示用変数
$message_edit_id = $edit_id;
$message_comment = $comment;
$message_delete_id = $delete_id;

//データベース接続
require_once __DIR__ . '/db.php';

//登録機能
function Add($pdo,$user_id,$comment){
    $sql = 'INSERT INTO simple_board_db(user_id,comment)
            VALUES(:user_id,:comment)';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id',$user_id,PDO::PARAM_INT);
    $stmt->bindParam(':comment',$comment,PDO::PARAM_STR);
    $stmt->execute();
}


//削除機能
function Delete($pdo,$delete_id){
    $sql = 'DELETE FROM simple_board_db WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id',$delete_id,PDO::PARAM_INT);
    $stmt->execute();
}

//編集準備処理
if(!empty($edit_num) && $edit=='編集'){
    $sql = 'SELECT user_id FROM simple_board_db WHERE id=:id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id',$edit_num,PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    if($result){
        $db_user_id = $result['user_id']; 
    }   
}

//削除準備処理
if(!empty($delete_id) && $delete=='削除'){
    $sql = 'SELECT user_id FROM simple_board_db WHERE id=:id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id',$delete_id,PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    if($result){
        $db_user_id = $result['user_id'];
    }   
}

//編集機能
function Edit($pdo,$edit_id,$user_id,$comment){
    $sql = 'UPDATE simple_board_db
            SET comment=:comment,is_edited=TRUE
            WHERE id=:id AND user_id=:user_id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':comment',$comment,PDO::PARAM_STR);
    $stmt->bindParam(':id',$edit_id,PDO::PARAM_INT);
    $stmt->bindParam(':user_id',$user_id,PDO::PARAM_INT);
    $stmt->execute();  
}

//テーブル初期化処理
function Drop_Table($pdo){
    $sql = 'DROP TABLE simple_board_db';
    $stmt = $pdo->query($sql);
}
//初期化したいとき下のコメントアウト外して
//Drop_Table($pdo);


//変数初期化処理
//データベース処理
if(!empty($logout)){
    //ログアウト処理
    session_destroy();
    header('Location: board_login.php');
    exit;
}
if(empty($edit_id) && !empty($comment) && $submit=='送信'){
    //新規データ登録処理
    $mode = 'add';
    Add($pdo,$user_id,$comment);
    $comment = '';
}
elseif (!empty($edit_id) && !empty($comment) && $submit=='送信'){
    //編集処理
    $mode = 'edit';
    Edit($pdo,$edit_id,$user_id,$comment);
    $comment = '';
    $edit_id='';
}
elseif(!empty($edit_num) && $edit =='編集'){
    //編集待機処理
    $sql = 'SELECT * FROM simple_board_db WHERE id=:id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id',$edit_num,PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    //結果が返ってきた場合
    if($result){
        //ログイン中のID と 投稿のuser_id が一致する場合
        if($user_id == $result['user_id']){
            $mode = 'wait_edit';
            $edit_id = $result['id'];
            $comment = $result['comment'];
        }
        else{
            $mode = 'error';
            $message = '選択した投稿の<strong>編集権限</strong>がありません';
        }
    }
    //返ってこなかった場合
    else{
        $mode = 'error';
        $message = '選択した投稿番号のコメントはありません';
    }
}
elseif(!empty($delete_id) && $delete=='削除'){
    if($result){
        if($db_user_id == $user_id){
            //削除処理
            $mode = 'delete';
            Delete($pdo,$delete_id);
            $comment = '';
        }
        else{
            //アカウントが違う場合  
            $mode = 'error';
            $message = '選択した投稿の<strong>削除権限</strong>がありません';
        }
    }
    else{
        $mode = 'error';
        $message = '選択した投稿番号のコメントはありません';
    }
    
}
?>

<!DOCTYPE html>
<html lang='ja'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width,initial-scale=1.0'>  
        <title>簡易掲示板(データベースVer)</title>
        <link rel='stylesheet' href='simple_board_style.css'>
    </head>

    <body>
        <header class="page-top">
            <p class="board-title">簡易掲示板(Ver2.1)</p>

            <div class="header-user">
                <p class="user-name"><?php echo $name; ?></p>
                <form action="" method="post">
                    <input type="submit" name="logout"
                        class="btn-logout" value="ログアウト">
                </form>
            </div>
            
        </header>
        
        <div class='form'>
            <form action='' method='post' class='post-form'>
                <!--入力フォーム-->
                <input type='hidden' name='edit_id' value='<?php echo $edit_id; ?>'>
                <textarea name='comment' class='input comment-input' placeholder='コメント'><?php echo $comment;?></textarea>
                <input type='submit' name='submit' value='送信' class='btn btn-submit'>
            </form>
        </div>
        
        <?php
        echo '<br />';
        if($mode == 'add'){
            //新規データ登録通知
            echo "<p class='message success'>「".$message_comment."」を受け付けました</p>";
        }
        elseif ($mode == 'edit'){
            //編集通知
            echo "<p class='message success'>投稿番号「".$message_edit_id."」の投稿を編集しました</p>";
        }
        elseif($mode == 'delete'){
            //削除通知
            echo "<p class='message success'>投稿番号「".$message_delete_id."」の投稿を削除しました</p>";
        }
        elseif($mode == 'wait_edit'){
            //編集待機
            echo "<p class='message info'>投稿番号「".$edit_id."」の投稿を編集中です。<br />コメントを編集して送信してください</p>";
        }
        elseif($mode =='error'){
            echo "<p class='message error'>".$message."</p>";
        }   
        else{
            echo "<p class='message error'>コメントを記入してください</p>";
        }
 
        //テーブル表示処理(逆順)
        $sql = 'SELECT
                simple_board_db.id AS id,
                simple_board_db.comment AS comment,
                simple_board_db.time AS time,
                simple_board_db.is_edited,
                users.id AS user_id,
                users.name AS name
                FROM simple_board_db
                JOIN users
                ON simple_board_db.user_id = users.id
                ORDER BY simple_board_db.id DESC';
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
        foreach($results as $row){
            ?>
            <div class='post-card'>
                <?php
                if($row['is_edited']==FALSE){
                    //編集歴なし
                    echo "<p class='header'>".$row['id'].": ".$row['name']." ".$row['time']."</p>";
                    echo "<p class = 'comment'>".nl2br($row['comment']).'</p>';
                }
                else{
                    //編集歴あり
                    echo "<p class='header'>".$row['id'].": ".$row['name']."(編集済み) ".$row['time']."</p>";
                    echo "<p class = 'comment'>".nl2br($row['comment']).'</p>';
                }
                //削除・編集ボタン表示
                if($row['user_id'] == $user_id){?>
                    <div class = 'post-actions'>
                        <form action='' method='post'>
                            <input type='hidden' name='edit_num' value='<?php echo $row['id']; ?>'>
                            <input type='submit' name='edit' value='編集' class='btn btn-edit'>
                        </form>
                        <form action='' method='post'>
                            <input type='hidden' name='delete_id' value='<?php echo $row['id']; ?>'>
                            <input type='submit' name='delete' value='削除' class='btn btn-delete'>
                        </form>
                    </div>
                 <?php
                }
                ?>
            </div>
        <?php
        }
        ?>
        
    </body>

</html>