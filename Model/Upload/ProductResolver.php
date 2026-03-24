<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model\Upload;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductResolver
{
    public function __construct(
        private readonly CollectionFactory $productCollectionFactory
    ) {
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getBySku(string $sku, ?int $storeId = null): ProductInterface
    {
        $normalizedSku = trim($sku);

        if ($normalizedSku === '') {
            throw new NoSuchEntityException(__('The product with SKU "%1" does not exist.', $sku));
        }

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setStoreId($storeId);
        $productCollection->addAttributeToSelect(['name', 'sku']);
        $productCollection->getSelect()->where('LOWER(e.sku) = ?', mb_strtolower($normalizedSku));
        $productCollection->setPageSize(1);

        $product = $productCollection->getFirstItem();

        if (!$product->getId()) {
            throw new NoSuchEntityException(__('The product with SKU "%1" does not exist.', $sku));
        }

        return $product;
    }
}
