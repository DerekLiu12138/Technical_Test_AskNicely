<?php

namespace Tests;
use PHPUnit\Framework\TestCase;
use App\CompanyRepo;

final class CompanyRepoTest extends TestCase {
  protected function setUp(): void {
    pdo_factory()->exec("SET FOREIGN_KEY_CHECKS=0; TRUNCATE TABLE employees; TRUNCATE TABLE companies; SET FOREIGN_KEY_CHECKS=1;");
  }

  public function testUpsertIdByName(): void {
    $repo = new CompanyRepo();

    $id1 = $repo->upsertIdByName('ACME');
    $this->assertIsInt($id1);
    $this->assertGreaterThan(0, $id1);

    $id2 = $repo->upsertIdByName('ACME');
    $this->assertSame($id1, $id2, 'Same name returns same id');

    $id3 = $repo->upsertIdByName('Stark');
    $this->assertNotSame($id1, $id3);
  }
}
