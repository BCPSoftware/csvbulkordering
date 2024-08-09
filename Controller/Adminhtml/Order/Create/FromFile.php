<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Oporteo\Csvorderupload\Api\Data\RowInterface;
use Oporteo\Csvorderupload\Helper\File;
use Oporteo\Csvorderupload\Model\Processor;

class FromFile extends Action
{
    public function __construct(
        Context $context,
        private readonly File $fileHelper,
        private readonly Processor $processor,
        private readonly Quote $backendQuote,
        private readonly Registry $registry,
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $this->registry->register(key: 'isApiArea', value: true);
        $rows = $this->fileHelper->getContent(file: $this->getRequest()->getFiles(name: 'file'));
        $quote = $this->backendQuote->getQuote();

        try {
            $this->processor->process(quote: $quote, products: $rows);

            $warnings = $this->processor->getErrors();

            foreach ($warnings as $warning) {
                $this->messageManager->addWarningMessage(message: $warning);
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(message: $e->getMessage());
        }

        $this->registry->unregister(key: 'isApiArea');

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([]);
    }
}
