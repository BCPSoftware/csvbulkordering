<?php

namespace Oporteo\Csvorderupload\Controller\Index;

class Fileupload extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product $product,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem,
        \Oporteo\Csvorderupload\Helper\Data $helper
    ) {
        $this->filesystem   = $filesystem;
        $this->_file        = $file;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->csv          = $csv;
        $this->formKey      = $formKey;
        $this->cart         = $cart;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->product      = $product;
        $this->stockItem    = $stockItem;
        $this->helper       = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $log    = [];
        $result = [];

        try {
            $mediaDirectory = $this->filesystem
                ->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
            $target     = $mediaDirectory->getAbsolutePath('csv_upload/');
            $uploader   = $this->_fileUploaderFactory->create(['fileId' => 'file']);
            /** Allowed extension types */
            $uploader->setAllowedExtensions(['csv']);
            /** rename file name if already exists */
            $uploader->setAllowRenameFiles(true);
            $result     = $uploader->save($target);
        } catch (\Exception $e) {
            $log['messages']['csv']['fail'][] = 'Error uploading file: '.$e->getMessage().'';
        }

        if ($result['file']) {
            $target = $result['path'].$result['file'];
            $templateLink   = $this->_url->getUrl('orderupload/index/gettemplate', [
                '_current' => true,
                '_use_rewrite' => true]);
            $msgLink        = '<a href="'.sprintf($templateLink).'">'.__('Click here to download template.').'</a>';

            if (!$this->_file->isExists($target)) {
                $log['messages']['csv']['fail'][] = 'Invalid file upload attempt.';
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
            }

            $csvData    = $this->csv->getData($target);
            $headers    = array_map('strtolower', $csvData[0]);

            $columnsCount   = count($headers);

            foreach ($csvData as $csvRowIndex => $csvRowData) {
                $rowDataCount   = $this->helper->getArrElCount($csvRowData);
                if ($rowDataCount < $columnsCount) {
                    $log['messages']['csv']['fail'][] = 'Unable to read line '. ($csvRowIndex + 1) .'. Skipped. Please
                    check formatting is correct by comparing your import to the template file. '.$msgLink;
                    unset($csvData[$csvRowIndex]);
                }
            }

            $skuIndex   = array_search('sku', $headers);
            $qtyIndex   = array_search('qty', $headers);

            if ($skuIndex === false || $qtyIndex === false) {
                switch (true) {
                    case ($skuIndex === false && $qtyIndex === false):
                        $log['messages']['csv']['fail'][] = 'Unable to read file. Missing "sku", "qty" attributes.
                        Please check formatting is correct by comparing your import to the template file. ' . $msgLink;
                        break;
                    case ($skuIndex === false):
                        $log['messages']['csv']['fail'][] = 'Unable to read file. Missing "sku" attribute. Please check
                    formatting is correct by comparing your import to the template file. ' . $msgLink;
                        break;
                    case ($qtyIndex === false):
                        $log['messages']['csv']['fail'][] = 'Unable to read file. Missing "qty" attribute. Please check
                    formatting is correct by comparing your import to the template file. ' . $msgLink;
                        break;
                }

                return false;
            }

            $skuArr     = [];
            $qtyArr     = [];

            foreach ($csvData as $row => $data) {
                if ($row > 0) {
                    if ((int)($data[$qtyIndex]) > 0) {
                        $skuArr[] = $data[$skuIndex];
                        $qtyArr[] = (int)($data[$qtyIndex]);
                    } else {
                        continue;
                    }
                }
            }

            // validate nonexistent SKU's
            $skusArr    = $this->helper->getAllSkusArr();
            $unSkuItems = array_diff($skuArr, $skusArr);

            foreach ($skuArr as $skuItemIndex => $skuItemValue) {
                if (!in_array($skuItemValue, $skusArr)) {
                    unset($skuArr[$skuItemIndex]);
                    unset($qtyArr[$skuItemIndex]);
                }
            }

            if (!empty($unSkuItems)) {
                $log['messages']['product']['fail'][] = 'There are '.count($unSkuItems).
                    ' ('.implode(", ", $unSkuItems).') nonexistent SKUs in uploaded CSV file. They are ignored.';
            }

            // validate duplicated sku's
            $duplicatesResult   = $this->helper->getKeysForDuplicateValues($skuArr);
            if (!empty($duplicatesResult)) {
                $log['messages']['product']['fail'][] = 'There are '.count($duplicatesResult).'
                duplicated SKUs in uploaded CSV file. Duplicates are removed except very first entries.';
                // remove duplicates except first entries
                foreach ($duplicatesResult as $sku => $duplicates) {
                    foreach ($duplicates as $dupItem) {
                        unset($skuArr[$dupItem]);
                        unset($qtyArr[$dupItem]);
                    }
                }
            }

            // remove file from server
            $this->_file->deleteFile($target);

            $importResult = ['skuArr' => $skuArr, 'qtyArr' => $qtyArr];

            $log['messages']['csv']['ok'][] = 'Successfully read CSV file.';

            if (!empty($skuArr = $importResult['skuArr']) && !empty($qtyArr = $importResult['qtyArr'])) {
                $collectionToAdd    = $this->helper->getProductCollectionBySku($skuArr);
                $qtys = array_combine($skuArr, $qtyArr);
                if (!empty($collectionToAdd)) {
                    $formKey = $this->formKey->getFormKey();

                    foreach ($collectionToAdd as $product) {
                        $stockQty   = $this->stockItem->getStockQty(
                            $product->getId(),
                            $product->getStore()->getWebsiteId()
                        );
                        $qtyToAdd   = ($qtys[$product->getSku()] > $stockQty) ? $stockQty : $qtys[$product->getSku()];
                        if ($stockQty < $qtys[$product->getSku()]) {
                            $log['messages']['product']['fail'][] = 'There are fewer products in stock. Only '.
                                $qtyToAdd. ' pcs added.';
                        }
                        $params = [
                            'form_key'  => $formKey,
                            'product'   => $product->getId(),
                            'qty'       => $qtyToAdd
                        ];
                        try {
                            $this->cart->addProduct($product, $params);
                        } catch (\Exception $e) {
                            $log['messages']['product']['fail'][] = 'Product "' . $product->getName() .
                                '" failed to add to Cart with message: "' . $e->getMessage() . '"';
                        }

                        $log['messages']['product']['ok'][] = 'Successfully added "' . $product->getName() .
                            '" to basket.';
                    }

                    $this->cart->save();
                    $this->cart->getQuote()->setTotalsCollectedFlag(false)->collectTotals()->save();

                    $totalItems     = $this->cart->getQuote()->getItemsCount();
                    if ($totalItems) {
                        $log['cart_items_qty'] = $totalItems;
                    }
                }
            }
        }

        return $this->getResponse()->setBody(json_encode($log));
    }
}
