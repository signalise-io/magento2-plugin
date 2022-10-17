<?php
/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */
declare(strict_types=1);

namespace Signalise\Plugin\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Heading extends Field
{
    public function render(AbstractElement $element): string
    {
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label"></td>';
        $html .= '  <td class="value">';
        $html .= '     <div class="signalise-heading-block">' . $element->getData('label') . '</div>';
        $html .= '     <div class="signalise-heading-comment">' . $element->getData('comment') . '</div>';
        $html .= '  </td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
