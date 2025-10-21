<?php
/**
 * PHPUnit Bootstrap Script (Test Environment)
 * 1) Read environment variables and set defaults
 * 2) Write back using putenv for use by code under test
 * 3) Wait for MySQL (TCP) to become ready
 * 4) Initialise tables and clear data
 */

// ---------- 1) Read environment variables and set defaults ----------
$DB_HOST = getenv('DB_HOST');           if (!$DB_HOST) $DB_HOST = 'test_db';
$DB_PORT = getenv('DB_PORT');           if (!$DB_PORT) $DB_PORT = '3306';
$DB_NAME = getenv('DB_NAME');           if (!$DB_NAME) $DB_NAME = 'asknicely_test';
$DB_USER = getenv('DB_USER');           if (!$DB_USER) $DB_USER = 'asknicely_user';
$DB_PASS = getenv('DB_PASS');
if (!$DB_PASS) $DB_PASS = getenv('DB_PASSWORD');
if (!$DB_PASS) $DB_PASS = 'asknicely_password';

// ---------- 2) Write back to putenv (so that the code under test, pdo_factory(), can read it) ----------
putenv("DB_HOST={$DB_HOST}");
putenv("DB_PORT={$DB_PORT}");
putenv("DB_NAME={$DB_NAME}");
putenv("DB_USER={$DB_USER}");
putenv("DB_PASS={$DB_PASS}");
putenv("DB_PASSWORD={$DB_PASS}");
putenv('DB_DSN=');

// ---------- 3) Load the code under test ----------
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Repositories.php';
require_once __DIR__ . '/../src/CsvImporter.php';

// ---------- 4) Waiting for MySQL (forced TCP, not via UNIX socket) ----------
$ok = false; $firstErr = null;
for ($i = 0; $i < 90; $i++) {
  try {
    $hostIp = gethostbyname($DB_HOST);
    if (!$hostIp) $hostIp = $DB_HOST; 
    $dsn = "mysql:host={$hostIp};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $tmpPdo = new PDO($dsn, $DB_USER, $DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $tmpPdo->query('SELECT 1');
    $ok = true; break;
  } catch (Throwable $e) {
    if ($firstErr === null) $firstErr = $e;
    usleep(300000); // 0.3s
  }
}
if (!$ok) {
  throw new RuntimeException(
    "MySQL not ready at {$DB_HOST}:{$DB_PORT}. First error: " .
    ($firstErr ? $firstErr->getMessage() : 'unknown')
  );
}

// ---------- 5) Initialise the table (idempotent) and clear the data ----------
$pdo = pdo_factory();
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

$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$pdo->exec("TRUNCATE TABLE employees");
$pdo->exec("TRUNCATE TABLE companies");
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
