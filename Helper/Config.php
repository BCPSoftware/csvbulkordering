<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const string XML_PATH_ENABLED = 'order_upload/general/enabled';
    private const string XML_PATH_PRICE_LIST = 'order_upload/general/price_list';
    private const string XML_PATH_PAGE_TEXT = 'order_upload/general/page_text';
    private const string XML_PATH_GUEST_CHECKOUT = 'checkout/options/guest_checkout';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPageText(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PAGE_TEXT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isPriceListEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRICE_LIST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isGuestCheckoutAllowed(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GUEST_CHECKOUT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
