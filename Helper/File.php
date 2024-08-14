<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Helper;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Oporteo\Csvorderupload\Model\Reader\Csv;
use Oporteo\Csvorderupload\Model\Reader\Xlsx;

class File
{
    private const string VAR_FOLDER = 'upload_failed';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly WriteFactory $writeFactory,
        private readonly Csv $csv,
        private readonly Xlsx $xlsx,
    ) {
    }

    /**
     * @param array $file
     * @return array<string, int>
     * @throws ValidatorException
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function getContent(array $file): array
    {
        try {
            return match ($file['type']) {
                'text/csv' => $this->csv->read(filePath: $file['tmp_name']),
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => $this->xlsx->read(
                    filePath: $file['tmp_name']
                ),
                default => throw new ValidatorException(__('File format not supported.')),
            };
        } catch (Exception $e) {
            $this->moveFile(filePath: $file['tmp_name'], filename: $file['name']);

            throw $e;
        }
    }

    /**
     * @param array $filePath
     * @param string $filename
     * @return void
     * @throws FileSystemException
     * @throws ValidatorException
     */
    private function moveFile(array $filePath, string $filename): void
    {
        $varDir = $this->filesystem->getDirectoryRead(directoryCode: DirectoryList::VAR_DIR);
        $subDir = $this->writeFactory->create(path: $varDir->getAbsolutePath(path: self::VAR_FOLDER));

        $subDir->getDriver()->copy(source: $filePath, destination: $subDir->getAbsolutePath($filename));

    }
}
