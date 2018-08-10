<?php

namespace Oporteo\Csvorderupload\Block;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Oporteo\Csvorderupload\Helper\Data $moduleHelper,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);

        $this->_moduleList    = $moduleList;
        $this->_layoutFactory = $layoutFactory;
        $this->_moduleHelper  = $moduleHelper;
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
