<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Observer;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ConfigObserver implements ObserverInterface
{
    /**
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        private TypeListInterface $cacheTypeList
    ) {}

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer): void
    {
        $this->cacheTypeList->cleanType('full_page');
    }
}
