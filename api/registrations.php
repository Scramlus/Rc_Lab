<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database config is missing.']);
    exit;
}

$config = require $configPath;
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['database']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $error) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not connect to database.']);
    exit;
}

function publicRegistrations(PDO $pdo): array
{
    $statement = $pdo->query(
        'SELECT nickname, category FROM registrations ORDER BY id DESC'
    );

    return $statement->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(publicRegistrations($pdo));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON.']);
    exit;
}

$name = trim((string)($input['name'] ?? ''));
$surname = trim((string)($input['surname'] ?? ''));
$nickname = trim((string)($input['nickname'] ?? ''));
$age = (int)($input['age'] ?? 0);
$phone = trim((string)($input['phone'] ?? ''));
$category = (string)($input['category'] ?? '');

if (
    $name === '' ||
    $surname === '' ||
    $nickname === '' ||
    $phone === '' ||
    $age < 6 ||
    $age > 80 ||
    !in_array($category, ['drift', 'race'], true)
) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid registration.']);
    exit;
}

$statement = $pdo->prepare(
    'INSERT INTO registrations (name, surname, nickname, age, phone, category)
     VALUES (:name, :surname, :nickname, :age, :phone, :category)'
);

$statement->execute([
    ':name' => $name,
    ':surname' => $surname,
    ':nickname' => $nickname,
    ':age' => $age,
    ':phone' => $phone,
    ':category' => $category,
]);

http_response_code(201);
echo json_encode(publicRegistrations($pdo));
