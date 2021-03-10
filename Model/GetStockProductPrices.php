<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Model;

use Exception;
use Magento\Catalog\Model\ProductRepository;
use Oporteo\Csvorderupload\Api\GetStockProductPricesInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GetStockProductPrices
 */
class GetStockProductPrices implements GetStockProductPricesInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetStockProductPrices constructor.
     *
     * @param ProductRepository $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $skus
     *
     * @return array
     */
    public function execute(array $skus): array
    {
        $productPrices = [];

        foreach ($skus as $sku) {
            $productPrices[$sku] = $this->getProductPrice($sku);
        }

        return $productPrices;
    }

    /**
     * @param string $sku
     *
     * @return float
     */
    private function getProductPrice(string $sku): float
    {
        try {
            return (float)$this->productRepository->get($sku)->getPrice();
        } catch (Exception $exception) {
            $this->logger->critical($exception);
        }
        
        return 0.0;
    }
}