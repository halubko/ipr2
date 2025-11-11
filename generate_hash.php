<?php
include_once 'config/Database.php'; 

$database = new Database();
$db = $database->getConnection();

$plainKey = 'my_secret_key_123';
$userIdToUpdate = 1;

$newHashedKey = password_hash($plainKey, PASSWORD_BCRYPT);

echo "--- ОБНОВЛЕНИЕ API-КЛЮЧА В БАЗЕ ДАННЫХ ---\n";
echo "Plain-text ключ: " . $plainKey . "\n";
echo "Сгенерированный HASH: " . $newHashedKey . "\n";
echo "Обновляемый user_id: " . $userIdToUpdate . "\n";
echo "------------------------------------------------\n";

$query = "UPDATE api_keys SET api_key = :hashed_key, is_active = TRUE WHERE user_id = :user_id";
$stmt = $db->prepare($query);

$stmt->bindParam(':hashed_key', $newHashedKey);
$stmt->bindParam(':user_id', $userIdToUpdate, PDO::PARAM_INT);

try {
    $stmt->execute();
    $rowsAffected = $stmt->rowCount();

    if ($rowsAffected > 0) {
        echo "УСПЕХ: Обновлено " . $rowsAffected . " строка(а) в таблице api_keys.\n";
        echo "Теперь используйте ключ '" . $plainKey . "' в Postman.\n";
    } else {
        echo "ПРЕДУПРЕЖДЕНИЕ: Обновление не выполнено. Проверьте, существует ли user_id = " . $userIdToUpdate . " в таблице api_keys.\n";
    }
} catch (PDOException $e) {
    echo "ОШИБКА БД: Не удалось выполнить запрос обновления: " . $e->getMessage() . "\n";
}

$db = null;
?>