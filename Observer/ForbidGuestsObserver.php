<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Observer;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Oporteo\OrderUpload\Helper\Config;

class ForbidGuestsObserver implements ObserverInterface
{
    public function __construct(
        private readonly HttpContext $httpContext,
        private readonly Config $config,
        private readonly ActionFlag $actionFlag,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (
            $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)
            || $this->config->isGuestCheckoutAllowed()
        ) {
            return;
        }

        $controllerAction = $observer->getEvent()->getControllerAction();

        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

        /** @var HttpResponse $response */
        $response = $controllerAction->getResponse();
        $response->setHttpResponseCode(403);
        $response->representJson($this->serializer->serialize([
            'success' => false,
            'message' => (string)__('Please sign in to use order upload.'),
        ]));
    }
}
