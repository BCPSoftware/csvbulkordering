<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Observer;

use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Oporteo\OrderUpload\Helper\Config;

class DisableRouteObserver implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly RequestInterface $request,
        private readonly HttpResponse $response,
        private readonly ActionFlag $actionFlag,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function execute(Observer $observer): void
    {
        if ($this->config->isEnabled()) {
            return;
        }

        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

        if ($this->request->isXmlHttpRequest()) {
            $this->response->setHttpResponseCode(403);
            $this->response->representJson($this->serializer->serialize([
                'success' => false,
                'message' => (string)__('Order upload is currently disabled.'),
            ]));

            return;
        }

        $this->request->initForward();
        $this->request->setRouteName('cms');
        $this->request->setControllerName('noroute');
        $this->request->setActionName('index');
        $this->request->setDispatched(false);
    }
}
