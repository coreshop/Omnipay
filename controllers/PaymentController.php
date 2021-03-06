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
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
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
        /** @var \Omnipay\Postfinance\Gateway $gateway */
        $gateway = $this->getModule()->getGateway();

        if(!$gateway->supportsPurchase()) {
            $message = 'OmniPay Gateway payment [' . $this->getModule()->getName() . '] does not support purchase';
            $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl($message);

            \Pimcore\Logger::error($message);
            $this->redirect($redirectUrl);
        }

        //create order
        $order = NULL;

        try {
            $order = $this->createOrder($this->view->language);

            try {
                $params = [
                    'newState'      => \CoreShop\Model\Order\State::STATE_PENDING_PAYMENT,
                    'newStatus'     => \CoreShop\Model\Order\State::STATUS_PENDING_PAYMENT,
                ];
                \CoreShop\Model\Order\State::changeOrderState($order, $params);

            } catch(\Exception $e) {
                $message = 'OmniPay Gateway payment [' . $this->getModule()->getName() . ']: Error on OrderChange: ' . $e->getMessage();
                \Pimcore\Logger::error($message);
                $this->redirect($this->getModule()->getErrorUrl($e->getMessage()));
            }

        } catch(\Exception $e ) {
            $message = 'OmniPay Gateway payment [' . $this->getModule()->getName() . ']: Error on Order creation. Message: ' . $e->getMessage();
            $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl($message);

            \Pimcore\Logger::error($message);
            $this->redirect($redirectUrl);
        }

        $params = $this->getGatewayParams($order);
        $request = $gateway->purchase($params);

        $data = $this->getGatewayRequestData($request);
        $response = $request->sendData($data);

        if($response instanceof \Omnipay\Common\Message\ResponseInterface) {
            try {
                if($response->isSuccessful()) {
                    \Pimcore\Logger::notice('OmniPay Gateway payment [' . $this->getModule()->getName() . ']: Gateway successfully responded redirect!');
                    $this->redirect($params['returnUrl']);
                } elseif($response->isRedirect()) {
                    if($response instanceof \Omnipay\Common\Message\RedirectResponseInterface) {
                        \Pimcore\Logger::notice('OmniPay Gateway payment [' . $this->getModule()->getName() . ']: response is a redirect. RedirectMethod: ' . $response->getRedirectMethod());
                        if ($response->getRedirectMethod() === 'GET')
                            $this->redirect($response->getRedirectUrl());
                        else {
                            $this->view->response = $response;
                            $this->_helper->viewRenderer('payment/post', null, true);
                        }
                    }
                } else {
                    $logMessage = 'OmniPay Gateway payment [' . $this->getModule()->getName() . '] Error: ' . $response->getMessage();
                    $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl($response->getMessage());

                    \Pimcore\Logger::error($logMessage);
                    $this->redirect($redirectUrl);
                }
            } catch(\Exception $e) {
                $logMessage = 'OmniPay Gateway payment [' . $this->getModule()->getName() . '] Error: ' . $e->getMessage();
                $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl($e->getMessage());

                \Pimcore\Logger::error($logMessage);
                $this->redirect($redirectUrl);
            }
        }
    }

    public function paymentReturnAbortAction()
    {
        //refill cart with last known cart added from current user
        $this->refillCart();

        $this->coreShopForward('canceled', 'checkout', 'CoreShop', []);
    }

    public function errorAction()
    {
        $this->coreShopForward('error', 'checkout', 'CoreShop', []);
    }

    public function confirmationAction()
    {
        $orderId = $this->getParam('order');

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
            $this->module = \CoreShop::getPaymentProvider('omnipay' . $this->gateway);
        }

        return $this->module;
    }

    /**
     * @param \Omnipay\Postfinance\Message\PurchaseRequest $request
     * @return array
     */
    public function getGatewayRequestData($request)
    {
        $data = $request->getData();

        $results = \Pimcore::getEventManager()->trigger(
            'coreshop.payment.omnipay.preGatewayRequestPayment',
            null,
            [ 'data' => $data ]
        );

        if ($results->count()) {
            $result = $results->last();
            if (is_array($result)) {
                $data = $result;
            }
        }

        return $data;
    }

    /**
     * @param \CoreShop\Model\Order $order
     *
     * Get all required Params for gateway.
     * extend this in your custom omnipay controller.
     *
     * @return array
     */
    public function getGatewayParams($order)
    {
        $cardParams = $this->getParam('card', []);

        $params = $this->getAllParams();
        $params['returnUrl'] = Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), 'payment-return');
        $params['cancelUrl'] = Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), 'payment-return-abort');

        // notifyUrl = declineUrl
        $params['notifyUrl'] = Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), 'payment-return-abort');

        $params['amount'] = $order->getTotal();
        $params['currency'] = $order->getCurrency()->getIsoCode();
        $params['transactionId'] = 'order_' . $order->getId();

        if(count($cardParams) > 0) {
            $params['card'] = new \Omnipay\Common\CreditCard($cardParams);
        }

        return $params;
    }
}