<?php

namespace App\Services\Registry;

use Generator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use RuntimeException;
use XMLReader;
use ZipArchive;

final class XlsxRowReaderService
{
    /** @return Generator<int, array<int, string|int|float|bool|null>> */
    public function srv_rows(string $path): Generator
    {
        $this->validateZipDirectory($path);
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY | ZipArchive::CHECKCONS) !== true) {
            throw $this->invalid();
        }

        $strings = null;
        $index = null;
        try {
            $this->validateArchive($zip);
            [$sheetPath, $sharedStringsPath] = $this->activeSheetPaths($zip);
            $strings = $this->temporaryFile();
            $index = $this->temporaryFile();
            $stringCount = $sharedStringsPath === null ? 0 : $this->indexSharedStrings($zip, $sharedStringsPath, $strings, $index);
            $row = null;
            $rowNumber = 0;
            $lastColumn = -1;
            $isWorksheet = false;

            foreach ($this->xmlNodes($zip, $sheetPath) as $xml) {
                if ($xml->nodeType === XMLReader::ELEMENT && $xml->depth === 0) {
                    $isWorksheet = $xml->localName === 'worksheet';
                    if (! $isWorksheet) {
                        throw $this->invalid();
                    }
                }
                if ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'row') {
                    $number = $xml->getAttribute('r');
                    $nextRow = $number === null ? $rowNumber + 1 : $this->positiveInteger($number, 1048576);
                    if ($row !== null || $nextRow <= $rowNumber) {
                        throw $this->invalid();
                    }
                    $rowNumber = $nextRow;
                    $lastColumn = -1;
                    $row = [];
                    if ($xml->isEmptyElement) {
                        yield $rowNumber => [];
                        $row = null;
                    }
                } elseif ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'c' && $row !== null) {
                    $coordinate = $xml->getAttribute('r');
                    $column = $coordinate === null ? $lastColumn + 1 : $this->columnIndex($coordinate, $rowNumber);
                    if ($column <= $lastColumn) {
                        throw $this->invalid();
                    }
                    $lastColumn = $column;
                    $type = $xml->getAttribute('t') ?? 'n';
                    $value = $this->elementText($xml, $type === 'inlineStr' ? 't' : 'v');
                    if ($value === '') {
                        continue;
                    }
                    if ($column >= $this->limit('max_columns', 64)) {
                        throw $this->invalid('Le fichier XLSX contient des données au-delà de la limite de colonnes autorisée.');
                    }
                    $row[$column] = match ($type) {
                        's' => $this->sharedString($value, $strings, $index, $stringCount),
                        'inlineStr', 'str', 'd' => StringHelper::controlCharacterOOXML2PHP($value),
                        'n' => is_numeric($value) ? $value + 0 : throw $this->invalid(),
                        'b' => match ($value) {
                            '1' => true, '0' => false, default => throw $this->invalid()
                        },
                        default => throw $this->invalid('Le fichier XLSX contient une cellule invalide ou une erreur Excel.'),
                    };
                } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->localName === 'row' && $row !== null) {
                    $values = [];
                    $width = $row === [] ? 0 : max(array_keys($row)) + 1;
                    for ($column = 0; $column < $width; $column++) {
                        $values[] = $row[$column] ?? null;
                    }
                    yield $rowNumber => $values;
                    $row = null;
                }
            }
            if (! $isWorksheet || $row !== null) {
                throw $this->invalid();
            }
        } finally {
            if (is_resource($strings)) {
                fclose($strings);
            }
            if (is_resource($index)) {
                fclose($index);
            }
            $zip->close();
        }
    }

    private function validateZipDirectory(string $path): void
    {
        $file = fopen($path, 'rb');
        if ($file === false) {
            throw $this->invalid();
        }
        try {
            $size = fstat($file)['size'];
            $tailBytes = min($size, 65557);
            if ($size < 22 || fseek($file, -$tailBytes, SEEK_END) !== 0) {
                throw $this->invalid();
            }
            $tail = fread($file, $tailBytes);
            if ($tail === false || ($position = strrpos($tail, "PK\x05\x06")) === false || strlen($tail) - $position < 22) {
                throw $this->invalid();
            }
            $directory = unpack('vdisk/vdirectory_disk/ventries_disk/ventries/Vdirectory_bytes/Vdirectory_offset/vcomment_bytes', substr($tail, $position + 4, 18));
            if ($position + 22 + $directory['comment_bytes'] !== strlen($tail)) {
                throw $this->invalid();
            }
            if ($directory['entries'] === 65535 || $directory['directory_bytes'] === 4294967295 || $directory['directory_offset'] === 4294967295) {
                $locatorPosition = $size - $tailBytes + $position - 20;
                if ($locatorPosition < 0 || fseek($file, $locatorPosition) !== 0) {
                    throw $this->invalid();
                }
                $locator = fread($file, 20);
                if ($locator === false || strlen($locator) !== 20 || substr($locator, 0, 4) !== "PK\x06\x07") {
                    throw $this->invalid();
                }
                $zip64 = unpack('Vdisk/Poffset/Vdisks', substr($locator, 4));
                if ($zip64['disk'] !== 0 || $zip64['disks'] !== 1 || $zip64['offset'] < 0 || $zip64['offset'] > $locatorPosition - 56 || fseek($file, $zip64['offset']) !== 0) {
                    throw $this->invalid();
                }
                $record = fread($file, 56);
                if ($record === false || strlen($record) !== 56 || substr($record, 0, 4) !== "PK\x06\x06") {
                    throw $this->invalid();
                }
                $recordBytes = unpack('Psize', substr($record, 4, 8))['size'];
                if ($recordBytes < 44 || $recordBytes > 65536 || $locatorPosition < $zip64['offset'] + 12 + $recordBytes) {
                    throw $this->invalid();
                }
                $directory = unpack('Vdisk/Vdirectory_disk/Pentries_disk/Pentries/Pdirectory_bytes/Pdirectory_offset', substr($record, 16));
            }
            if ($directory['disk'] !== 0 || $directory['directory_disk'] !== 0 || $directory['entries_disk'] !== $directory['entries']
                || $directory['directory_offset'] < 0 || $directory['directory_bytes'] < 0
                || $directory['directory_offset'] > $size || $directory['directory_bytes'] > $size - $directory['directory_offset']) {
                throw $this->invalid();
            }
            if ($directory['entries'] < 0 || $directory['entries'] > $this->limit('max_entries', 1024)
                || $directory['directory_bytes'] > $this->limit('max_directory_bytes', 1048576)) {
                throw $this->invalid('Le fichier XLSX contient trop de composants ou un index ZIP trop volumineux. Simplifiez le classeur.');
            }
        } finally {
            fclose($file);
        }
    }

    private function validateArchive(ZipArchive $zip): void
    {
        if ($zip->numFiles > $this->limit('max_entries', 1024)) {
            throw $this->invalid('Le fichier XLSX contient trop de composants. Simplifiez le classeur avant de l’importer.');
        }
        $total = 0;
        $names = [];
        for ($entry = 0; $entry < $zip->numFiles; $entry++) {
            $stat = $zip->statIndex($entry);
            if ($stat === false || $stat['size'] < 0 || $stat['comp_size'] < 0
                || strlen($stat['name']) > 512 || isset($names[$stat['name']]) || ($stat['encryption_method'] ?? 0) !== 0) {
                throw $this->invalid();
            }
            $names[$stat['name']] = true;
            $total += $stat['size'];
            if ($stat['size'] > $this->limit('max_part_bytes', 134217728) || $total > $this->limit('max_uncompressed_bytes', 268435456)) {
                throw $this->invalid('Le fichier XLSX est trop volumineux une fois décompressé. Utilisez un fichier CSV ou simplifiez le classeur.');
            }
        }
    }

    /** @return array{string, ?string} */
    private function activeSheetPaths(ZipArchive $zip): array
    {
        $sheets = [];
        $active = 0;
        foreach ($this->xmlNodes($zip, 'xl/workbook.xml') as $xml) {
            if ($xml->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            if ($xml->localName === 'workbookView' && $xml->getAttribute('activeTab') !== null) {
                $active = $this->positiveInteger($xml->getAttribute('activeTab'), $this->limit('max_sheets', 64), true);
            } elseif ($xml->localName === 'sheet') {
                $id = $xml->getAttributeNs('id', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships')
                    ?? $xml->getAttributeNs('id', 'http://purl.oclc.org/ooxml/officeDocument/relationships');
                if ($id === null || strlen($id) > 256 || count($sheets) >= $this->limit('max_sheets', 64)) {
                    throw $this->invalid();
                }
                $sheets[] = $id;
            }
        }
        if (! isset($sheets[$active])) {
            throw $this->invalid();
        }
        $sheetPath = null;
        $sharedStringsPath = null;
        foreach ($this->xmlNodes($zip, 'xl/_rels/workbook.xml.rels') as $xml) {
            if ($xml->nodeType !== XMLReader::ELEMENT || $xml->localName !== 'Relationship') {
                continue;
            }
            $type = $xml->getAttribute('Type') ?? '';
            if ($xml->getAttribute('Id') === $sheets[$active] || str_ends_with($type, '/sharedStrings')) {
                if ($xml->getAttribute('TargetMode') === 'External') {
                    throw $this->invalid('Le fichier XLSX contient une référence externe non autorisée.');
                }
                $target = $this->archivePath($xml->getAttribute('Target') ?? '');
                if ($xml->getAttribute('Id') === $sheets[$active]) {
                    if (! str_ends_with($type, '/worksheet') || $sheetPath !== null) {
                        throw $this->invalid();
                    }
                    $sheetPath = $target;
                } else {
                    $sharedStringsPath = $target;
                }
            }
        }
        if ($sheetPath === null) {
            throw $this->invalid();
        }

        return [$sheetPath, $sharedStringsPath];
    }

    /** @param resource $strings @param resource $index */
    private function indexSharedStrings(ZipArchive $zip, string $path, $strings, $index): int
    {
        $count = 0;
        foreach ($this->xmlNodes($zip, $path) as $xml) {
            if ($xml->nodeType !== XMLReader::ELEMENT || $xml->localName !== 'si') {
                continue;
            }
            if ($count >= $this->limit('max_shared_strings', 1000000)) {
                throw $this->invalid('Le fichier XLSX contient trop de textes partagés. Utilisez un fichier CSV.');
            }
            $value = StringHelper::controlCharacterOOXML2PHP($this->elementText($xml, 't'));
            $position = ftell($strings);
            if ($position === false) {
                throw new RuntimeException('Le fichier de travail XLSX ne peut pas être lu.');
            }
            $this->write($index, pack('J', $position));
            $this->write($strings, pack('N', strlen($value)).$value);
            $count++;
        }

        return $count;
    }

    /** @param resource $strings @param resource $index */
    private function sharedString(string $value, $strings, $index, int $count): string
    {
        $number = $this->positiveInteger($value, max(0, $count - 1), true);
        if ($count === 0 || fseek($index, $number * 8) !== 0) {
            throw $this->invalid();
        }
        $offset = fread($index, 8);
        if ($offset === false || strlen($offset) !== 8 || fseek($strings, unpack('Joffset', $offset)['offset']) !== 0) {
            throw $this->invalid();
        }
        $length = fread($strings, 4);
        if ($length === false || strlen($length) !== 4) {
            throw $this->invalid();
        }
        $bytes = unpack('Nlength', $length)['length'];
        $text = $bytes === 0 ? '' : fread($strings, $bytes);
        if ($text === false || strlen($text) !== $bytes) {
            throw $this->invalid();
        }

        return $text;
    }

    /** @return Generator<int, XMLReader> */
    private function xmlNodes(ZipArchive $zip, string $path): Generator
    {
        $stat = $zip->statName($path);
        if ($stat === false) {
            throw $this->invalid();
        }
        $source = $zip->getStream($path);
        if ($source === false) {
            throw $this->invalid();
        }
        $file = null;
        $reader = new XMLReader;
        $previousErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $file = $this->temporaryFile();
            $bytes = 0;
            $xmlBuffer = '';
            while (! feof($source)) {
                $chunk = fread($source, 8192);
                if ($chunk === false) {
                    throw $this->invalid();
                }
                $bytes += strlen($chunk);
                if ($bytes > $this->limit('max_part_bytes', 134217728)) {
                    throw $this->invalid('Le fichier XLSX est trop volumineux une fois décompressé.');
                }
                $this->validateXmlBuffer($xmlBuffer, $chunk);
                $this->write($file, $chunk);
            }
            if ($bytes !== $stat['size']) {
                throw $this->invalid();
            }
            if (! $reader->open(stream_get_meta_data($file)['uri'], null, LIBXML_NONET | LIBXML_COMPACT)) {
                throw $this->invalid();
            }
            $reader->setParserProperty(XMLReader::LOADDTD, false);
            $reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);
            $reader->setParserProperty(XMLReader::VALIDATE, false);
            while ($this->readNode($reader)) {
                yield $reader;
            }
            if (libxml_get_errors() !== []) {
                throw $this->invalid();
            }
        } finally {
            $reader->close();
            fclose($source);
            if (is_resource($file)) {
                fclose($file);
            }
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private function readNode(XMLReader $xml): bool
    {
        $read = $xml->read();
        if ($read && ($xml->depth > 64 || in_array($xml->nodeType, [XMLReader::DOC_TYPE, XMLReader::ENTITY_REF], true))) {
            throw $this->invalid('Le fichier XLSX contient une structure XML non autorisée.');
        }

        return $read;
    }

    private function validateXmlBuffer(string &$buffer, string $chunk): void
    {
        if (str_contains($chunk, "\0")) {
            throw $this->invalid('Le fichier XLSX doit utiliser un encodage XML UTF-8 compatible.');
        }
        $buffer .= $chunk;
        while ($buffer !== '') {
            if ($buffer[0] !== '<') {
                $nextTag = strpos($buffer, '<');
                if ($nextTag !== false) {
                    if ($nextTag > 65536) {
                        throw $this->invalid('Le fichier XLSX contient une structure XML trop volumineuse.');
                    }
                    $buffer = substr($buffer, $nextTag);

                    continue;
                }
            } else {
                if (str_starts_with($buffer, '<!DOCTYPE')) {
                    throw $this->invalid('Le fichier XLSX contient une structure XML non autorisée.');
                }
                $tagPattern = <<<'REGEX'
~\A<(?:"[^"]*"|'[^']*'|[^'">])*+>~s
REGEX;
                $pattern = match (true) {
                    str_starts_with($buffer, '<!--') => '~\A<!--.*?-->~s',
                    str_starts_with($buffer, '<![CDATA[') => '~\A<!\[CDATA\[.*?\]\]>~s',
                    default => $tagPattern,
                };
                $matched = preg_match($pattern, $buffer, $matches);
                if ($matched === false || ($matched === 1 && strlen($matches[0]) > 65536)) {
                    throw $this->invalid('Le fichier XLSX contient une structure XML trop volumineuse.');
                }
                if ($matched === 1) {
                    $buffer = substr($buffer, strlen($matches[0]));

                    continue;
                }
            }
            if (strlen($buffer) > 65536) {
                throw $this->invalid('Le fichier XLSX contient une structure XML trop volumineuse.');
            }
            break;
        }
    }

    private function elementText(XMLReader $xml, string $textElement): string
    {
        if ($xml->isEmptyElement) {
            return '';
        }
        $depth = $xml->depth;
        $textDepth = null;
        $phoneticDepth = null;
        $value = '';
        while ($this->readNode($xml)) {
            if ($xml->nodeType === XMLReader::END_ELEMENT && $xml->depth === $depth) {
                return $value;
            }
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'rPh' && ! $xml->isEmptyElement) {
                $phoneticDepth = $xml->depth;
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->depth === $phoneticDepth) {
                $phoneticDepth = null;
            }
            if ($phoneticDepth !== null) {
                continue;
            }
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === $textElement && ! $xml->isEmptyElement) {
                $textDepth = $xml->depth;
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->depth === $textDepth) {
                $textDepth = null;
            } elseif ($textDepth !== null && in_array($xml->nodeType, [XMLReader::TEXT, XMLReader::CDATA, XMLReader::SIGNIFICANT_WHITESPACE, XMLReader::WHITESPACE], true)) {
                if (strlen($value) + strlen($xml->value) > $this->limit('max_cell_bytes', 4096)) {
                    throw $this->invalid('Le fichier XLSX contient une cellule trop volumineuse.');
                }
                $value .= $xml->value;
            }
        }

        throw $this->invalid();
    }

    private function archivePath(string $target): string
    {
        if ($target === '' || strlen($target) > 512 || preg_match('~[:\\\\\x00]~', $target) || str_starts_with($target, '//')) {
            throw $this->invalid();
        }
        $parts = [];
        foreach (explode('/', str_starts_with($target, '/') ? substr($target, 1) : 'xl/'.$target) as $part) {
            if ($part === '..') {
                if ($parts === []) {
                    throw $this->invalid();
                }
                array_pop($parts);
            } elseif ($part !== '' && $part !== '.') {
                $parts[] = $part;
            }
        }

        return implode('/', $parts);
    }

    private function columnIndex(string $coordinate, int $row): int
    {
        if (! preg_match('/^([A-Z]{1,3})([1-9][0-9]{0,6})$/', $coordinate, $matches) || (int) $matches[2] !== $row) {
            throw $this->invalid();
        }
        $column = 0;
        foreach (str_split($matches[1]) as $letter) {
            $column = $column * 26 + ord($letter) - 64;
        }
        if ($column > 16384) {
            throw $this->invalid();
        }

        return $column - 1;
    }

    private function positiveInteger(string $value, int $maximum, bool $allowZero = false): int
    {
        if (! preg_match('/^(0|[1-9][0-9]{0,8})$/', $value) || (int) $value > $maximum || (! $allowZero && (int) $value === 0)) {
            throw $this->invalid();
        }

        return (int) $value;
    }

    private function limit(string $key, int $default): int
    {
        return max(1, (int) config('risfm.registry_import.xlsx.'.$key, $default));
    }

    /** @return resource */
    private function temporaryFile(): mixed
    {
        $file = tmpfile();
        if ($file === false) {
            throw new RuntimeException('Un fichier de travail XLSX ne peut pas être créé.');
        }

        return $file;
    }

    /** @param resource $file */
    private function write($file, string $bytes): void
    {
        if (fwrite($file, $bytes) !== strlen($bytes)) {
            throw new RuntimeException('Le fichier de travail XLSX ne peut pas être écrit.');
        }
    }

    private function invalid(string $message = 'Le fichier XLSX ne peut pas être lu. Vérifiez qu’il est valide et non chiffré.'): ValidationException
    {
        return ValidationException::withMessages(['import_file' => $message]);
    }
}
