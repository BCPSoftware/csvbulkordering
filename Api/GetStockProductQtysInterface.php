<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Api;

/**
 * Interface GetStockProductQtys
 */
interface GetStockProductQtysInterface
{
    /**
     * @param array $skus
     *
     * @return array
     */
    public function execute(array $skus): array;
}