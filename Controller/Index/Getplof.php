<?php


namespace Oporteo\Csvorderupload\Controller\Index;

use Magento\Framework\App\Action\Context;

class Getplof extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\File\Csv $csvWriter,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Oporteo\Csvorderupload\Helper\Data $helper
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->csvWriter = $csvWriter;
        $this->directoryList = $directoryList;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockItem = $stockItem;
        $this->priceHelper = $priceHelper;
        $this->helper   = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $productCollection  = $this->helper->getProductCollection();

        $csvData = [
            [
                'Sku',
                'Name',
                'Price',
                'Qty'
            ]
        ];

        foreach ($productCollection as $product) {
            $row = [
                $product->getSku(),
                $product->getName(),
                ($this->priceHelper->currency($product->getPrice(), true, false)),
                ''
            ];
            array_push($csvData, $row);
        }

        $outputFile = "Price_List_". date('Ymd_His').".csv";
        $outputDirectory = \Magento\Framework\App\Filesystem\DirectoryList::MEDIA;
        $filePath =  $this->directoryList->getPath($outputDirectory) . "/" . $outputFile;
        $this->csvWriter
            ->setEnclosure('"')
            ->setDelimiter(',')
            ->saveData($filePath, $csvData);

        $this->fileFactory->create(
            $outputFile,
            [
                'type'  => "filename",
                'value' => $outputFile,
                'rm'    => true,
            ],
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA,
            'application/vnd.ms-excel',
            null
        );

        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw;
    }
}
