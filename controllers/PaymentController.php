<?php
/**
 * Omnipay
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

use Omnipay\Controller\Payment;
use CoreShop\Tool;

/**
 * Class Omnipay_PaymentController
 */
class Omnipay_PaymentController extends Payment
{
    public function paymentAction()
    {
        $gateway = $this->getModule()->getGateway();

        if(!$gateway->supportsPurchase()) {
            throw new \CoreShop\Exception("Gateway doesn't support purchase!");
        }

        $cardParams = $this->getParam("card", []);

        $params = $this->getAllParams();
        $params['returnUrl'] = Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "payment-return");
        $params['cancelUrl'] = Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "payment-return-abort");
        $params['amount'] = $this->cart->getTotal();
        $params['currency'] = Tool::getCurrency()->getIsoCode();
        $params['transactionId'] = uniqid();

        if(count($cardParams) > 0) {
            $params['card'] = new \Omnipay\Common\CreditCard($cardParams);
        }

        $response = $gateway->purchase($params)->send();

        if($response instanceof \Omnipay\Common\Message\ResponseInterface) {

            if($response->getTransactionReference()) {
                $this->cart->setCustomIdentifier($response->getTransactionReference());
            }
            else {
                $this->cart->setCustomIdentifier($params['transactionId']);
            }
            $this->cart->save();

            if($response->isSuccessful()) {
                $this->redirect($params['returnUrl']);
            }
            else if($response->isRedirect()) {
                if($response instanceof \Omnipay\Common\Message\RedirectResponseInterface) {
                    if ($response->getRedirectMethod() === "GET")
                        $this->redirect($response->getRedirectUrl());
                    else {
                        $this->view->response = $response;
                        $this->_helper->viewRenderer('payment/post', null, true);
                    }
                }
            }
            else {
                throw new \CoreShop\Exception("Sorry, your request failed");
            }
        }
    }

    public function paymentReturnAbortAction()
    {
        $this->redirect($this->view->url(array(), "coreshop_index"));
    }

    public function errorAction() {
        $this->coreShopForward("error", "checkout", "CoreShop", []);
    }

    public function confirmationAction()
    {
        $orderId = $this->getParam("order");

        if ($orderId) {
            $order = \CoreShop\Model\Order::getById($orderId);

            if ($order instanceof \CoreShop\Model\Order) {
                $this->session->order = $order;
            }
        }

        parent::confirmationAction();
    }

    /**
     * @return Omnipay\Shop\Provider
     */
    public function getModule()
    {
        if (is_null($this->module)) {
            $this->module = \CoreShop\Plugin::getPaymentProvider("omnipay" . $this->gateway);
        }

        return $this->module;
    }
}
