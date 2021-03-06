<?php

/**
*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

require_once(__DIR__.'/dotpay.php');

/**
 * Controller for handling return address
 */
class dotpaybackModuleFrontController extends DotpayController {
    /**
     * Removes cokie with last order data
     */
    public function removeCookieWithOrderId() {
        $cookie = new Cookie('lastOrder');
        $cookie->logout();
    }
    /**
     * Proces coming back from a Dotpay server
     */
    public function initContent() {
        $this->display_column_left = false;
        parent::initContent();
        $message = NULL;
        
        if((bool)Context::getContext()->customer->is_guest) $url=$this->context->link->getPageLink('guest-tracking', true);
        else $url=$this->context->link->getPageLink('history', true);
        $cookie = new Cookie('lastOrder');
        $order = new Order($cookie->orderId);
        if(Tools::getValue('error_code') !== false) {
            $this->removeCookieWithOrderId();
            switch(Tools::getValue('error_code'))
            {
                case 'PAYMENT_EXPIRED':
                    $message = $this->module->l('Exceeded expiration date of the generated payment link.');
                    break;
                case 'UNKNOWN_CHANNEL':
                    $message = $this->module->l('Selected payment channel is unknown.');
                    break;
                case 'DISABLED_CHANNEL':
                    $message = $this->module->l('Selected channel payment is desabled.');
                    break;
                case 'BLOCKED_ACCOUNT':
                    $message = $this->module->l('Account is disabled.');
                    break;
                case 'INACTIVE_SELLER':
                    $message = $this->module->l('Seller account is inactive.');
                    break;
                case 'AMOUNT_TOO_LOW':
                    $message = $this->module->l('Amount is too low.');
                    break;
                case 'AMOUNT_TOO_HIGH':
                    $message = $this->module->l('Amount is too high.');
                    break;
                case 'BAD_DATA_FORMAT':
                    $message = $this->module->l('Data format is bad.');
                    break;
                case 'HASH_NOT_EQUAL_CHK':
                    $message = $this->module->l('Request has been modified during transmission.');
                    break;
                default:
                    $message = $this->module->l('There was an unidentified error. Please contact to your seller and give him the order number.');
            }
        }
        
        $this->context->smarty->assign(array(
            'message' => $message,
            'redirectUrl' => $url,
            'orderReference' => $order->reference,
            'checkStatusUrl' => $this->context->link->getModuleLink($this->module->name, 'status', array()),
            'waitingMessage' => $this->module->l('Waiting for confirm your payment...').'<br>'.$this->module->l('It make take up to 2 minutes.'),
            'successMessage' => $this->module->l('Thank you! The process of payment completed correctly. In a moment you will be able to check the status of your order.'),
            'errorMessage' => $this->module->l('Payment was rejected.'),
            'timeoutMessage' => $this->module->l('Time intended for waiting for payment confirmation has elapsed. When transaction will be confirmed we will notify you on email. If payment will not be confirmed, please contact with shop owner and give him the order number:').' '.$order->reference,
        ));
        $this->setTemplate("back.tpl");
    }
}
