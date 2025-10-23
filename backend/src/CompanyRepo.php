<?php
namespace App;

class CompanyRepo {
  public function upsertIdByName(string $name): int {
    $pdo = pdo_factory();
    $pdo->prepare('INSERT IGNORE INTO companies(name) VALUES (?)')->execute([$name]);
    $stmt = $pdo->prepare('SELECT id FROM companies WHERE name=?');
    $stmt->execute([$name]);
    return (int)$stmt->fetchColumn();
  }
}
