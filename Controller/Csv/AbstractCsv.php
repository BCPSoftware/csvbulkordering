<?php

namespace Oporteo\Csvorderupload\Controller\Csv;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractCsv
 */
abstract class AbstractCsv extends Action
{
    /**
     * @var string
     */
    private const CONTENT_TYPE = 'application/vnd.ms-excel';

    /**
     * @var string
     */
    private const TYPE = 'filename';

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Csv
     */
    private $csvWriter;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var string
     */
    private $outputDirectory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AbstractCsv constructor.
     *
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param FileFactory $fileFactory
     * @param Csv $csvWriter
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        FileFactory $fileFactory,
        Csv $csvWriter,
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->csvWriter = $csvWriter;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->outputDirectory = DirectoryList::MEDIA;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        try {
            $outputFile = $this->getOutputFile();
            $filePath = $this->getFilePath($outputFile);

            return $this->writer($filePath, $this->getContent())
                ->createFile($outputFile)
                ->getResultRaw();
        } catch (Exception $exception) {
            $this->logger->critical($exception);
            $this->messageManager->addErrorMessage(__('An error has occurred'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererUrl();
    }

    /**
     * Get content
     *
     * @return array
     */
    abstract protected function getContent(): array;

    /**
     * Get output file
     *
     * @return string
     */
    abstract protected function getOutputFile(): string;

    /**
     * Get file path
     *
     * @param string $outputFile
     *
     * @return string
     *
     * @throws FileSystemException
     */
    protected function getFilePath(string $outputFile): string
    {
        return $this->directoryList->getPath($this->outputDirectory) . "/" . $outputFile;
    }

    /**
     * Create file
     *
     * @param string $outputFile
     *
     * @return $this
     *
     * @throws Exception
     */
    protected function createFile(string $outputFile): AbstractCsv
    {

        $this->fileFactory->create(
            $outputFile,
            [
                'type' => self::TYPE,
                'value' => $outputFile,
                'rm' => true,
            ],
            $this->outputDirectory,
            self::CONTENT_TYPE
        );

        return $this;
    }

    /**
     * Writer csv file
     *
     * @param string $filePath
     * @param array $csvData
     *
     * @return $this
     *
     * @throws FileSystemException
     */
    protected function writer(string $filePath, array $csvData): AbstractCsv
    {
        $this->csvWriter
            ->setEnclosure('"')
            ->setDelimiter(',')
            ->appendData($filePath, $csvData);

        return $this;
    }

    /**
     * Get result raw
     *
     * @return ResultInterface
     */
    protected function getResultRaw(): ResultInterface
    {
        return $this->resultRawFactory->create();
    }
}
