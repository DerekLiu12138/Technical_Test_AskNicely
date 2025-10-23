<?php
declare(strict_types=1);

namespace App;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use App\EmployeeRepo;

final class CsvImporter
{
    /** @return array{imported:int, skipped:int, errors:string[]} */
    public function import(string $filepath): array
    {
        if (!is_readable($filepath)) {
            throw new RuntimeException('CSV not readable');
        }

        $repo = new EmployeeRepo();
        $fp = fopen($filepath, 'r');
        if (!$fp) {
            throw new RuntimeException('Cannot open CSV');
        }

        $headers = fgetcsv($fp);
        if (!$headers || !is_array($headers) || count($headers) === 0) {
            throw new RuntimeException('Empty CSV');
        }

        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/u', '', (string)$headers[0]);
        }

        $map = $this->buildHeaderMap($headers);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNo    = 1;
        while (($line = fgetcsv($fp)) !== false) {
            $rowNo++;
            if (!is_array($line) || $this->isBlankRow($line)) {
                $skipped++;
                continue;
            }
            try {
                $row = $this->rowFromMap($map, $line);
                $repo->upsert($row);
                $imported++;
            } catch (Throwable $e) {
                $errors[] = "row {$rowNo}: " . $e->getMessage();
                $skipped++;
            }
        }
        fclose($fp);

        return compact('imported', 'skipped', 'errors');
    }

    private function buildHeaderMap(array $headers): array
    {
        $aliases = [
            'company' => ['company', 'companyname', 'companynames', 'organisation', 'organization', 'org', 'orgname'],
            'name'    => ['name', 'employeename', 'fullname', 'employee', 'staffname'],
            'email'   => ['email', 'emailaddress', 'e-mail', 'mail', 'workemail'],
            'salary'  => ['salary', 'wage', 'pay', 'compensation', 'annualsalary', 'basepay'],
        ];

        $rev = [];
        foreach ($aliases as $canon => $list) {
            foreach ($list as $v) {
                $rev[$v] = $canon;
            }
        }

        $idx = [];
        foreach ($headers as $i => $h) {
            $norm = $this->normalizeHeader((string)$h);
            if ($norm === '') {
                continue;
            }
            $canon = $rev[$norm] ?? null;
            if ($canon !== null && !isset($idx[$canon])) {
                $idx[$canon] = $i;
                continue;
            }

            if (!isset($idx['company']) && in_array($norm, ['companyname', 'company'], true)) $idx['company'] = $i;
            if (!isset($idx['name'])    && in_array($norm, ['employeename', 'name'], true))   $idx['name']    = $i;
            if (!isset($idx['email'])   && in_array($norm, ['emailaddress', 'email'], true))  $idx['email']   = $i;
            if (!isset($idx['salary'])  && $norm === 'salary')                                 $idx['salary']  = $i;
        }

        $required = ['company', 'name', 'email', 'salary'];
        $missing  = array_values(array_diff($required, array_keys($idx)));
        if ($missing) {
            throw new InvalidArgumentException('Missing column(s): ' . implode(', ', $missing));
        }
        return $idx;
    }

    private function normalizeHeader(string $s): string
    {
        $s = preg_replace('/^\xEF\xBB\xBF/u', '', $s);         // BOM
        $s = str_replace("\xC2\xA0", ' ', $s);                 // NBSP → space
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = preg_replace('/\s+/', ' ', $s);                   // collapse spaces
        $s = preg_replace('/[^a-z]/', '', $s);                 // letters only
        return $s ?? '';
    }

    private function rowFromMap(array $map, array $line): array
    {
        $clean = function (?string $v): string {
            $v = (string)($v ?? '');
            $v = str_replace("\xC2\xA0", ' ', $v);
            return trim($v);
        };

        $company   = $clean($line[$map['company']] ?? '');
        $name      = $clean($line[$map['name']] ?? '');
        $email     = $clean($line[$map['email']] ?? '');
        $salaryRaw = $clean($line[$map['salary']] ?? '0');

        if ($company === '' || $name === '' || $email === '') {
            throw new InvalidArgumentException('Blank required field');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email: ' . $email);
        }

        $salaryStr = preg_replace('/[^\d\-]/', '', $salaryRaw);
        if ($salaryStr === '' || !preg_match('/^-?\d+$/', $salaryStr)) {
            throw new InvalidArgumentException('Invalid salary: ' . $salaryRaw);
        }
        $salary = (int)$salaryStr;

        return [
            'company' => $company,
            'name'    => $name,
            'email'   => $email,
            'salary'  => $salary,
        ];
    }

    private function isBlankRow(array $line): bool
    {
        foreach ($line as $v) {
            if (trim(str_replace("\xC2\xA0", ' ', (string)$v)) !== '') {
                return false;
            }
        }
        return true;
    }
}
