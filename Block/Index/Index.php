<?php

namespace Oporteo\Csvorderupload\Block\Index;

class Index extends \Magento\Framework\View\Element\Template
{

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Oporteo\Csvorderupload\Helper\Data $helperData,
        array $data = []
    ) {
        $this->helper   = $helperData;
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
