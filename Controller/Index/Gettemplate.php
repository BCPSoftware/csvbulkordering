<?php


namespace Oporteo\Csvorderupload\Controller\Index;

use Magento\Framework\App\Action\Context;

class Gettemplate extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\File\Csv $csvWriter,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->csvWriter = $csvWriter;
        $this->directoryList = $directoryList;
        parent::__construct($context);
    }

    public function execute()
    {
        $csvData = [
            [
                'Sku',
                'Qty'
            ]
        ];
        $outputFile = "Template_". date('Ymd_His').".csv";
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
