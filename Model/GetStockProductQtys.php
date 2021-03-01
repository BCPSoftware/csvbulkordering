<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Model;

use Exception;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Oporteo\Csvorderupload\Api\GetStockProductQtysInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GetStockProductQtys
 */
class GetStockProductQtys implements GetStockProductQtysInterface
{
    /**
     * @var GetProductSalableQtyInterface
     */
    private $productSalableQty;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetStockProductQtys constructor.
     *
     * @param GetProductSalableQtyInterface $productSalableQty
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetProductSalableQtyInterface $productSalableQty,
        DefaultStockProviderInterface $defaultStockProvider,
        LoggerInterface $logger
    ) {
        $this->productSalableQty = $productSalableQty;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->logger = $logger;
    }

    /**
     * Get Product Quantities for given SKU and Stock
     *
     * @param array $skus
     *
     * @return array
     */
    public function execute(array $skus): array
    {
        $productQtys = [];

        foreach ($skus as $sku) {
            $productQtys[$sku] = $this->getProductSalableQty($sku);
        }

        return $productQtys;
    }

    /**
     * Get product salable qty
     *
     * @param string $sku
     *
     * @return float
     */
    private function getProductSalableQty(string $sku): float
    {
        $stockId = $this->defaultStockProvider->getId();

        try {
            return $this->productSalableQty->execute($sku, $stockId);
        } catch (Exception $exception) {
            $this->logger->critical($exception);
        }

        return 0.0;
    }
}
