<?php
require_once('db.php');

$real_hash = password_hash('123', PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("UPDATE Admins SET password_hash = ? WHERE login = 'admin'");
    $stmt->execute([$real_hash]);
    
    echo "<h1>Успех!</h1>";
    echo "<p>Реальный хеш создан и записан в БД: <b>" . $real_hash . "</b></p>";
    echo "<a href='admin.php'>Теперь перейди в admin.php и войди (admin / 123)</a>";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>