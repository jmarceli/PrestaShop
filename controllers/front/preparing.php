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
 * Controller for handling preparing form for Dotpay
 */
class dotpaypreparingModuleFrontController extends DotpayController {
    public function initContent() {
        parent::initContent();
        $this->display_column_left = false;
        $this->display_header = false;
        $this->display_footer = false;
        $cartId = 0;
        
        if(Tools::getValue('order_id') == false) {
            $cartId = $this->context->cart->id;
            $exAmount = $this->api->getExtrachargeAmount(true);
            if($exAmount > 0 && !$this->isExVPinCart()) {
                $productId = $this->config->getDotpayExchVPid();
                if($productId != 0) {
                    $product = new Product($productId, true);
                    $product->price = $exAmount;
                    $product->save();
                    $product->flushPriceCache();

                    $this->context->cart->updateQty(1, $product->id);
                    $this->context->cart->update();
                    $this->context->cart->getPackageList(true);
                }
            }
            
            $discAmount = $this->api->getDiscountAmount();
            if($discAmount > 0) {
                $discount = new CartRule($this->config->getDotpayDiscountId());
                $discount->reduction_amount = $this->api->getDiscountAmount();
                $discount->reduction_currency = $this->context->cart->id_currency;
                $discount->reduction_tax = 1;
                $discount->update();
                $this->context->cart->addCartRule($discount->id);
                $this->context->cart->update();
                $this->context->cart->getPackageList(true);
            }
            
            $result = $this->module->validateOrder(
                $this->context->cart->id,
                (int)$this->config->getDotpayNewStatusId(),
                $this->getDotAmount(),
                $this->module->displayName,
                NULL,
                array(),
                NULL,
                false,
                $this->customer->secure_key
            );
        } else {
            $this->context->cart = Cart::getCartByOrderId(Tools::getValue('order_id'));
            $this->initPersonalData();
            $cartId = $this->context->cart->id;
        }
        
        $this->api->onPrepareAction(Tools::getValue('dotpay_type'), array(
            'order' => Order::getOrderByCartId($cartId),
            'customer' => $this->context->customer->id
        ));
        
        if($this->config->isApiConfigOk() && 
           $this->api->isChannelInGroup(Tools::getValue('channel'), array(DotpayApi::cashGroup, DotpayApi::transfersGroup))
        ) {
            $this->context->cookie->dotpay_channel = Tools::getValue('channel');
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'confirm', array('order_id'=>Order::getOrderByCartId($cartId))));
            die();
        }
        
        $this->context->smarty->assign(array(
            'hiddenForm' => $this->api->getHiddenForm()
        ));
        $cookie = new Cookie('lastOrder');
        $cookie->orderId = Order::getOrderByCartId($cartId);
        $cookie->write();
        $this->setTemplate("preparing.tpl");
    }
}