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

require_once(__DIR__.'/Curl.php');

/**
 * Provides the functionality of seller API
 */
class DotpaySellerApi {
    private $_baseurl;
    private $_test;
    
    public function __construct($url) {
        $this->_baseurl = $url;
    }
    
    /**
     * Returns infos about credit card
     * @param string $username
     * @param string $password
     * @param string $number
     * @return \stdClass
     */
    public function getCreditCardInfo($username, $password, $number) {
        $payment = $this->getPaymentByNumber($username, $password, $number);
        if($payment->payment_method->channel_id!=248)
            return NULL;
        return $payment->payment_method->credit_card;
    }
    
    /**
     * Checks, if username and password are right
     * @param string $username Username of user
     * @param string $password Password of user
     * @param string $version Version of used Dotpay Api
     * @return boolean|null
     */
    public function isAccountRight($username, $password, $version) {
        if($version == 'legacy')
            return NULL;
        if(empty($username) && empty($password))
            return NULL;
        $url = $this->_baseurl.$this->getDotPaymentApi()."payments/";
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $curl->exec();
        $info = $curl->getInfo();
        return ($info['http_code']>=200 && $info['http_code']<300);
    }
    
    /**
     * Returns ifnos about payment
     * @param string $username
     * @param string $password
     * @param string $number
     * @return \stdClass
     */
    public function getPaymentByNumber($username, $password, $number) {
        $url = $this->_baseurl.$this->getDotPaymentApi()."payments/$number/";
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $response = json_decode($curl->exec());
        return $response;
    }
    
    /**
     * Returns ifnos about payment
     * @param string $username
     * @param string $password
     * @param int $orderId
     * @return \stdClass
     */
    public function getPaymentByOrderId($username, $password, $orderId) {
        $url = $this->_baseurl.$this->getDotPaymentApi().'payments/?control='.$orderId;
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $response = json_decode($curl->exec());
        return $response->results;
    }
    
    /**
     * Makes a return payment and returns infos about a result of this operation
     * @param string $username
     * @param string $password
     * @param string $payment
     * @param float $amount
     * @param type $control
     * @param type $description
     * @return type
     */
    public function makeReturnMoney($username, $password, $payment, $amount, $control, $description) {
        $url = $this->_baseurl.$this->getDotPaymentApi().'payments/'.$payment.'/refund/';
        $data = array(
            'amount' => str_replace(',', '.', $amount),
            'description' => $description,
            'control' => $control
        );
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password)
             ->addOption(CURLOPT_POST, 1)
             ->addOption(CURLOPT_POSTFIELDS, json_encode($data))
             ->addOption(CURLOPT_SSL_VERIFYPEER, TRUE)
             ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
             ->addOption(CURLOPT_RETURNTRANSFER, 1)
             ->addOption(CURLOPT_TIMEOUT, 100)
             ->addOption(CURLOPT_HTTPHEADER, array(
                'Accept: application/json; indent=4',
                'content-type: application/json'));
        $resp = json_decode($curl->exec(), true);
        return $curl->getInfo()+$resp;
    }
    
    /**
     * Returns path for payment API
     * @return string
     */
    private function getDotPaymentApi() {
        return "api/";
    }

    /**
     * Sets option for cUrl and return cUrl resource
     * @param resource $curl
     */
    private function setCurlOption($curl) {
        $curl->addOption(CURLOPT_SSL_VERIFYPEER, TRUE)
             ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
             ->addOption(CURLOPT_RETURNTRANSFER, 1)
             ->addOption(CURLOPT_TIMEOUT, 100)
             ->addOption(CURLOPT_CUSTOMREQUEST, "GET");
    }
}
