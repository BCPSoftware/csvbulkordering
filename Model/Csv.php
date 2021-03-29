<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Model;

/**
 * Class Csv
 */
class Csv extends \Magento\Framework\File\Csv
{
    /**
     * @param string $file
     * @param array $data
     * @param string $mode
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function appendData($file, $data, $mode = 'w'): self
    {
        $bom = chr(239) . chr(187) . chr(191);

        if (file_exists($file)) {
            $fileHandler = fopen($file, $mode);
        } else {
            $fileHandler = fopen($file, $mode);
            fwrite($fileHandler, $bom);
        }

        foreach ($data as $dataRow) {
            $this->file->filePutCsv($fileHandler, $dataRow, $this->_delimiter, $this->_enclosure);
        }

        fclose($fileHandler);

        return $this;
    }
}