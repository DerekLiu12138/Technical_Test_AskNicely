<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap.php';

use App\AdminService;
use App\CsvImporter;
use App\EmployeeRepo;
use App\CompanyRepo;


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($path === '/api/health') {
    json(['ok' => true, 'time' => time()]);
  }

  // --- Admin endpoints ---
  if ($path === '/api/admin/health' && $method === 'GET') {
    $s = new AdminService();
    json($s->health());
  }

  if ($path === '/api/admin/bootstrap' && $method === 'POST') {
    $s = new AdminService();
    json($s->bootstrap());
  }
  // --- end admin ---

  if ($path === '/api/upload' && $method === 'POST') {
    if (!isset($_FILES['file'])) json(['error' => 'file is required'], 400);
    $tmp = $_FILES['file']['tmp_name'];
    $imp = new CsvImporter();
    $result = $imp->import($tmp);
    json($result);
  }

  if ($path === '/api/employees' && $method === 'GET') {
    $repo = new EmployeeRepo();
    $data = $repo->all();
    json(is_array($data) ? $data : []);
  }

  if (preg_match('#^/api/employees/(\\d+)/email$#', $path, $m) && $method === 'PATCH') {
    $id = (int)$m[1];
    $body = input_json();
    if (!isset($body['email'])) json(['error' => 'email required'], 400);
    (new EmployeeRepo())->updateEmail($id, $body['email']);
    json(['ok' => true]);
  }

  if ($path === '/api/companies/avg-salary' && $method === 'GET') {
    $repo = new EmployeeRepo();
    $data = $repo->avgSalaryByCompany();
    json(is_array($data) ? $data : []);
  }

  http_response_code(404);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Not Found', 'path' => $path]);
} catch (Throwable $e) {
  json(['error' => $e->getMessage()], 400);
}
