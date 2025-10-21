<?php
// Set CORS only in HTTP mode
if (PHP_SAPI !== 'cli' && isset($_SERVER['REQUEST_METHOD'])) {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}

function json($data, int $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function input_json() {
  $raw = file_get_contents('php://input');
  return $raw ? json_decode($raw, true) : [];
}

function pdo_factory(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  //On each initial invocation, read the environment variables.
  $host = getenv('DB_HOST') ?: '127.0.0.1';
  $port = getenv('DB_PORT') ?: '3306';
  $name = getenv('DB_NAME') ?: 'asknicely_test';
  $user = getenv('DB_USER') ?: 'asknicely_user';
  $pass = getenv('DB_PASS') ?: (getenv('DB_PASSWORD') ?: 'asknicely_password');
  $dsnEnv = getenv('DB_DSN');
  $dsn = $dsnEnv ?: "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

  if ($host === '' || $dsn === '') {
    throw new RuntimeException("Invalid DB env: host='{$host}', port='{$port}', name='{$name}', user='{$user}'");
  }

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  return $pdo;
}
