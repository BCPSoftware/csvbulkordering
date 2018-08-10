<?php

namespace Oporteo\Csvorderupload\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class ConfigObserver implements ObserverInterface
{

    public function __construct(
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    ) {
        $this->_cacheTypeList        = $cacheTypeList;
        $this->_cacheFrontendPool    = $cacheFrontendPool;
    }

    public function execute(EventObserver $observer)
    {
        $this->_cacheTypeList->cleanType('full_page');
    }
}
