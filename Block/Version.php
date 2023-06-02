<?php

namespace Oporteo\Csvorderupload\Block;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\LayoutFactory;
use Oporteo\Csvorderupload\Helper\Data;

class Version extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        private ModuleListInterface $moduleList,
        private LayoutFactory $layoutFactory,
        private Data $moduleHelper,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    public function render(AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $module = $this->_moduleList->getOne('Oporteo_Csvorderupload');

        $html .= $element->addField($module['name'], 'label', [
            'name'  => 'dummy',
            'label' => 'Version',
            'value' => $module['setup_version'],
        ])->setRenderer($this->_getFieldRenderer())
            ->toHtml();

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    private function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $layout = $this->_layoutFactory->create();

            $this->_fieldRenderer = $layout->createBlock(
                \Magento\Config\Block\System\Config\Form\Field::class
            );
        }

        return $this->_fieldRenderer;
    }
}
