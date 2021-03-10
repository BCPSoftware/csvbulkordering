<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Api;

/**
 * Interface GetStockProductPricesInterface
 */
interface GetStockProductPricesInterface
{
    /**
     * @param array $skus
     *
     * @return array
     */
    public function execute(array $skus): array;
}