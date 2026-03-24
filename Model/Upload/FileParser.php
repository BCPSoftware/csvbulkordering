<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model\Upload;

use finfo;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Oporteo\OrderUpload\Model\Columns\CollectionFactory as ColumnCollectionFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FileParser
{
    private const string FILE_TYPE_CSV = 'csv';
    private const string FILE_TYPE_SPREADSHEET = 'spreadsheet';

    private const array SUPPORTED_MIME_TYPES = [
        self::FILE_TYPE_CSV => [
            'application/csv',
            'text/csv',
            'text/plain',
            'text/x-csv',
        ],
        self::FILE_TYPE_SPREADSHEET => [
            'application/cdfv2',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ],
    ];

    private const array AMBIGUOUS_MIME_TYPES = [
        'application/vnd.ms-excel',
    ];

    public function __construct(
        private readonly EventManagerInterface $eventManager,
        private readonly ColumnCollectionFactory $columnCollectionFactory,
    ) {
    }

    /**
     * @param array $file
     * @return array
     * @throws LocalizedException
     */
    public function parse(array $file, ?int $storeId = null): array
    {
        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($errorCode !== UPLOAD_ERR_OK || $tmpPath === '') {
            throw new LocalizedException(__('File upload failed. Please try again.'));
        }

        $fileType = $this->resolveFileType(path: $tmpPath);

        if ($fileType === null) {
            throw new LocalizedException(__('Only CSV, XLS, and XLSX files are supported.'));
        }

        $rows = $fileType === self::FILE_TYPE_CSV
            ? $this->readCsv(path: $tmpPath)
            : $this->readSpreadsheet(path: $tmpPath);

        if ($rows === []) {
            throw new LocalizedException(__('The uploaded file is empty.'));
        }

        $headers = array_shift($rows);

        if (!is_array($headers)) {
            throw new LocalizedException(__('Unable to read file headers.'));
        }

        $normalizedHeaders = $this->normalizeHeaders(headers: $headers);
        $this->validateHeaders(headers: $normalizedHeaders, storeId: $storeId);

        $mappedRows = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $mappedRow = [];
            foreach ($normalizedHeaders as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $mappedRow[$header] = isset($row[$index]) ? trim((string)$row[$index]) : null;
            }

            if ($this->isEmptyRow($mappedRow)) {
                continue;
            }

            $mappedRows[] = $mappedRow;
        }

        return $mappedRows;
    }

    protected function detectMimeType(string $path): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        return is_string($mimeType) ? $mimeType : '';
    }

    private function resolveFileType(string $path): ?string
    {
        $mimeType = strtolower($this->detectMimeType(path: $path));

        if ($mimeType === '') {
            return null;
        }

        if (in_array($mimeType, self::AMBIGUOUS_MIME_TYPES, true)) {
            return $this->isBinaryFile(path: $path) ? self::FILE_TYPE_SPREADSHEET : self::FILE_TYPE_CSV;
        }

        if (in_array($mimeType, self::SUPPORTED_MIME_TYPES[self::FILE_TYPE_CSV], true)) {
            return self::FILE_TYPE_CSV;
        }

        if (in_array($mimeType, self::SUPPORTED_MIME_TYPES[self::FILE_TYPE_SPREADSHEET], true)) {
            return self::FILE_TYPE_SPREADSHEET;
        }

        return null;
    }

    private function isBinaryFile(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $chunk = fread($handle, 512);
        fclose($handle);

        return is_string($chunk) && str_contains($chunk, "\0");
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return $rows;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(
                static fn ($value): ?string => $value === null ? null : trim((string) $value),
                $row
            );
        }

        fclose($handle);

        if ($rows !== [] && isset($rows[0][0])) {
            $rows[0][0] = $this->stripUtf8Bom((string) $rows[0][0]);
        }

        return $rows;
    }

    /**
     * @param string $path
     * @return array
     * @throws LocalizedException
     */
    private function readSpreadsheet(string $path): array
    {
        if (!class_exists(IOFactory::class)) {
            throw new LocalizedException(__('Spreadsheet support is not available.'));
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        return array_map(
            static fn (array $row): array => array_map(
                static fn ($value): ?string => $value === null ? null : trim((string) $value),
                $row
            ),
            $rows
        );
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalizedHeaders = [];

        foreach ($headers as $header) {
            $normalizedHeaders[] = $this->normalizeHeader(header: $header);
        }

        return $normalizedHeaders;
    }

    private function normalizeHeader(?string $header): string
    {
        return strtolower(trim((string) $header));
    }

    /**
     * @param array $headers
     * @return void
     * @throws LocalizedException
     */
    private function validateHeaders(array $headers, ?int $storeId = null): void
    {
        $headerCollection = $this->columnCollectionFactory->create(data: ['columns' => $headers]);

        if (!$headerCollection->hasColumn(column: 'sku') || !$headerCollection->hasColumn(column: 'qty')) {
            throw new LocalizedException(__('File must include sku and qty columns.'));
        }

        $this->eventManager->dispatch(
            eventName: 'oporteo_orderupload_validate_headers',
            data: [
                'headers' => $headerCollection,
                'store_id' => $storeId,
            ]
        );
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    private function stripUtf8Bom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }
}
