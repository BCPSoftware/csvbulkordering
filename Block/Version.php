<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Block;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\LayoutFactory;

class Version extends Fieldset
{
    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param ModuleListInterface $moduleList
     * @param LayoutFactory $layoutFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        private ModuleListInterface $moduleList,
        private LayoutFactory $layoutFactory,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $html = $this->_getHeaderHtml($element);

        $module = $this->moduleList->getOne('Oporteo_Csvorderupload');

        $html .= $element->addField($module['name'], 'label', [
            'name'  => 'dummy',
            'label' => 'Version',
            'value' => $module['setup_version'],
        ])->setRenderer($this->getFieldRenderer())->toHtml();

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * @return RendererInterface
     */
    private function getFieldRenderer(): RendererInterface
    {
        if (empty($this->fieldRenderer)) {
            $layout = $this->layoutFactory->create();

            $this->fieldRenderer = $layout->createBlock(Field::class);
        }

        return $this->fieldRenderer;
    }
}
