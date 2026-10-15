<?php

namespace Tests\Unit\Registry;

use App\Services\Registry\XlsxRowReaderService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

final class XlsxRowReaderTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_inline_cells_preserve_positions_numbers_accents_and_original_row_numbers(): void
    {
        $path = $this->workbook($this->sheet(
            '<row r="1"><c r="A1" t="inlineStr"><is><t>Type de titre</t></is></c><c r="C1" t="inlineStr"><is><t>Numéro du formulaire</t></is></c></row>'.
            '<row r="7"><c r="A7" t="inlineStr"><is><t>Brevet</t></is></c><c r="B7"><v>2020</v></c><c r="C7" t="inlineStr"><is><t>B-000123</t></is></c></row>',
        ));
        $this->assertSame([1 => ['Type de titre', null, 'Numéro du formulaire'], 7 => ['Brevet', 2020, 'B-000123']], iterator_to_array(app(XlsxRowReaderService::class)->srv_rows($path)));
    }

    public function test_shared_strings_support_rich_text_and_ignore_phonetic_annotations(): void
    {
        $path = $this->workbook($this->sheet('<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c></row>'), [
            'xl/sharedStrings.xml' => '<sst xmlns="'.$this->namespace().'"><si><r><t>Mar</t></r><r><t>que</t></r><rPh sb="0" eb="1"><t>IGNORED</t></rPh></si><si><t xml:space="preserve"> 000123 </t></si></sst>',
        ]);
        $this->assertSame([1 => ['Marque', ' 000123 ']], iterator_to_array(app(XlsxRowReaderService::class)->srv_rows($path)));
    }

    public function test_large_dimensions_and_empty_far_cells_do_not_expand_a_dense_grid(): void
    {
        $path = $this->workbook($this->sheet('<row r="1"><c r="A1" t="inlineStr"><is><t>Real value</t></is></c></row><row r="1048576"><c r="XFD1048576" s="1"/></row>', 'A1:XFD1048576'));
        $this->assertSame([1 => ['Real value'], 1048576 => []], iterator_to_array(app(XlsxRowReaderService::class)->srv_rows($path)));
    }

    public function test_only_the_active_worksheet_is_read_and_absolute_relationships_work(): void
    {
        $path = $this->workbook($this->sheet(''), [
            'xl/workbook.xml' => '<workbook xmlns="'.$this->namespace().'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView activeTab="1"/></bookViews><sheets><sheet name="First" sheetId="1" r:id="rId1"/><sheet name="Second" sheetId="2" r:id="rId2"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="/xl/worksheets/sheet2.xml"/></Relationships>',
            'xl/worksheets/sheet2.xml' => $this->sheet('<row r="1"><c r="A1" t="inlineStr"><is><t>Active</t></is></c></row>'),
        ]);
        $this->assertSame([1 => ['Active']], iterator_to_array(app(XlsxRowReaderService::class)->srv_rows($path)));
    }

    public function test_formulas_use_cached_values_without_evaluating_the_expression(): void
    {
        $path = $this->workbook($this->sheet('<row r="1"><c r="A1"><f>1/0</f><v>2020</v></c><c r="B1" t="str"><f>HYPERLINK("https://attacker.invalid", "unsafe")</f><v>Cached text</v></c></row>'));
        $this->assertSame([1 => [2020, 'Cached text']], iterator_to_array(app(XlsxRowReaderService::class)->srv_rows($path)));
    }

    public function test_oversized_archive_parts_are_rejected_before_xml_parsing(): void
    {
        config()->set('risfm.registry_import.xlsx.max_part_bytes', 4096);
        $this->assertRejected($this->workbook($this->sheet(''), ['unused.bin' => str_repeat('A', 4097)]), 'décompressé');
    }

    public function test_total_uncompressed_archive_size_is_bounded(): void
    {
        config()->set('risfm.registry_import.xlsx.max_uncompressed_bytes', 2048);
        $this->assertRejected($this->workbook($this->sheet(''), ['unused.bin' => str_repeat('A', 2048)]), 'décompressé');
    }

    public function test_excessive_archive_entries_are_rejected(): void
    {
        config()->set('risfm.registry_import.xlsx.max_entries', 2);
        $this->assertRejected($this->workbook($this->sheet('')), 'trop de composants');
    }

    public function test_zip_directory_limits_are_checked_before_opening_the_archive(): void
    {
        config()->set('risfm.registry_import.xlsx.max_directory_bytes', 32);
        $this->assertRejected($this->workbook($this->sheet('')), 'index ZIP trop volumineux');
    }

    public function test_forged_huge_zip_entry_count_is_rejected_before_ziparchive_open(): void
    {
        $path = $this->workbook($this->sheet(''));
        $bytes = file_get_contents($path);
        $position = strrpos($bytes, "PK\x05\x06");
        file_put_contents($path, substr_replace($bytes, pack('vv', 60000, 60000), $position + 8, 4));
        $this->assertRejected($path, 'trop de composants');
    }

    public function test_valid_zip64_directory_is_supported_without_loading_an_unbounded_index(): void
    {
        $path = $this->workbook($this->sheet('<row r="1"><c r="A1" t="inlineStr"><is><t>ZIP64</t></is></c></row>'));
        $bytes = file_get_contents($path);
        $position = strrpos($bytes, "PK\x05\x06");
        $directory = unpack('vdisk/vdirectory_disk/ventries_disk/ventries/Vsize/Voffset/vcomment', substr($bytes, $position + 4, 18));
        $record = "PK\x06\x06".pack('PvvVVPPPP', 44, 45, 45, 0, 0, $directory['entries'], $directory['entries'], $directory['size'], $directory['offset']);
        $locator = "PK\x06\x07".pack('VPV', 0, $position, 1);
        $end = "PK\x05\x06".pack('vvvvVVv', 0, 0, 65535, 65535, 4294967295, 4294967295, 0);
        file_put_contents($path, substr($bytes, 0, $position).$record.$locator.$end);

        $this->assertSame([1 => ['ZIP64']], iterator_to_array(app(XlsxRowReaderService::class)->srv_rows($path)));
    }

    public function test_overlarge_cells_are_rejected_in_inline_and_shared_strings(): void
    {
        config()->set('risfm.registry_import.xlsx.max_cell_bytes', 16);
        $this->assertRejected($this->workbook($this->sheet('<row r="1"><c r="A1" t="inlineStr"><is><t>'.str_repeat('A', 17).'</t></is></c></row>')), 'cellule trop volumineuse');
        $this->assertRejected($this->workbook($this->sheet(''), ['xl/sharedStrings.xml' => '<sst xmlns="'.$this->namespace().'"><si><t>'.str_repeat('A', 17).'</t></si></sst>']), 'cellule trop volumineuse');
    }

    public function test_nonempty_cells_beyond_the_column_limit_are_rejected(): void
    {
        config()->set('risfm.registry_import.xlsx.max_columns', 4);
        $this->assertRejected($this->workbook($this->sheet('<row r="1"><c r="E1"><v>1</v></c></row>')), 'limite de colonnes');
    }

    public function test_excessive_shared_string_count_is_rejected(): void
    {
        config()->set('risfm.registry_import.xlsx.max_shared_strings', 1);
        $this->assertRejected($this->workbook($this->sheet(''), ['xl/sharedStrings.xml' => '<sst xmlns="'.$this->namespace().'"><si><t>First</t></si><si><t>Second</t></si></sst>']), 'trop de textes partagés');
    }

    public function test_dtds_and_external_entity_declarations_are_rejected(): void
    {
        $this->assertRejected($this->workbook('<!DOCTYPE worksheet [<!ENTITY probe SYSTEM "file:///etc/passwd">]>'.$this->sheet('<row r="1"><c r="A1" t="inlineStr"><is><t>&probe;</t></is></c></row>')), 'structure XML non autorisée');
    }

    public function test_external_worksheet_relationships_are_rejected(): void
    {
        $this->assertRejected($this->workbook($this->sheet(''), ['xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="https://attacker.invalid/sheet.xml" TargetMode="External"/></Relationships>']), 'référence externe');
    }

    public function test_invalid_shared_string_references_and_malformed_xml_are_rejected(): void
    {
        $this->assertRejected($this->workbook($this->sheet('<row r="1"><c r="A1" t="s"><v>999999999</v></c></row>')), 'ne peut pas être lu');
        $this->assertRejected($this->workbook('<worksheet><sheetData><row>'), 'ne peut pas être lu');
    }

    public function test_huge_xml_start_tags_are_rejected_before_libxml_allocates_attribute_objects(): void
    {
        $attributes = '';
        for ($index = 0; $index < 10000; $index++) {
            $attributes .= ' a'.$index.'=">"';
        }
        $this->assertRejected($this->workbook('<worksheet'.$attributes.'><sheetData/></worksheet>'), 'structure XML trop volumineuse');
    }

    public function test_cdata_comments_and_greater_than_signs_inside_attributes_are_supported(): void
    {
        $path = $this->workbook($this->sheet('<!-- This is a comment with an apostrophe: it\'s fine. --><row r="1"><c r="A1" t="inlineStr" custom=">"><is><t><![CDATA[Value > 0]]></t></is></c></row>'));
        $this->assertSame([1 => ['Value > 0']], iterator_to_array(app(XlsxRowReaderService::class)->srv_rows($path)));
    }

    public function test_one_hundred_thousand_rows_and_unique_shared_strings_read_under_64_mb(): void
    {
        $sheetPath = $this->temporaryPath();
        $stringsPath = $this->temporaryPath();
        $sheet = fopen($sheetPath, 'wb');
        $strings = fopen($stringsPath, 'wb');
        $this->assertIsResource($sheet);
        $this->assertIsResource($strings);
        fwrite($sheet, '<worksheet xmlns="'.$this->namespace().'"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c></row>');
        fwrite($strings, '<sst xmlns="'.$this->namespace().'">');
        foreach (['title_type', 'form_year', 'form_number', 'priority', 'BRV', 'normal'] as $value) {
            fwrite($strings, '<si><t>'.$value.'</t></si>');
        }
        for ($number = 1; $number <= 100000; $number++) {
            $row = $number + 1;
            $reference = $number + 5;
            fwrite($strings, '<si><t>B-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT).'</t></si>');
            fwrite($sheet, '<row r="'.$row.'"><c r="A'.$row.'" t="s"><v>4</v></c><c r="B'.$row.'"><v>2020</v></c><c r="C'.$row.'" t="s"><v>'.$reference.'</v></c><c r="D'.$row.'" t="s"><v>5</v></c></row>');
        }
        fwrite($strings, '</sst>');
        fwrite($sheet, '</sheetData></worksheet>');
        fclose($strings);
        fclose($sheet);
        $path = $this->workbook($this->sheet(''), ['xl/sharedStrings.xml' => '<sst/>']);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $zip->addFile($stringsPath, 'xl/sharedStrings.xml');
        $zip->close();

        $script = <<<'PHP'
require $argv[2].'/vendor/autoload.php';
$app = require $argv[2].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$baseline = memory_get_usage(true);
memory_reset_peak_usage();
$count = 0;
$last = null;
foreach ($app->make(App\Services\Registry\XlsxRowReaderService::class)->srv_rows($argv[1]) as $row) {
    $count++;
    $last = $row;
}
echo json_encode(['rows' => $count, 'last' => $last, 'memory_delta' => memory_get_peak_usage(true) - $baseline]);
PHP;
        $process = new Process([PHP_BINARY, '-d', 'memory_limit=64M', '-r', $script, $path, base_path()], base_path(), [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'CACHE_STORE' => 'array',
        ]);
        $process->setTimeout(90)->mustRun();
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(100001, $result['rows']);
        $this->assertSame(['BRV', 2020, 'B-100000', 'normal'], $result['last']);
        $this->assertLessThanOrEqual(16 * 1024 * 1024, $result['memory_delta']);
    }

    /** @param array<string, string> $extraParts */
    private function workbook(string $sheet, array $extraParts = []): string
    {
        $path = $this->temporaryPath();
        $parts = [
            'xl/workbook.xml' => '<workbook xmlns="'.$this->namespace().'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'.(isset($extraParts['xl/sharedStrings.xml']) ? '<Relationship Id="rIdStrings" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' : '').'</Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet,
        ];
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach (array_replace($parts, $extraParts) as $name => $value) {
            $this->assertTrue($zip->addFromString($name, $value));
        }
        $this->assertTrue($zip->close());

        return $path;
    }

    private function sheet(string $rows, string $dimension = 'A1:D2'): string
    {
        return '<worksheet xmlns="'.$this->namespace().'"><dimension ref="'.$dimension.'"/><sheetData>'.$rows.'</sheetData></worksheet>';
    }

    private function namespace(): string
    {
        return 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    }

    private function temporaryPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'risfm-xlsx-test-');
        $this->assertNotFalse($path);
        $this->paths[] = $path;

        return $path;
    }

    private function assertRejected(string $path, string $message): void
    {
        try {
            foreach (app(XlsxRowReaderService::class)->srv_rows($path) as $row) {
            }
            $this->fail('Le fichier XLSX devait être rejeté.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($message, $exception->errors()['import_file'][0]);
        }
    }
}
