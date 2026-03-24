<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
    ) {}

    public function execute(): ResultInterface
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->getConfig()->getTitle()->set(title: __('Order Upload'));

        return $resultPage;
    }
}
