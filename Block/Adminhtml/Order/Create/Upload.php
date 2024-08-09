<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Block\Adminhtml\Order\Create;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate;
use Magento\Sales\Block\Adminhtml\Order\Create\Items;

class Upload extends AbstractCreate
{
    protected $_template = 'Oporteo_Csvorderupload::order/create/upload.phtml';

    /**
     * @return self
     * @throws LocalizedException
     */
    protected function _prepareLayout(): self
    {
        /** @var Items $items */
        $items = $this->getLayout()->getBlock(name: 'items');

        if (!$items) {
            return parent::_prepareLayout();
        }

        $items->addButton([
            'label' => __('Upload'),
            'onclick' => 'order.uploadFile()',
        ]);

        return parent::_prepareLayout();
    }
}
