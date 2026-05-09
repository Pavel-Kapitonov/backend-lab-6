<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

if (!empty($_SESSION['login'])) {
    header('Location: ./');
    exit;
}

$errors = !empty($_COOKIE['login_error']) ? $_COOKIE['login_error'] : '';
setcookie('login_error', '', 100000, '/'); 

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в личный кабинет</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="myform">
        <h2>Вход</h2>
        <?php if ($errors): ?>
            <div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 10px;">
                <?= htmlspecialchars($errors) ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <label>Логин:</label>
                <input name="login" type="text" required>
            </div>
            <div class="form-group">
                <label>Пароль:</label>
                <input name="pass" type="password" required>
            </div>
            <input class="knopka" type="submit" value="Войти">
        </form>
        <p><a href="./">Назад к регистрации</a></p>
    </div>
</body>
</html>
<?php
} else {
    $login = $_POST['login'];
    $pass = $_POST['pass'];

    $config = include('db_config.php');
    try {
        $db = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8", $config['user'], $config['pass']);
        
        $stmt = $db->prepare("SELECT * FROM Users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['request_id'];
            
            header('Location: ./');
        } else {
            setcookie('login_error', 'Неверный логин или пароль', 0, '/');
            header('Location: login.php');
        }
    } catch (PDOException $e) {
        print('Error : ' . $e->getMessage());
        exit;
    }
}