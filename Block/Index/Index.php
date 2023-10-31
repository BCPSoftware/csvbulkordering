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

    public function getCacheLifetime(): null
    {
        return null;
    }

    public function getConfig(string $config_path): string
    {
        return $this->helperData->getConfig($config_path);
    }

    public function isEmptyCart(): bool
    {
        return $this->helperData->isEmptyCart();
    }

    public function isScopePrivate(): true
    {
        return true;
    }
}
