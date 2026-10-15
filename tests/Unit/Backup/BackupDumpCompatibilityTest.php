<?php

namespace Tests\Unit\Backup;

use App\Services\Backup\BackupDumpCompatibilityService;
use PHPUnit\Framework\TestCase;

final class BackupDumpCompatibilityTest extends TestCase
{
    public function test_legacy_table_statements_are_adapted_without_changing_record_values(): void
    {
        $sql = <<<'SQL'
-- MySQL dump
DROP TABLE IF EXISTS `sys_database_backup_models`;
CREATE TABLE `sys_database_backup_models` (`id` bigint, `creator_name` varchar(255));
LOCK TABLES `sys_database_backup_models` WRITE;
/*!40000 ALTER TABLE `sys_database_backup_models` DISABLE KEYS */;
INSERT INTO `sys_database_backup_models` VALUES (1,'sys_database_backup_models');
/*!40000 ALTER TABLE `sys_database_backup_models` ENABLE KEYS */;
UNLOCK TABLES;
INSERT INTO `aud_audit_log_models` VALUES (1,'sys_database_backup_models');
SQL;

        $adapted = $this->adapt($sql);

        $this->assertStringContainsString('DROP TABLE IF EXISTS `bkp_backup_models`;', $adapted);
        $this->assertStringContainsString('CREATE TABLE `bkp_backup_models`', $adapted);
        $this->assertStringContainsString('LOCK TABLES `bkp_backup_models` WRITE;', $adapted);
        $this->assertStringContainsString('ALTER TABLE `bkp_backup_models` DISABLE KEYS', $adapted);
        $this->assertStringContainsString("INSERT INTO `bkp_backup_models` VALUES (1,'sys_database_backup_models');", $adapted);
        $this->assertStringContainsString("INSERT INTO `aud_audit_log_models` VALUES (1,'sys_database_backup_models');", $adapted);
    }

    public function test_current_schema_dump_is_not_modified(): void
    {
        $sql = "-- MySQL dump\nCREATE TABLE `bkp_backup_models` (`id` bigint);\nINSERT INTO `bkp_backup_models` VALUES (1);\n";

        $this->assertSame($sql, $this->adapt($sql));
    }

    private function adapt(string $sql): string
    {
        $input = tmpfile();
        $this->assertIsResource($input);
        fwrite($input, $sql);
        rewind($input);

        try {
            return implode('', iterator_to_array((new BackupDumpCompatibilityService)->srv_restoreInput($input)));
        } finally {
            fclose($input);
        }
    }
}
