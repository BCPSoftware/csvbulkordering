<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Model\Reader;

use Magento\Framework\Exception\LocalizedException;
use Oporteo\Csvorderupload\Api\FileReaderInterface;
use Shuchkin\SimpleXLSX;

class Xlsx implements FileReaderInterface
{
    /**
     * @param string $filePath
     * @return array
     * @throws LocalizedException
     */
    public function read(string $filePath): array
    {
        $xlsx = SimpleXLSX::parse($filePath);
        $content = [];

        foreach ($xlsx->readRows() as $id => $row) {
            if ($id === 0) {
                array_walk($row, static fn (&$value) => $value = strtolower(trim($value)));
                $skuIndex = array_search(self::COL_SKU, $row, true);
                $qtyIndex = array_search(self::COL_QTY, $row, true);

                continue;
            }

            if ($skuIndex === -1 || $qtyIndex === -1) {
                throw new LocalizedException(__('Cannot read file header.'));
            }

            $content[] = [
                self::COL_SKU => $row[$skuIndex],
                self::COL_QTY => (int) $row[$qtyIndex],
            ];
        }

        return $content;
    }
}
