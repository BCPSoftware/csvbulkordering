<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Block\Adminhtml\System;

use Composer\InstalledVersions;
use Magento\Backend\Block\Template;

class Version extends Template
{
    protected $_template = 'Oporteo_OrderUpload::system/version.phtml';

    public function getVersion(): string
    {
        return InstalledVersions::getPrettyVersion($this->getData(key: 'packageName'));
    }

    public function getName(): string
    {
        return $this->getData(key: 'packageLabel');
    }

    protected function _toHtml(): string
    {
        if (!$this->getData(key: 'packageName') || !$this->getData(key: 'packageLabel')) {
            return '';
        }

        return parent::_toHtml();
    }

    protected function getCacheLifetime()
    {
        return 3600 * 24 * 10;
    }
}
