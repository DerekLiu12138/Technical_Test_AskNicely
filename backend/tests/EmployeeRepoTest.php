<?php

namespace Tests;
use PHPUnit\Framework\TestCase;
use App\CompanyRepo;
use InvalidArgumentException;
use App\EmployeeRepo;


final class EmployeeRepoTest extends TestCase {
  protected function setUp(): void {
    pdo_factory()->exec("SET FOREIGN_KEY_CHECKS=0; TRUNCATE TABLE employees; TRUNCATE TABLE companies; SET FOREIGN_KEY_CHECKS=1;");
  }

  public function testUpsertAllAndAvg(): void {
    $r = new EmployeeRepo();
    $r->upsert(['company'=>'ACME','name'=>'John','email'=>'john@acme.com','salary'=>50000]);
    $r->upsert(['company'=>'ACME','name'=>'Jane','email'=>'jane@acme.com','salary'=>60000]);
    $r->upsert(['company'=>'Stark','name'=>'Tony','email'=>'tony@stark.com','salary'=>100000]);

    // all()
    $rows = $r->all();
    $this->assertCount(3, $rows);
    $this->assertSame('ACME', $rows[0]['company']);

    // Upsert update (same as company+name)
    $r->upsert(['company'=>'ACME','name'=>'John','email'=>'johnny@acme.com','salary'=>55000]);
    $rows = $r->all();
    $this->assertCount(3, $rows);
    $john = array_values(array_filter($rows, fn($x)=>$x['name']==='John'))[0];
    $this->assertSame('johnny@acme.com', $john['email']);
    $this->assertSame(55000, (int)$john['salary']);

    // average salary by company
    $avg = $r->avgSalaryByCompany();
    $map = [];
    foreach ($avg as $a) $map[$a['company']] = (int)round($a['avg_salary']);
    $this->assertSame(57500, $map['ACME']); 
    $this->assertSame(100000, $map['Stark']);
  }

  public function testUpdateEmailValidation(): void {
    $r = new EmployeeRepo();
    $r->upsert(['company'=>'ACME','name'=>'John','email'=>'john@acme.com','salary'=>50000]);
    $rows = $r->all();
    $id = $rows[0]['id'];

    $this->expectException(InvalidArgumentException::class);
    $r->updateEmail($id, 'invalid-email');
  }
}
