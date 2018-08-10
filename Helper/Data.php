<?php

namespace Oporteo\Csvorderupload\Helper;

use Magento\Framework\App\Helper\Context;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\ScopeInterface as ScopeInterface;

class Data extends AbstractHelper
{
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Checkout\Helper\Cart $cartHelper
    ) {
        $this->cartHelper = $cartHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context);
    }

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isEmptyCart()
    {
        $result = ($this->cartHelper->getItemsCount() === 0) ? true : false;

        return $result;
    }

    public function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', ['in' => ['simple', 'virtual']])
            ->addAttributeToFilter('status', '1')
            ->addAttributeToFilter('visibility', ['in' => ['2', '3', '4']]);

        return $collection;
    }

    public function getProductCollectionBySku($skuArr)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('sku', $skuArr);
        ;

        return $collection;
    }

    public function getKeysForDuplicateValues($my_arr, $clean = false)
    {
        if ($clean) {
            return array_unique($my_arr);
        }

        $dups = $new_arr = [];
        foreach ($my_arr as $key => $val) {
            if (!isset($new_arr[$val])) {
                $new_arr[$val] = $key;
            } else {
                if (isset($dups[$val])) {
                    $dups[$val][] = $key;
                } else {
                    $dups[$val] = [$key];
                }
            }
        }
        return $dups;
    }

    public function getAllSkusArr()
    {
        $prodCollection = $this->getProductCollection();
        $skusArr        = [];

        foreach ($prodCollection as $product) {
            $skusArr[]  = $product->getSku();
        }

        return $skusArr;
    }

    public function getArrElCount($arr)
    {
        return count($arr);
    }
}
