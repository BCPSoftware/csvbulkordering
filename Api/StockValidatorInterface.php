<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

interface StockValidatorInterface
{
    /**
     * @throws LocalizedException
     */
    public function validate(ProductInterface $product, DataObject $buyRequest): void;
}
