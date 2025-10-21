<?php
require_once __DIR__.'/bootstrap.php';

/**
 * Admin-only DB bootstrapper.
 * Uses root credentials (DB_ADMIN_USER/DB_ADMIN_PASS) to create database,
 * user, grants, and schema. Idempotent.
 */
final class AdminService {
  private function adminPdo(): PDO {
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $adminUser = getenv('DB_ADMIN_USER') ?: 'root';
    $adminPass = getenv('DB_ADMIN_PASSWORD') ?: '';
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $adminUser, $adminPass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
  }

  private function requireToken(): void {
    $server = $_SERVER;
    $hdr = $server['HTTP_X_ADMIN_TOKEN'] ?? '';
    $expected = getenv('ADMIN_TOKEN') ?: '';
    if ($expected === '' || !hash_equals($expected, $hdr)) {
      http_response_code(401);
      header('Content-Type: application/json');
      echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }

  public function health(): array {
    $bizInfo = ['ok' => false, 'error' => null];
    try {
      $biz = pdo_factory();
      $bizInfo = ['ok' => true, 'version' => $biz->query('SELECT VERSION() AS v')->fetch()['v'] ?? 'unknown'];
    } catch (Throwable $e) {
      $bizInfo['error'] = $e->getMessage();
    }
  
    $adm = $this->adminPdo();
    $adminVer = $adm->query('SELECT VERSION() AS v')->fetch()['v'] ?? 'unknown';
    return ['ok' => true, 'admin_version' => $adminVer, 'biz' => $bizInfo, 'time' => time()];
  }  

  public function bootstrap(): array {
    $this->requireToken();

    $dbName = getenv('DB_NAME') ?: 'engineer';
    $user   = getenv('DB_USER') ?: 'engineer';
    $pass   = getenv('DB_PASS') ?: 'engineerpwd';

    $pdo = $this->adminPdo();
    // 1) create database & user (if not exists)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("CREATE USER IF NOT EXISTS '$user'@'%' IDENTIFIED BY '$pass'");
    $pdo->exec("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$user'@'%'");
    $pdo->exec("FLUSH PRIVILEGES");

    // 2) create schema (idempotent)
    $pdo->exec("USE `$dbName`");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        salary INT NOT NULL,
        CONSTRAINT fk_company FOREIGN KEY (company_id)
          REFERENCES companies(id) ON DELETE CASCADE,
        CONSTRAINT uq_company_employee UNIQUE (company_id, name)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    return ['ok' => true, 'message' => 'bootstrap completed'];
  }
}
