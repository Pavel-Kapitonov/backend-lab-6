<?php
$config = include('db_config.php');


$name = $_POST['fio'] ?? '';        
$tel = $_POST['phone'] ?? '';      
$email = $_POST['email'] ?? '';
$dateborn = $_POST['dateborn'] ?? '';
$gender = $_POST['gender'] ?? '';
$bio = $_POST['bio'] ?? '';
$languages = $_POST['languages'] ?? [];
$agreement = isset($_POST['agreement']);

$errors = array();

if (empty($name) || !preg_match('/^[a-zA-Zа-яёА-ЯЁ\s\-]+$/u', $name)) {
    $errors['fio'] = "Можно только буквы, пробелы и дефис";
}

if (empty($tel) || !preg_match('/^\+?[0-9]{11}$/', $tel)) {
    $errors['phone'] = "Введите 11 цифр вашего номера (РФ)";
}

if (empty($email) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    $errors['email'] = "Некорректный email";
}

if (empty($dateborn) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateborn)) {
    $errors['dateborn'] = "Выберите дату в формате ГГГГ-ММ-ДД";
}

if (empty($gender) || !preg_match('/^(M|F)$/', $gender)) {
    $errors['gender'] = "Выберите пол";
}

if (empty($languages)) {
    $errors['languages'] = "Необходимо выбрать хотя бы один язык";
} elseif (!is_array($languages)) {
    $errors['languages'] = "Ошибка: данные должны быть массивом";
} else {
    foreach ($languages as $lang) {
        if (!preg_match('/^[a-zA-Z0-9\+\#\-\s]+$/u', $lang)) {
            $errors['languages'] = "Недопустимое значение в поле выбора языков";
            break;
        }
    }
}

if (empty($bio) || !preg_match('/^[a-zA-Zа-яёА-ЯЁ0-9\s\.,\-\!\?]+$/u', $bio)) {
    $errors['bio'] = "В биографии разрешены буквы, цифры, пробелы и знаки .,-!?";
}

if (empty($agreement)) {
    $errors['agreement'] = "Необходимо согласиться с соглашением";
}

if (!empty($errors)) {
    setcookie('errors', json_encode($errors), 0, '/');

    $values = [
        'fio' => $name,
        'phone' => $tel,
        'email' => $email,
        'dateborn' => $dateborn,
        'gender' => $gender,
        'bio' => $bio,
        'languages' => $languages,
    ];
    setcookie('values', json_encode($values), 0, '/');

    header('Location: index.php');
    exit;
}

require_once('db.php');

try {
    session_start();

    $db->beginTransaction();

    if (!empty($_SESSION['login'])) {
        $requestId = $_SESSION['uid'];

        $stmt = $db->prepare("UPDATE Request SET name = :name, tel = :tel, email = :email, 
                              dateborn = :dateborn, gender = :gender, bio = :bio 
                              WHERE request_id = :rid");
        
        $stmt->execute([
            ':name' => $name,
            ':tel' => $tel,
            ':email' => $email,
            ':dateborn' => $dateborn,
            ':gender' => $gender,
            ':bio' => $bio,
            ':rid' => $requestId
        ]);

        $db->prepare("DELETE FROM Connection WHERE request_id = ?")->execute([$requestId]);
    } else {
        $stmt = $db->prepare("INSERT INTO Request (name, tel, email, dateborn, gender, bio, agreed) 
                              VALUES (:name, :tel, :email, :dateborn, :gender, :bio, :agreed)");
        $stmt->execute([
            ':name' => $name,
            ':tel' => $tel,
            ':email' => $email,
            ':dateborn' => $dateborn,
            ':gender' => $gender,
            ':bio' => $bio,
            ':agreed' => $agreement ? 1 : 0
        ]);

        $requestId = $db->lastInsertId();

        $login = 'user' . $requestId; 
        $pass = bin2hex(random_bytes(4)); 
        $hash = password_hash($pass, PASSWORD_DEFAULT); 

        $stmt = $db->prepare("INSERT INTO Users (request_id, login, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$requestId, $login, $hash]);

        setcookie('login', $login, time() + 3600);
        setcookie('pass', $pass, time() + 3600);
    }

    $getLangId = $db->prepare("SELECT language_id FROM Language WHERE language_name = ?");
    $insertConn = $db->prepare("INSERT INTO Connection (request_id, language_id) VALUES (?, ?)");


    foreach ($languages as $langName) {
        $getLangId->execute([$langName]);
        $row = $getLangId->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $insertConn->execute([$requestId, $row['language_id']]);
        }
    }

    $db->commit();

    $expire = time() + 365 * 24 * 60 * 60;
    setcookie('fio_value', $name, $expire, '/');
    setcookie('phone_value', $tel, $expire, '/');
    setcookie('email_value', $email, $expire, '/');
    setcookie('dateborn_value', $dateborn, $expire, '/');
    setcookie('gender_value', $gender, $expire, '/');
    setcookie('bio_value', $bio, $expire, '/');
    
    setcookie('languages_value', implode(',', $languages), $expire, '/');
    setcookie('agreement_value', 'on', $expire, '/');

    setcookie('success', '1', 0, '/');

    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    print('Error : ' . $e->getMessage());
    exit;
} 
