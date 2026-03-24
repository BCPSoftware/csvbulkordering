<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model\Upload;

use Exception;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;

class ValidatedRowsProcessor
{
    public function __construct(
        private readonly ProductResolver $productResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param callable $addProduct Receives product, buy request array|DataObject, original row
     * @return array{successes: array<int, array<string, string>>, failure_count: int}
     */
    public function process(array $rows, ?int $storeId, callable $addProduct, string $logMessage): array
    {
        $results = [
            'successes' => [],
            'failure_count' => 0,
        ];

        foreach ($rows as $index => $row) {
            try {
                $sku = trim((string) ($row['sku'] ?? ''));
                $rowNumber = (string) ($row['row'] ?? (string) ($index + 2));
                $product = $this->productResolver->getBySku($sku, $storeId);
                $buyRequestData = (array) ($row['buy_request_data'] ?? []);

                $addProduct($product, new DataObject($buyRequestData), $row);

                $results['successes'][] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'name' => (string) $product->getName(),
                    'qty' => (string) ($row['qty'] ?? ''),
                ];
            } catch (Exception $exception) {
                $results['failure_count']++;
                $this->logger->error(
                    $logMessage,
                    [
                        'row' => $row['row'] ?? (string) ($index + 2),
                        'sku' => $row['sku'] ?? '',
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }

        return $results;
    }
}
