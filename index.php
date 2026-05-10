<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

$errors = !empty($_COOKIE['errors']) ? json_decode($_COOKIE['errors'], true) : [];
$values = !empty($_COOKIE['values']) ? json_decode($_COOKIE['values'], true) : [];
$show_success = !empty($_COOKIE['success']);


if (empty($errors) && !empty($_SESSION['login'])) {
    $config = include('db_config.php');
    try {
        $db = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8", $config['user'], $config['pass']);
        
        $stmt = $db->prepare("SELECT * FROM Request WHERE request_id = ?");
        $stmt->execute([$_SESSION['uid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $values['fio'] = $row['name'];
            $values['phone'] = $row['tel'];
            $values['email'] = $row['email'];
            $values['dateborn'] = $row['dateborn'];
            $values['gender'] = $row['gender'];
            $values['bio'] = $row['bio'];
            $values['agreement'] = $row['agreed'];
        }

        $stmt = $db->prepare("SELECT l.language_name FROM Connection c 
                              JOIN Language l ON c.language_id = l.language_id 
                              WHERE c.request_id = ?");
        $stmt->execute([$_SESSION['uid']]);
        $values['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        print('Error : ' . $e->getMessage());
        exit;
    }
}
elseif (empty($values)) {
    $values['fio'] = $_COOKIE['fio_value'] ?? '';
    $values['phone'] = $_COOKIE['phone_value'] ?? '';
    $values['email'] = $_COOKIE['email_value'] ?? '';
    $values['dateborn'] = $_COOKIE['dateborn_value'] ?? '2006-08-13';
    $values['gender'] = $_COOKIE['gender_value'] ?? 'M';
    $values['bio'] = $_COOKIE['bio_value'] ?? '';
    $values['agreement'] = $_COOKIE['agreement_value'] ?? '';
    $values['languages'] = !empty($_COOKIE['languages_value']) ? explode(',', $_COOKIE['languages_value']) : [];   
}

setcookie('errors', '', 100000, '/');
setcookie('values', '', 100000, '/');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма Лаба 4</title>
    <link rel="stylesheet" href="css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        .error { border: 2px solid red; background-color: #ffe6e6; }
        .error-msg { color: red; font-size: 0.8em; }
        .error-header { color: red; border: 1px solid red; padding: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="myform">

        <div style="text-align: right;">
            <?php if (!empty($_SESSION['login'])): ?>
                Вы вошли как: <strong><?= $_SESSION['login'] ?></strong> | <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="login.php">Войти</a>
            <?php endif; ?>
        </div>

        <h2>Форма регистрации</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-header">Исправьте ошибки в полях ниже</div>
        <?php endif; ?>

        <?php if ($show_success): ?>
            <div class="success-msg">
                Данные успешно сохранены! <br>
                <?php if (!empty($_COOKIE['pass'])): ?>
                    Вы можете <a href="login.php">войти</a> с логином <strong><?= htmlspecialchars($_COOKIE['login']) ?></strong> 
                    и паролем <strong><?= htmlspecialchars($_COOKIE['pass']) ?></strong> для изменения данных.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="handler.php" method="POST">
            
            <div class="form-group">
                <label>ФИО:</label>
                <input name="fio" type="text" 
                       value="<?= htmlspecialchars($values['fio']) ?>" 
                       class="<?= isset($errors['fio']) ? 'error' : '' ?>">
                <?php if (isset($errors['fio'])) echo '<div class="error-msg">'.$errors['fio'].'</div>'; ?>
            </div>

            <div class="form-group">
                <label>Телефон:</label>
                <input name="phone" type="tel" 
                       value="<?= htmlspecialchars($values['phone']) ?>" 
                       class="<?= isset($errors['phone']) ? 'error' : '' ?>">
                <?php if (isset($errors['phone'])) echo '<div class="error-msg">'.$errors['phone'].'</div>'; ?>
            </div>

            <div class="form-group">
                <label>E-mail:</label>
                <input name="email" type="email" 
                       value="<?= htmlspecialchars($values['email']) ?>" 
                       class="<?= isset($errors['email']) ? 'error' : '' ?>">
                <?php if (isset($errors['email'])) echo '<div class="error-msg">'.$errors['email'].'</div>'; ?>
            </div>

            <div class="form-group">
                <label>Дата рождения:</label>
                <input name="dateborn" type="date" 
                       value="<?= htmlspecialchars($values['dateborn']) ?>" 
                       class="<?= isset($errors['dateborn']) ? 'error' : '' ?>">
                <?php if (isset($errors['dateborn'])) echo '<div class="error-msg">'.$errors['dateborn'].'</div>'; ?>
            </div>

            <div class="form-group">
                <span <?= isset($errors['gender']) ? 'class="error-msg"' : '' ?>>Пол:</span>
                <input type="radio" name="gender" value="M" <?= $values['gender'] == 'M' ? 'checked' : '' ?>> М
                <input type="radio" name="gender" value="F" <?= $values['gender'] == 'F' ? 'checked' : '' ?>> Ж
                <?php if (isset($errors['gender'])) echo '<div class="error-msg">'.$errors['gender'].'</div>'; ?>
            </div>

            <div class="form-group">
                <label>Любимые языки:</label>
                <select name="languages[]" multiple class="<?= isset($errors['languages']) ? 'error' : '' ?>">
                    <?php
                    $langs = [
                        'Pascal' => 'Pascal', 
                        'C' => 'C', 
                        'C++' => 'C++', 
                        'JavaScript' => 'JS',
                        'PHP' => 'PHP'
                    ];
                    foreach ($langs as $v => $l) {
                        $selected = in_array($v, (array)$values['languages']) ? 'selected' : '';
                        echo "<option value='$v' $selected>$l</option>";
                    }
                    ?>
                </select>
                <?php if (isset($errors['languages'])) echo '<div class="error-msg">'.$errors['languages'].'</div>'; ?>
            </div>

            <div class="form-group">
                <label>Биография:</label>
                <textarea name="bio" class="<?= isset($errors['bio']) ? 'error' : '' ?>"><?= htmlspecialchars($values['bio']) ?></textarea>
                <?php if (isset($errors['bio'])) echo '<div class="error-msg">'.$errors['bio'].'</div>'; ?>
            </div>

            <div class="form-group">
                <input type="checkbox" name="agreement" <?= !empty($values['agreement']) ? 'checked' : '' ?>> 
                <span <?= isset($errors['agreement']) ? 'class="error-msg"' : '' ?>>С контрактом ознакомлен</span>
                <?php if (isset($errors['agreement'])) echo '<div class="error-msg">'.$errors['agreement'].'</div>'; ?>
            </div>

            <input class="knopka" type="submit" value="Сохранить">
        </form>
    </div>
</body>
</html>

<?php
setcookie('login', '', 100000, '/');
setcookie('pass', '', 100000, '/');
?>