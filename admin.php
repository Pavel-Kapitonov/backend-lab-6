<?php
require_once('db.php');

$auth_success = false;
if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
    try {
        $stmt = $db->prepare("SELECT password_hash FROM Admins WHERE login = ?");
        $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
            $auth_success = true;
        }
    } catch (PDOException $e) {
        die("Ошибка БД: " . $e->getMessage());
    }
}

if (!$auth_success) {
  header('HTTP/1.1 401 Unauthorized');
  header('WWW-Authenticate: Basic realm="Admin Panel"');
  print('<h1>401 Требуется авторизация</h1>');
  exit();
}

if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM Connection WHERE request_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM Users WHERE request_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM Request WHERE request_id = ?")->execute([$del_id]);
        $db->commit();
        header('Location: admin.php'); 
    } catch (PDOException $e) {
        $db->rollBack();
        die("Ошибка при удалении: " . $e->getMessage());
    }
}

$stats = $db->query("
    SELECT l.language_name, COUNT(c.request_id) as count 
    FROM Language l 
    LEFT JOIN Connection c ON l.language_id = c.language_id 
    GROUP BY l.language_name
")->fetchAll(PDO::FETCH_ASSOC);

$users = $db->query("SELECT * FROM Request")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .stats { background: #eef; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Панель администратора</h1>
    <p>Вы успешно авторизовались.</p>

    <div class="stats">
        <h3>Статистика по языкам:</h3>
        <ul>
            <?php foreach ($stats as $s): ?>
                <li><strong><?= htmlspecialchars($s['language_name']) ?>:</strong> <?= $s['count'] ?> чел.</li>
            <?php endforeach; ?>
        </ul>
    </div>

    <h3>Список пользователей:</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Телефон</th>
            <th>Email</th>
            <th>Дата рожд.</th>
            <th>Пол</th>
            <th>Био</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['request_id'] ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['tel']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['dateborn'] ?></td>
            <td><?= $u['gender'] ?></td>
            <td><?= htmlspecialchars($u['bio']) ?></td>
            <td>
                <a href="edit.php?id=<?= $u['request_id'] ?>">Редактировать</a> | 
                <a href="admin.php?delete_id=<?= $u['request_id'] ?>" onclick="return confirm('Удалить?')">Удалить</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>