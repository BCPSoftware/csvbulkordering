<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Oporteo\OrderUpload\Model\Upload\Processor;

class Proceed implements HttpPostActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly RequestInterface $request,
        private readonly Processor $processor,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();

        try {
            $token = trim((string) $this->request->getParam('token'));

            if ($token === '') {
                throw new \InvalidArgumentException((string)__('Validation session has expired. Please upload the file again.'));
            }

            $results = $this->processor->process($token, $this->checkoutSession->getQuote());
            $summary = [
                'success' => $results['successes'] !== []
                    ? [
                        'title' => (string) __('%1 product(s) were added to your cart.', count($results['successes'])),
                        'items' => $results['successes'],
                    ]
                    : null,
                'error' => (int)$results['failure_count'] > 0
                    ? [
                        'title' => (string) __('%1 validated product(s) could not be added to your cart.', (int) $results['failure_count']),
                        'message' => (string) __('Some products failed during cart processing. Please review the cart below.'),
                    ]
                    : null,
            ];

            return $resultJson->setData([
                'success' => true,
                'summary' => $summary,
            ]);
        } catch (\Exception $exception) {
            return $resultJson->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
