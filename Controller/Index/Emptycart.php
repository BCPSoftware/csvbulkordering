<?php

namespace Oporteo\Csvorderupload\Controller\Index;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Emptycart extends \Magento\Framework\App\Action\Action
{
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        private PageFactory $resultPageFactory,
        private Cart $cart
    ) {
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->cart->truncate();
        $this->cart->saveQuote();

        return $this->resultRedirectFactory->create()->setPath(
            'orderupload',
            ['_secure'=>$this->getRequest()->isSecure()]
        );
    }
}
