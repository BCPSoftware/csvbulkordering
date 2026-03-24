<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Oporteo\OrderUpload\Model\Upload\Validator;

class Validate implements HttpPostActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly Validator $validator,
    ) {
    }

    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();

        try {
            return $resultJson->setData([
                'success' => true,
                'summary' => $this->validator->validate(
                    (array) ($this->request->getFiles('upload_file') ?? []),
                    (int) $this->storeManager->getStore()->getId()
                ),
            ]);
        } catch (\Exception $exception) {
            return $resultJson->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
