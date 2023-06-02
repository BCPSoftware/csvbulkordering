<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        private PageFactory $resultPageFactory,
        private ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page
     * @throws NotFoundException
     */
    public function execute()
    {
        $storeScope = ScopeInterface::SCOPE_STORE;
        $enableConfig = $this->scopeConfig->getValue('oporteo/general/enabled', $storeScope);
        if ($enableConfig) {
            return $this->resultPageFactory->create();
        } else {
            throw new NotFoundException(__('Parameter is incorrect.'));
        }
    }
}
