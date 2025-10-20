<?php
require_once __DIR__.'/bootstrap.php';

class CompanyRepo {
  public function upsertIdByName(string $name): int {
    $pdo = pdo_factory();
    $pdo->prepare('INSERT IGNORE INTO companies(name) VALUES (?)')->execute([$name]);
    $stmt = $pdo->prepare('SELECT id FROM companies WHERE name=?');
    $stmt->execute([$name]);
    return (int)$stmt->fetchColumn();
  }
}

class EmployeeRepo {
  public function upsert(array $row): void {
    // $row: [company, name, email, salary]
    $companyId = (new CompanyRepo())->upsertIdByName($row['company']);
    $pdo = pdo_factory();
    $sql = 'INSERT INTO employees (company_id, name, email, salary)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE email = VALUES(email), salary = VALUES(salary)';
    $pdo->prepare($sql)->execute([$companyId, $row['name'], $row['email'], $row['salary']]);
  }

  public function all(): array {
    $pdo = pdo_factory();
    $sql = 'SELECT e.id, c.name as company, e.name, e.email, e.salary
            FROM employees e JOIN companies c ON e.company_id = c.id
            ORDER BY c.name, e.name';
    return $pdo->query($sql)->fetchAll();
  }

  public function updateEmail(int $id, string $email): void {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email');
    $pdo = pdo_factory();
    $pdo->prepare('UPDATE employees SET email=? WHERE id=?')->execute([$email, $id]);
  }

  public function avgSalaryByCompany(): array {
    $pdo = pdo_factory();
    $sql = 'SELECT c.name as company, ROUND(AVG(e.salary), 2) as avg_salary
            FROM employees e JOIN companies c ON e.company_id = c.id
            GROUP BY c.id, c.name
            ORDER BY c.name';
    return $pdo->query($sql)->fetchAll();
  }
}
