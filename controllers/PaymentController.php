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

/**
 * Class Omnipay_PaymentController
 */
class Omnipay_PaymentController extends Payment
{
    public function paymentAction()
    {
        $gateway = $this->getModule()->getGateway();

        if(!$gateway->supportsPurchase()) {

            \Pimcore\Logger::error("OmniPay Gateway payment [" . $this->getModule()->getName() . "] does not support purchase");
            throw new \CoreShop\Exception("Gateway doesn't support purchase!");
        }

        $params = $this->getGatewayParams();

        $response = $gateway->purchase($params)->send();

        if($response instanceof \Omnipay\Common\Message\ResponseInterface) {

            if($response->getTransactionReference()) {
                $this->cart->setCustomIdentifier($response->getTransactionReference());
            } else {
                $this->cart->setCustomIdentifier($params['transactionId']);
            }

            $this->cart->save();

            try {

                if($response->isSuccessful()) {
                    \Pimcore\Logger::notice("OmniPay Gateway payment [" . $this->getModule()->getName() . "]: Gateway successfully responded redirect!");
                    $this->redirect($params['returnUrl']);
                } else if($response->isRedirect()) {
                    if($response instanceof \Omnipay\Common\Message\RedirectResponseInterface) {
                        \Pimcore\Logger::notice("OmniPay Gateway payment [" . $this->getModule()->getName() . "]: response is a redirect. RedirectMethod: " . $response->getRedirectMethod() );
                        if ($response->getRedirectMethod() === "GET")
                            $this->redirect($response->getRedirectUrl());
                        else {
                            $this->view->response = $response;
                            $this->_helper->viewRenderer('payment/post', null, true);
                        }
                    }
                } else {
                    throw new \CoreShop\Exception($response->getMessage());
                }

            } catch(\Exception $e) {
                \Pimcore\Logger::error("OmniPay Gateway payment [" . $this->getModule()->getName() . "] Error: " . $e->getMessage());
            }

        }
    }

    public function paymentReturnAbortAction()
    {
        $this->coreShopForward("canceled", "checkout", "CoreShop", []);
    }

    public function errorAction()
    {
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
            $this->module = \CoreShop::getPaymentProvider("omnipay" . $this->gateway);
        }

        return $this->module;
    }

    /**
     * Get all required Params for gateway.
     * extend this in your custom omnipay controller.
     *
     * @return array
     */
    public function getGatewayParams()
    {
        $cardParams = $this->getParam("card", []);

        $params = $this->getAllParams();
        $params['returnUrl'] = Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "payment-return");
        $params['cancelUrl'] = Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "payment-return-abort");
        $params['amount'] = $this->cart->getTotal();
        $params['currency'] = \Coreshop::getTools()->getCurrency()->getIsoCode();
        $params['transactionId'] = uniqid();

        if(count($cardParams) > 0) {
            $params['card'] = new \Omnipay\Common\CreditCard($cardParams);
        }

        return $params;
    }
}
