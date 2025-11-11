<?php
// Этот файл предназначен для выполнения на сервере (например, через CLI или браузер),
// чтобы сгенерировать API-ключ и сохранить его хеш в базу данных.
// КЛИЕНТ ДОЛЖЕН ПОЛУЧИТЬ ТОЛЬКО $plainKey!

include_once 'config/Database.php';
include_once 'config/Auth.php'; 

// --- Настройки ---
$target_user_id = 1; // Укажите ID пользователя, для которого генерируется ключ
// --- Конец настроек ---

function generateRandomKey($length = 32) {
    // Генерация криптографически стойкой случайной строки
    return bin2hex(random_bytes($length / 2));
}

$database = new Database();
$db = $database->getConnection();

// 1. Генерируем открытый (PLAIN) API-ключ
$plainKey = generateRandomKey(64); 

// 2. Хешируем ключ для безопасного хранения в БД
$hashedKey = Auth::generateHashedKey($plainKey);

// 3. Сохраняем хеш в базу данных
$query = "INSERT INTO api_keys 
          SET user_id = :user_id, key_hash = :key_hash, is_active = 1";

$stmt = $db->prepare($query);

$stmt->bindParam(':user_id', $target_user_id);
$stmt->bindParam(':key_hash', $hashedKey);

if ($stmt->execute()) {
    echo "✅ Ключ успешно сгенерирован и хеш сохранен.\n\n";
    echo "--- ВАЖНО ---\n";
    echo "Открытый API-ключ для клиента (используйте его в заголовке X-API-Key):\n";
    echo "**" . $plainKey . "**\n\n";
    echo "Хеш, сохраненный в БД (столбец key_hash):\n";
    echo $hashedKey . "\n";
    echo "--------------------\n";
    echo "НЕ СОХРАНЯЙТЕ ОТКРЫТЫЙ КЛЮЧ ИСХОДНОГО КОДА! Передайте его клиенту ОДИН РАЗ.\n";
} else {
    echo "❌ Ошибка при сохранении ключа в базу данных.\n";
    print_r($stmt->errorInfo());
}