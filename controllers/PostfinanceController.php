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

require 'PaymentController.php';

use Omnipay\Model\Postfinance\TransactionInfo;
use Omnipay\Postfinance\Message\Helper;
use CoreShop\Model\Order\State;

/**
 * Class Omnipay_PostfinanceController
 */
class Omnipay_PostfinanceController extends Omnipay_PaymentController
{
    /**
     * This Action listens to server2server communication
     */
    public function paymentReturnServerAction()
    {
        $requestData = $this->parseRequestData();

        $this->disableLayout();
        $this->disableViewAutoRender();

        \Pimcore\Logger::log('OmniPay paymentReturnServer [Postfinance]. TransactionID: ' . $requestData['transaction'] . ', Status: ' . $requestData['status']);

        if (!empty($requestData['transaction'])) {
            $order = \CoreShop\Model\Order::getById($requestData['orderId']);

            if ($order instanceof \CoreShop\Model\Order) {

                //get transaction
                $this->getOrderPayment(
                    $order,
                    $requestData['payId']
                )->addTransactionNote($requestData['payId'], TransactionInfo::getStatusTranslation($requestData['status'], true));

                //set state
                $state = $this->getState($requestData['status']);
                \Pimcore\Logger::notice('OmniPay paymentReturnServer [Postfinance]. Change order (' . $order->getId() . ') state to: ' . $state['state']);

                $params = [
                    'newState'      => $state['state'],
                    'newStatus'     => $state['status'],
                ];

                try {
                    \CoreShop\Model\Order\State::changeOrderState($order, $params);
                } catch(\Exception $e) {
                    \Pimcore\Logger::notice('OmniPay paymentReturnServer [Postfinance]. changeOrderState Error: ' . $e->getMessage());
                }

                //@fixme!
                /*
                $payments = $order->getPayments();
                foreach ($payments as $p) {
                    $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentOmnipay($p);
                    $dataBrick->setTransactionId($requestData['transaction']);
                    $p->save();
                }
                */

            } else {
                \Pimcore\Logger::notice('OmniPay paymentReturnServer [Postfinance]. Order with identifier ' . $requestData['transaction'] . ' not found');
            }
        } else {

            \Pimcore\Logger::notice('OmniPay paymentReturnServer [Postfinance]. No valid transaction id given');
        }

        exit;
    }

    /**
     * This Action can be called via Frontend
     * @throws \CoreShop\Exception
     * @throws \CoreShop\Exception\ObjectUnsupportedException
     */
    public function paymentReturnAction()
    {
        $requestData = $this->parseRequestData();

        $this->disableLayout();
        $this->disableViewAutoRender();

        \Pimcore\Logger::notice('OmniPay paymentReturn [Postfinance]. TransactionID: ' . $requestData['transaction'] . ', Status: ' . $requestData['status']);

        if (!empty($requestData['transaction'])) {
            $order = \CoreShop\Model\Order::getById($requestData['orderId']);

            if ($order instanceof \CoreShop\Model\Order) {

                //get transaction
                $this->getOrderPayment(
                    $order,
                    $requestData['payId']
                )->addTransactionNote($requestData['payId'], TransactionInfo::getStatusTranslation($requestData['status'], true));

                $state = $this->getState($requestData['status']);
                \Pimcore\Logger::notice('OmniPay paymentReturnServer [Postfinance]. Change order state to: ' . $state['state']);

                $params = [
                    'newState'      => $state['state'],
                    'newStatus'     => $state['status'],
                ];

                try {
                    \CoreShop\Model\Order\State::changeOrderState($order, $params);
                    $this->redirect($this->getModule()->getConfirmationUrl($order));
                } catch(\Exception $e) {
                    $this->redirect($this->getModule()->getErrorUrl($e->getMessage()));
                }

                //@fixme!
                /*
                $payments = $order->getPayments();
                foreach ($payments as $p) {
                    $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentOmnipay($p);
                    $dataBrick->setTransactionId($requestData['transaction']);
                    $p->save();
                }
                */

                $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getConfirmationUrl($order);

            } else {
                \Pimcore\Logger::notice('OmniPay paymentReturn [Postfinance]: Order with identifier ' . $requestData['transaction'] . ' not found');
                $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl('order with identifier ' . $requestData['transaction'] . ' not found');
            }
        } else {
            \Pimcore\Logger::notice('OmniPay paymentReturn [Postfinance]: No valid transaction id given');
            $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl('no valid transaction id given');
        }

        $this->redirect($redirectUrl);
        exit;
    }

    public function paymentReturnAbortAction()
    {
        $requestData = $this->parseRequestData();

        if (!empty($requestData['transaction'])) {
            $state = $this->getState($requestData['status']);
            \Pimcore\Logger::notice('OmniPay paymentReturnAbortAction [Postfinance]. Change order state to: ' . $state['state']);

            $order = \CoreShop\Model\Order::getById($requestData['orderId']);

            if ($order instanceof \CoreShop\Model\Order) {

                //get transaction
                $this->getOrderPayment(
                    $order,
                    $requestData['payId']
                )->addTransactionNote($requestData['payId'], TransactionInfo::getStatusTranslation($requestData['status'], true));

                $params = [
                    'newState'      => $state['state'],
                    'newStatus'     => $state['status']
                ];

                try {
                    \CoreShop\Model\Order\State::changeOrderState($order, $params);
                    $this->redirect($this->getModule()->getConfirmationUrl($order));
                } catch(\Exception $e) {
                    $this->redirect($this->getModule()->getErrorUrl($e->getMessage()));
                }
            }
        }

        $this->coreShopForward('canceled', 'checkout', 'CoreShop', []);
    }

    public function errorAction()
    {
        $requestData = $this->parseRequestData();

        if (!empty($requestData['transaction'])) {
            $state = $this->getState($requestData['status']);
            \Pimcore\Logger::notice('OmniPay errorAction [Postfinance]. Change order state to: ' . $state['state']);

            $order = \CoreShop\Model\Order::getById($requestData['orderId']);

            if ($order instanceof \CoreShop\Model\Order) {

                //get transaction
                $this->getOrderPayment(
                    $order,
                    $requestData['payId']
                )->addTransactionNote($requestData['payId'], TransactionInfo::getStatusTranslation($requestData['status'], true));

                $params = [
                    'newState'      => $state['state'],
                    'newStatus'     => $state['status']
                ];

                try {
                    \CoreShop\Model\Order\State::changeOrderState($order, $params);
                    $this->redirect($this->getModule()->getConfirmationUrl($order));
                } catch(\Exception $e) {
                    $this->redirect($this->getModule()->getErrorUrl($e->getMessage()));
                }
            }
        }

        $this->coreShopForward('error', 'checkout', 'CoreShop', []);
    }

    /**
     * @param \CoreShop\Model\Order $order
     *
     * @return array
     */
    public function getGatewayParams($order)
    {
        $params = parent::getGatewayParams($order);

        $language = $this->language;
        $gatewayLanguage = 'en_EN';

        if(!empty($language)) {
            $gatewayLanguage = $language . '_' . strtoupper($language);
        }

        $params['language'] = $gatewayLanguage;

        return $params;
    }

    private function parseRequestData()
    {
        /**
         * @var $transaction
         * CoreShop transaction ID
         *
         */
        $transaction = $_REQUEST['orderID'];

        /**
         * CoreShop Order Id
         */
        $orderId = str_replace('order_', '', $transaction);

        /**
         * @var $status
         *
         * @see https://e-payment-postfinance.v-psp.com/en/guides/user%20guides/statuses-and-errors/statuses
         *
         * 0 => incomplete / not valid
         * 1 => canceled by user
         * 2 => canceled by financial institution
         * 5 => approved
         * 9 => payment requested
         *
         */
        $status = (int) $_REQUEST['STATUS'];

        /**
         * @var $payId
         */
        $payId = $_REQUEST['PAYID'];

        /**
         * @var $payIdSub
         */
        $payIdSub = $_REQUEST['PAYIDSUB'];

        /**
         * @var $ncError
         */
        $ncError = $_REQUEST['NCERROR'];

        return [
            'transaction'   => $transaction,
            'orderId'       => $orderId,
            'status'        => $status,
            'payId'         => $payId,
            'payIdSub'      => $payIdSub,
            'ncError'       => $ncError
        ];
    }

    private function getState($code)
    {
        $state = State::STATE_CANCELED;
        $status = State::STATUS_CANCELED;
        $fixInvoice = false;

        switch($code)
        {
            //1
            case Helper::POSTFINANCE_AUTH_REFUSED:
            case Helper::POSTFINANCE_PAYMENT_CANCELED_BY_CUSTOMER:
            case Helper::POSTFINANCE_PAYMENT_REFUSED:
                $state = State::STATE_CANCELED;
                $status = State::STATE_CANCELED;
                break;

            //5
            case Helper::POSTFINANCE_AUTHORIZED:
            case Helper::POSTFINANCE_AUTHORIZED_WAITING:
            case Helper::POSTFINANCE_AUTHORIZED_UNKNOWN:
            case Helper::POSTFINANCE_AUTHORIZED_TO_GET_MANUALLY:
                $state = State::STATE_PENDING_PAYMENT;
                $status = State::STATUS_PENDING_PAYMENT;
                break;

            //7
            case Helper::POSTFINANCE_PAYMENT_DELETED:
            case Helper::POSTFINANCE_PAYMENT_DELETED_WAITING:
            case Helper::POSTFINANCE_PAYMENT_DELETED_UNCERTAIN:
            case Helper::POSTFINANCE_PAYMENT_DELETED_REFUSED:
            case Helper::POSTFINANCE_PAYMENT_DELETED_OK:
            case Helper::POSTFINANCE_PAYMENT_DELETED_PROCESSED_MERCHANT:
                $state = State::STATE_CANCELED;
                $status = State::STATE_CANCELED;
                break;

            //9
            case Helper::POSTFINANCE_PAYMENT_REQUESTED:
            case Helper::POSTFINANCE_PAYMENT_PROCESSING:
            case Helper::POSTFINANCE_PAYMENT_UNCERTAIN:
                $state =  State::STATE_PROCESSING;
                $status = State::STATUS_PROCESSING;
                $fixInvoice = true;
                break;

        }

        return ['state' => $state, 'status' => $status, 'fixInvoice' => $fixInvoice];
    }
}