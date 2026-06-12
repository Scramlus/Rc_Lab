<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'api/config.php is missing or named incorrectly.',
    ]);
    exit;
}

$config = require $configPath;

foreach (['host', 'database', 'username', 'password'] as $key) {
    if (!isset($config[$key]) || $config[$key] === '') {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => "Missing database config value: {$key}",
        ]);
        exit;
    }
}

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['database']
        ),
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $statement = $pdo->query("SHOW TABLES LIKE 'registrations'");
    $hasTable = (bool)$statement->fetch();

    if (!$hasTable) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Database connected, but registrations table is missing.',
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Database connection and registrations table are ready.',
    ]);
} catch (PDOException $error) {
    $response = [
        'ok' => false,
        'error' => 'Database connection failed. Check database name, username, password, and user permissions.',
    ];

    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        $response['debug'] = [
            'code' => $error->getCode(),
            'message' => $error->getMessage(),
        ];
    }

    http_response_code(500);
    echo json_encode($response);
}
