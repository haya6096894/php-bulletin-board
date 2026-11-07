
<?php

$dsn = 'pgsql:host=localhost;port=5432;dbname=my_board';

$user = 'board_app';

// 接続パスワードは環境変数から取得
$password = getenv('BOARD_DB_PASSWORD');

if ($password === false || $password === '') {
    throw new RuntimeException('DBパスワードが設定されていません');
}

$pdo = new PDO(
    $dsn,
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
?>