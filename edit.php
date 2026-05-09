<?php
require_once('db_conn.php');

if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID не указан");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE Request SET name = ?, tel = ?, email = ?, dateborn = ?, gender = ?, bio = ? WHERE request_id = ?");
        $stmt->execute([
            $_POST['fio'], $_POST['phone'], $_POST['email'], 
            $_POST['dateborn'], $_POST['gender'], $_POST['bio'], $id
        ]);

        $db->prepare("DELETE FROM Connection WHERE request_id = ?")->execute([$id]);
        $insertConn = $db->prepare("INSERT INTO Connection (request_id, language_id) VALUES (?, ?)");
        
        if (!empty($_POST['languages'])) {
            foreach ($_POST['languages'] as $langId) {
                $insertConn->execute([$id, $langId]);
            }
        }

        $db->commit();
        header('Location: admin.php?success=edited');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        die("Ошибка при сохранении: " . $e->getMessage());
    }
}

$stmt = $db->prepare("SELECT * FROM Request WHERE request_id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Загружаем ID выбранных языков
$stmt = $db->prepare("SELECT language_id FROM Connection WHERE request_id = ?");
$stmt->execute([$id]);
$user_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);

$all_langs = $db->query("SELECT * FROM Language")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование пользователя #<?= $id ?></title>
    <style>
        .form-edit { width: 400px; margin: 20px auto; font-family: sans-serif; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="tel"], textarea, select { width: 100%; padding: 8px; }
    </style>
</head>
<body>
    <div class="form-edit">
        <h2>Редактирование профиля #<?= $id ?></h2>
        <form method="POST">
            <div class="form-group">
                <label>ФИО:</label>
                <input name="fio" type="text" value="<?= htmlspecialchars($user['name']) ?>">
            </div>
            <div class="form-group">
                <label>Телефон:</label>
                <input name="phone" type="tel" value="<?= htmlspecialchars($user['tel']) ?>">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>">
            </div>
            <div class="form-group">
                <label>Дата рождения:</label>
                <input name="dateborn" type="date" value="<?= $user['dateborn'] ?>">
            </div>
            <div class="form-group">
                <label>Пол:</label>
                <input type="radio" name="gender" value="M" <?= $user['gender'] == 'M' ? 'checked' : '' ?>> М
                <input type="radio" name="gender" value="F" <?= $user['gender'] == 'F' ? 'checked' : '' ?>> Ж
            </div>
            <div class="form-group">
                <label>Языки (выберите заново):</label>
                <select name="languages[]" multiple>
                    <?php foreach ($all_langs as $lang): ?>
                        <option value="<?= $lang['language_id'] ?>" <?= in_array($lang['language_id'], $user_langs) ? 'selected' : '' ?>>
                            <?= $lang['language_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Биография:</label>
                <textarea name="bio"><?= htmlspecialchars($user['bio']) ?></textarea>
            </div>
            <button type="submit">Сохранить изменения</button>
            <a href="admin.php">Отмена</a>
        </form>
    </div>
</body>
</html>