<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Model;

use Accord\Api\Helper\AttributeManager;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

class Processor
{
    private array $errors = [];

    public function __construct(
        private readonly CollectionFactory $productCollectionFactory,
        private readonly CartRepositoryInterface $cartRepository,
    ) {
    }

    /**
     * @param CartInterface $quote
     * @param array<string, int> $products key - SKU value - Quantity
     * @return void
     * @throws LocalizedException
     */
    public function process(CartInterface $quote, array $products): void
    {
        $this->reset();
        $collection = $this->getProductCollection(skus: array_column($products, 'sku'));
        $skus = [];

        foreach ($products as $data) {
            [$sku, $qty] = array_values($data);

            if (in_array($sku, $skus, true)) {
                $this->addError(
                    sku: $sku,
                    message: __('Multiple records for product "%1" found. Only first occurrence will be processed.', $sku)
                );

                continue;
            }

            $skus[] = $sku;

            if ($product = $collection->getItemByColumnValue(column: ProductInterface::SKU, value: $sku)) {
                $quote->addProduct(product: $product, request: $qty);

                continue;
            }

            $this->addError(sku: $sku, message: __('Product "%1" not found.', $sku));
        }

        $this->cartRepository->save(quote: $quote);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    private function getProductCollection(array $skus): Collection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect([AttributeManager::CASE_ONLY]);
        $collection->addFieldToFilter(ProductInterface::SKU, $skus);

        return $collection;
    }

    private function addError(string $sku, Phrase|string $message): void
    {
        if (!isset($this->errors[$sku])) {
            $this->errors[$sku] = $message;
        }
    }

    private function reset(): void
    {
        $this->errors = [];
    }
}
