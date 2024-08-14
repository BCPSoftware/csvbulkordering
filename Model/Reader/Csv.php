<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Model\Reader;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Oporteo\Csvorderupload\Api\FileReaderInterface;

class Csv implements FileReaderInterface
{
    public function __construct(
        private readonly \Magento\Framework\File\Csv $csv,
    ) {
    }

    /**
     * @param string $filePath
     * @return array
     * @throws Exception
     */
    public function read(string $filePath): array
    {
        $content = $this->csv->getData(file: $filePath);

        $headerRow = $content[0];
        unset($content[0]);

        array_walk($headerRow, static fn (&$value) => $value = strtolower(trim($value)));
        $skuIndex = array_search(self::COL_SKU, $headerRow, true);
        $qtyIndex = array_search(self::COL_QTY, $headerRow, true);

        if ($skuIndex === -1 || $qtyIndex === -1) {
            throw new LocalizedException(__('Cannot read file header.'));
        }

        return array_reduce(
            $content,
            static function (array $carry, array $item) use ($skuIndex, $qtyIndex) {
                $qty = (int) $item[$qtyIndex];

                if ($qty) {
                    $carry[] = [
                        self::COL_SKU => $item[$skuIndex],
                        self::COL_QTY => $qty,
                    ];
                }

                return $carry;
            },
            []
        );
    }
}
