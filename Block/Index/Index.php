<?php

namespace Oporteo\Csvorderupload\Block\Index;

use Magento\Framework\View\Element\Template\Context;
use Oporteo\Csvorderupload\Helper\Data;

class Index extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        Context $context,
        private Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCacheLifetime()
    {
        return null;
    }

    public function getConfig($config_path)
    {
        return $this->helper->getConfig($config_path);
    }

    public function isEmptyCart()
    {
        return $this->helper->isEmptyCart();
    }

    public function isScopePrivate()
    {
        return true;
    }
}
