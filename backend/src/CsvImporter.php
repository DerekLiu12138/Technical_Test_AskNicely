<?php
require_once __DIR__.'/Repositories.php';

class CsvImporter {
  /** @return array{imported:int, skipped:int, errors:string[]} */
  public function import(string $filepath): array {
    if (!is_readable($filepath)) throw new RuntimeException('CSV not readable');

    $repo = new EmployeeRepo();
    $fp = fopen($filepath, 'r');
    if (!$fp) throw new RuntimeException('Cannot open CSV');

    $headers = fgetcsv($fp);
    if (!$headers) throw new RuntimeException('Empty CSV');

    // Normalize header names
    $map = $this->buildHeaderMap($headers);

    $imported = 0; $skipped = 0; $errors = [];
    while (($line = fgetcsv($fp)) !== false) {
      if ($this->isBlankRow($line)) { $skipped++; continue; }
      try {
        $row = $this->rowFromMap($map, $line);
        $repo->upsert($row);
        $imported++;
      } catch (Throwable $e) {
        $errors[] = $e->getMessage();
        $skipped++;
      }
    }
    fclose($fp);

    return compact('imported','skipped','errors');
  }

  private function buildHeaderMap(array $headers): array {
    $norm = fn($s)=>strtolower(trim($s));
    $idx = [];
    foreach ($headers as $i => $h) {
      $k = $norm($h);
      if (in_array($k, ['company name','company'])) $idx['company'] = $i;
      elseif (in_array($k, ['employee name','name'])) $idx['name'] = $i;
      elseif (in_array($k, ['email address','email'])) $idx['email'] = $i;
      elseif ($k === 'salary') $idx['salary'] = $i;
    }
    foreach (['company','name','email','salary'] as $k) if (!isset($idx[$k])) throw new InvalidArgumentException("Missing column: $k");
    return $idx;
  }

  private function rowFromMap(array $map, array $line): array {
    $company = trim($line[$map['company']] ?? '');
    $name = trim($line[$map['name']] ?? '');
    $email = trim($line[$map['email']] ?? '');
    $salaryRaw = trim($line[$map['salary']] ?? '0');

    if ($company === '' || $name === '' || $email === '') throw new InvalidArgumentException('Blank required field');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email: '.$email);

    $salary = (int)preg_replace('/[^0-9-]/', '', $salaryRaw);
    if (!is_numeric($salary)) throw new InvalidArgumentException('Invalid salary');

    return [
      'company' => $company,
      'name' => $name,
      'email' => $email,
      'salary' => $salary,
    ];
  }

  private function isBlankRow(array $line): bool {
    foreach ($line as $v) if (trim((string)$v) !== '') return false;
    return true;
  }
}
