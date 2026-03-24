<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\AdminOrder\Create;
use Oporteo\OrderUpload\Helper\Config;
use Oporteo\OrderUpload\Model\Upload\AdminOrderProcessor;
use Oporteo\OrderUpload\Model\Upload\Validator;

class Upload extends Action
{
    public const string ADMIN_RESOURCE = 'Magento_Sales::create';

    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly Config $config,
        private readonly Validator $validator,
        private readonly AdminOrderProcessor $adminOrderProcessor,
        private readonly Create $adminOrderCreate
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();

        try {
            $storeId = (int) $this->adminOrderCreate->getQuote()->getStoreId();

            if (!$this->config->isEnabled($storeId)) {
                throw new LocalizedException(__('Order upload functionality is disabled.'));
            }

            $file = $this->getRequest()->getFiles('file');

            if (!is_array($file) || $file === []) {
                throw new LocalizedException(__('Please select a file to upload.'));
            }

            $validationResult = $this->validator->validate(file: $file, storeId: $storeId);
            $processingResult = $this->adminOrderProcessor->process(
                token: (string) $validationResult['token'],
                adminOrderCreate: $this->adminOrderCreate
            );

            $successCount = count($processingResult['successes']);
            $failureCount = (int) $validationResult['failure_count'] + (int) $processingResult['failure_count'];

            if ($successCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('%1 product(s) were added to the order.', $successCount)
                );
            }

            if ($failureCount > 0) {
                $this->messageManager->addErrorMessage(
                    __('%1 product(s) could not be added from the uploaded file.', $failureCount)
                );
            }

            return $resultJson->setData([
                'success' => true,
            ]);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

            return $resultJson->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
