<?php


namespace Oporteo\Csvorderupload\Controller\Index;

use \Magento\Framework\Exception\NotFoundException;

class Index extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        parent::__construct($context);
        $this->scopeConfig          = $scopeConfig;
    }

    public function execute()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $enableConfig = $this->scopeConfig->getValue('oporteo/general/enabled', $storeScope);
        if ($enableConfig) {
            return $this->resultPageFactory->create();
        } else {
            throw new NotFoundException(__('Parameter is incorrect.'));
        }
    }
}
