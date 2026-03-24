<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model\Upload;

use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Oporteo\OrderUpload\Api\StockValidatorInterface;
use Oporteo\OrderUpload\Model\ValidationStorage;

class Validator
{
    public function __construct(
        private readonly FileParser $fileParser,
        private readonly ProductResolver $productResolver,
        private readonly StockValidatorInterface $stockValidator,
        private readonly EventManagerInterface $eventManager,
        private readonly ValidationStorage $validationStorage
    ) {
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    public function validate(array $file, ?int $storeId = null): array
    {
        $rows = $this->fileParser->parse($file, $storeId);
        $validRows = [];
        $successes = [];
        $failures = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $sku = trim((string) ($row['sku'] ?? ''));
                $qty = (int) ($row['qty'] ?? 0);

                if ($sku === '') {
                    throw new LocalizedException(__('SKU is required.'));
                }

                if ($qty <= 0) {
                    throw new LocalizedException(__('Quantity must be greater than 0.'));
                }

                $product = $this->productResolver->getBySku($sku, $storeId);
                $buyRequest = new DataObject(['qty' => $qty]);
                $errors = new DataObject(['messages' => []]);
                $this->eventManager->dispatch(
                    'oporteo_orderupload_prepare_buy_request',
                    [
                        'row' => $row,
                        'product' => $product,
                        'qty' => $qty,
                        'store_id' => $storeId,
                        'buy_request' => $buyRequest,
                        'errors' => $errors,
                    ]
                );

                $messages = array_filter(array_map('trim', (array)$errors->getData('messages')));

                if ($messages !== []) {
                    throw new LocalizedException(__(implode(' ', $messages)));
                }

                $this->stockValidator->validate($product, $buyRequest);

                $preparedRow = [
                    'row' => (string)$rowNumber,
                    'sku' => $sku,
                    'name' => (string)$product->getName(),
                    'qty' => $this->formatQty($qty),
                    'buy_request_data' => (array)$buyRequest->getData(),
                ];

                $validRows[] = $preparedRow;
                $successes[] = [
                    'row' => $preparedRow['row'],
                    'sku' => $preparedRow['sku'],
                    'name' => $preparedRow['name'],
                    'qty' => $preparedRow['qty'],
                ];
            } catch (\Exception $exception) {
                $failures[] = [
                    'row' => (string)$rowNumber,
                    'sku' => trim((string)($row['sku'] ?? '')),
                    'reason' => $this->resolveReason($exception),
                ];
            }
        }

        $token = $this->validationStorage->store([
            'valid_rows' => $validRows,
        ]);

        return [
            'token' => $token,
            'successes' => $successes,
            'failures' => $failures,
            'success_count' => count($successes),
            'failure_count' => count($failures),
        ];
    }

    private function formatQty(float $qty): string
    {
        if ((float)(int)$qty === $qty) {
            return (string)(int)$qty;
        }

        return rtrim(rtrim(sprintf('%.4F', $qty), '0'), '.');
    }

    private function resolveReason(\Exception $exception): string
    {
        $message = trim($exception->getMessage());

        if ($message !== '') {
            return $message;
        }

        return (string)__('Product failed validation.');
    }
}
