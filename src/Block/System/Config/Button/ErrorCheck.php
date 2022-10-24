<?php
/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */
declare(strict_types=1);

namespace Signalise\Plugin\Block\System\Config\Button;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ErrorCheck extends Field
{
    protected $_template = 'Signalise_Plugin::system/config/button/error.phtml';

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    public function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    public function getErrorCheckUrl(): string
    {
        return $this->getUrl('signalise_api_settings/log/error');
    }

    public function getButtonHtml()
    {
        try {
            $button = $this->getLayout()->createBlock(
                Button::class
            )->setData([
                'id' => 'signalise-button_error',
                'label' => __('Check logs'),
            ]);
            return $button->toHtml();
        } catch (Exception $e) {
            return false;
        }
    }
}
