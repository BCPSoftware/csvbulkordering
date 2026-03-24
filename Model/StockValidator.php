<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Oporteo\OrderUpload\Api\StockValidatorInterface;

class StockValidator implements StockValidatorInterface
{
    public function __construct(
        private readonly StockRegistryInterface $stockRegistry
    ) {
    }

    public function validate(ProductInterface $product, DataObject $buyRequest): void
    {
        $qty = (int)$buyRequest->getData('qty');

        if ($qty <= 0) {
            throw new LocalizedException(__('Quantity must be greater than 0.'));
        }

        if (!$product->isSaleable()) {
            throw new LocalizedException(__('Product is not available for sale.'));
        }

        $stockItem = $this->stockRegistry->getStockItem((int)$product->getId());
        if (!$stockItem || !$stockItem->getManageStock()) {
            return;
        }

        if (!$stockItem->getIsInStock()) {
            throw new LocalizedException(__('Product is out of stock.'));
        }

        if (!$stockItem->getBackorders() && $stockItem->getQty() < $qty) {
            throw new LocalizedException(__('Requested quantity is not available.'));
        }
    }
}
