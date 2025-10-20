<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(204);
    exit(0);
}
header('Access-Control-Allow-Origin: *');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function db() : PDO {
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'appdb';
    $user = getenv('DB_USER') ?: 'app';
    $pass = getenv('DB_PASSWORD') ?: 'app123';
    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

try {
    if ($path === '/api/health') {
        echo json_encode(['ok' => true, 'time' => date('c')]); exit;
    }

    if ($path === '/api/employees' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = db()->query("SELECT id, company, name, email, salary FROM employees ORDER BY id DESC");
        echo json_encode($stmt->fetchAll()); exit;
    }

    if ($path === '/api/employees' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $sql = "INSERT INTO employees(company, name, email, salary) VALUES(?,?,?,?)";
        db()->prepare($sql)->execute([
            $input['company'] ?? '',
            $input['name'] ?? '',
            $input['email'] ?? '',
            (int)($input['salary'] ?? 0),
        ]);
        echo json_encode(['ok'=>true]); exit;
    }

    if (preg_match('#^/api/employees/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = (int)$m[1];
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $sql = "UPDATE employees SET email=? WHERE id=?";
        db()->prepare($sql)->execute([$input['email'] ?? '', $id]);
        echo json_encode(['ok'=>true]); exit;
    }

    http_response_code(404);
    echo json_encode(['error'=>'Not Found']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}