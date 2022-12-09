<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Controller\Index;

use Exception;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\SerializerInterface;
use Oporteo\Csvorderupload\Api\GetStockProductQtysInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Oporteo\Csvorderupload\Helper\Data as DataHelper;

/**
 * Class Fileupload
 */
class Fileupload extends Action implements HttpPostActionInterface
{
    /**
     * @var Csv
     */
    private $csv;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $file;

    /**
     * @var UploaderFactory
     */
    private $fileUploaderFactory;

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var QuoteResourceModel
     */
    private $quoteResourceModel;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var GetStockProductQtysInterface
     */
    private $stockProductQtys;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * Fileupload constructor.
     *
     * @param Context $context
     * @param Filesystem $filesystem
     * @param File $file
     * @param UploaderFactory $fileUploaderFactory
     * @param Csv $csv
     * @param Session $session
     * @param GetStockProductQtysInterface $stockProductQtys
     * @param DataHelper $dataHelper
     * @param QuoteResourceModel $quoteResourceModel
     * @param SerializerInterface $serializer
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        File $file,
        UploaderFactory $fileUploaderFactory,
        Csv $csv,
        Session $session,
        GetStockProductQtysInterface $stockProductQtys,
        DataHelper $dataHelper,
        QuoteResourceModel $quoteResourceModel,
        SerializerInterface $serializer,
        Cart $cart
    ) {
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->csv = $csv;
        $this->session = $session;
        $this->dataHelper = $dataHelper;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->serializer = $serializer;
        $this->stockProductQtys = $stockProductQtys;
        $this->cart = $cart;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $log['messages']['product'] = ['qty' => 0];

        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $target = $mediaDirectory->getAbsolutePath('csv_upload/');
            $uploader = $this->fileUploaderFactory->create(['fileId' => 'file']);
            $uploader->setAllowedExtensions(['csv']);
            $uploader->setAllowRenameFiles(true);
            $result = $uploader->save($target);
        } catch (Exception $e) {
            $log['messages']['csv']['fail'][] = 'Error uploading file: ' . $e->getMessage();

            return $this->getResponse()->representJson($this->serializer->serialize($log));
        }

        if ($result['file']) {
            $target = $result['path'].$result['file'];
            $templateLink   = $this->_url->getUrl(
                'orderupload/csv/template',
                [
                    '_current' => true,
                    '_use_rewrite' => true
                ]
            );
            $msgLink = '<a href="'. sprintf($templateLink) . '">' . __('Click here to download template.') . '</a>';

            if (!$this->file->isExists($target)) {
                $log['messages']['csv']['fail'][] = 'Invalid file upload attempt.';

                return $this->getResponse()->representJson($this->serializer->serialize($log));
            }

            $csvData = $this->csv->getData($target);
            $headers = array_map(
                function ($item) {
                    return preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', strtolower($item));
                },
                $csvData[0]
            );
            $columnsCount = count($headers);

            foreach ($csvData as $csvRowIndex => $csvRowData) {
                $rowDataCount = $this->dataHelper->getArrElCount($csvRowData);

                if ($rowDataCount < $columnsCount) {
                    $log['messages']['csv']['fail'][] = 'Unable to read line '. ($csvRowIndex + 1) .'. Skipped. Please
                    check formatting is correct by comparing your import to the template file. ' . $msgLink;
                    unset($csvData[$csvRowIndex]);
                }
            }

            $skuIndex = array_search('sku', $headers);
            $qtyIndex = array_search('qty', $headers);

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

                return $this->getResponse()->representJson($this->serializer->serialize($log));
            }

            $skuArr = [];
            $qtyArr = [];

            foreach ($csvData as $row => $data) {
                if ($row > 0) {
                    if ((int)$data[$qtyIndex] > 0) {
                        $skuArr[] = $data[$skuIndex];
                        $qtyArr[] = (int)$data[$qtyIndex];
                    } else {
                        continue;
                    }
                }
            }

            $skusArr = $this->dataHelper->getAllSkusArr();
            $unSkuItems = array_diff($skuArr, $skusArr);

            foreach ($skuArr as $skuItemIndex => $skuItemValue) {
                if (!in_array($skuItemValue, $skusArr)) {
                    unset($skuArr[$skuItemIndex]);
                    unset($qtyArr[$skuItemIndex]);
                }
            }

            if (!empty($unSkuItems)) {
                $log['messages']['product']['fail'][] = 'The following SKU\'s have not been imported from the CSV File '
                    . count($unSkuItems) . ' (' . implode(", ", $unSkuItems) . ').';
                $log['messages']['product']['qty'] += count($unSkuItems);
            }

            $duplicatesResult = $this->dataHelper->getKeysForDuplicateValues($skuArr);

            if (!empty($duplicatesResult)) {
                $log['messages']['product']['fail'][] = 'There are ' . count($duplicatesResult) . '
                duplicated SKUs in uploaded CSV file. Duplicates are removed except very first entries.';
                $log['messages']['product']['qty'] += count($duplicatesResult);

                foreach ($duplicatesResult as $sku => $duplicates) {
                    foreach ($duplicates as $dupItem) {
                        unset($skuArr[$dupItem]);
                        unset($qtyArr[$dupItem]);
                    }
                }
            }

            $this->file->deleteFile($target);
            $importResult = ['skuArr' => $skuArr, 'qtyArr' => $qtyArr];

            if (!empty($skuArr = $importResult['skuArr']) && !empty($qtyArr = $importResult['qtyArr'])) {
                $collectionToAdd = $this->dataHelper->getProductCollectionBySku($skuArr);
                $qtys = array_combine($skuArr, $qtyArr);
                $quote = $this->session->getQuote();
                $stockQtys = $this->stockProductQtys->execute($skuArr);

                if (!empty($collectionToAdd)) {
                    $quote = $this->cart->getQuote();
                    $quote->setTotalsCollectedFlag(true);

                    foreach ($collectionToAdd as $product) {
                        $qtyToAdd = ($qtys[$product->getSku()] > $stockQtys[$product->getSku()])
                            ? (float)$stockQtys[$product->getSku()]
                            : (float)$qtys[$product->getSku()];

                        if ($stockQtys[$product->getSku()] < $qtys[$product->getSku()]) {
                            $log['messages']['product']['fail'][] = sprintf(
                                'SKU %s has insufficient stock. Only %s were added.',
                                $product->getSku(),
                                $qtyToAdd
                            );
                            $log['messages']['product']['qty'] ++;
                        }

                        if ($qtyToAdd === 0.0) {
                            continue;
                        }

                        try {
                            $this->cart->addProduct($product, $qtyToAdd);
                        } catch (Exception $e) {
                            $log['messages']['product']['fail'][] = 'Product "' . $product->getName() .
                                '" failed to add to Cart with message: "' . $e->getMessage() . '"';
                            $log['messages']['product']['qty'] ++;

                            continue;
                        }
                        $this->cart->save();

                        $log['messages']['product']['ok'][] = 'Successfully added "' . $product->getName() .
                            '" to basket.';
                    }

                    $quote->setTotalsCollectedFlag(false);
                    $this->cart->save();
                    $totalItems = $quote->getItemsCount();

                    if ($totalItems) {
                        $log['cart_items_qty'] = $totalItems;
                    }
                }
            }
        }

        return $this->getResponse()->representJson($this->serializer->serialize($log));
    }
}
