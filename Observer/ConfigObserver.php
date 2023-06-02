<?php

namespace Oporteo\Csvorderupload\Observer;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ConfigObserver implements ObserverInterface
{

    public function __construct(
        private TypeListInterface $cacheTypeList,
        private Pool $cacheFrontendPool
    ) {}

    public function execute(EventObserver $observer)
    {
        $this->_cacheTypeList->cleanType('full_page');
    }
}
