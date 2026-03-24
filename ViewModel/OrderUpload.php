<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\ViewModel;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Oporteo\OrderUpload\Helper\Config;

class OrderUpload implements ArgumentInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly HttpContext $httpContext,
        private readonly UrlInterface $urlBuilder,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function getPageText(): string
    {
        return $this->config->getPageText();
    }

    public function isLoggedIn(): bool
    {
        return (bool) $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    public function canUpload(): bool
    {
        return $this->isLoggedIn() || $this->config->isGuestCheckoutAllowed();
    }

    public function isPriceListEnabled(): bool
    {
        return $this->config->isPriceListEnabled();
    }

    public function getValidateUrl(): string
    {
        return $this->urlBuilder->getUrl('orderupload/index/validate');
    }

    public function getProceedUrl(): string
    {
        return $this->urlBuilder->getUrl('orderupload/index/proceed');
    }

    public function getTemplateUrl(): string
    {
        return $this->urlBuilder->getUrl('orderupload/template/download');
    }

    public function getPriceListUrl(): string
    {
        return $this->urlBuilder->getUrl('orderupload/pricelist/index');
    }

    public function getLoginUrl(): string
    {
        return $this->urlBuilder->getUrl('customer/account/login');
    }

    public function getWidgetInitConfig(array $widgetInitParams = []): string
    {
        $config = array_merge(
            [
                'validateUrl' => $this->getValidateUrl(),
                'proceedUrl' => $this->getProceedUrl(),
            ],
            $widgetInitParams
        );

        return $this->serializer->serialize([
            'Oporteo_OrderUpload/js/order-upload' => $config,
        ]);
    }
}
