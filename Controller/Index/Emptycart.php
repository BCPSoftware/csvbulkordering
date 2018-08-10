<?php


namespace Oporteo\Csvorderupload\Controller\Index;

class Emptycart extends \Magento\Framework\App\Action\Action
{

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        $this->cart = $cart;
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
