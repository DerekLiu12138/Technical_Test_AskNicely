<?php
use PHPUnit\Framework\TestCase;

final class CsvImporterTest extends TestCase {
  protected function setUp(): void {
    pdo_factory()->exec("SET FOREIGN_KEY_CHECKS=0; TRUNCATE TABLE employees; TRUNCATE TABLE companies; SET FOREIGN_KEY_CHECKS=1;");
  }

  public function testImportHappyPath(): void {
    $csv = implode("\n", [
      "Company Name,Employee Name,Email Address,Salary",
      "ACME Corporation,John Doe,john@acme.com,50000",
      "ACME Corporation,Jane Doe,jane@acme.com,60000",
      "Stark Industries,Tony Stark,tony@stark.com,100000",
    ]);
    $tmp = tempnam(sys_get_temp_dir(), 'csv'); file_put_contents($tmp, $csv);

    $imp = new CsvImporter();
    $res = $imp->import($tmp);
    unlink($tmp);

    $this->assertSame(3, $res['imported']);
    $this->assertSame(0, $res['skipped']);

    $rows = (new EmployeeRepo())->all();
    $this->assertCount(3, $rows);
  }

  public function testImportSkipsInvalidEmail(): void {
    $csv = implode("\n", [
      "Company Name,Employee Name,Email Address,Salary",
      "ACME,John,john@acme.com,50000",
      "ACME,Jane,invalid_email,60000"
    ]);
    $tmp = tempnam(sys_get_temp_dir(), 'csv'); file_put_contents($tmp, $csv);

    $imp = new CsvImporter();
    $res = $imp->import($tmp);
    unlink($tmp);

    $this->assertSame(1, $res['imported']);
    $this->assertSame(1, $res['skipped']);
    $this->assertNotEmpty($res['errors']);
  }
}
