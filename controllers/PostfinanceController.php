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

require 'PaymentController.php';

use Omnipay\Model\Postfinance\TransactionInfo;

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
        $this->disableLayout();
        $this->disableViewAutoRender();

        try {
            $order = $this->_processRequest();
        } catch(\Exception $e) {
            \Pimcore\Logger::notice($e->getMessage());
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
        $this->disableLayout();
        $this->disableViewAutoRender();

        try {
            $order = $this->_processRequest();
            $this->redirect($this->getModule()->getConfirmationUrl($order));
        } catch(\Exception $e) {
            \Pimcore\Logger::notice($e->getMessage());
            $this->redirect($this->getModule()->getErrorUrl($e->getMessage()));
        }

        exit;
    }

    public function paymentReturnAbortAction()
    {
        $this->disableLayout();
        $this->disableViewAutoRender();

        try {
            $order = $this->_processRequest();
            parent::paymentReturnAbortAction();
        } catch(\Exception $e) {
            \Pimcore\Logger::notice($e->getMessage());
            $this->redirect($this->getModule()->getErrorUrl($e->getMessage()));
        }
    }

    public function errorAction()
    {
        $this->disableLayout();
        $this->disableViewAutoRender();
        $this->coreShopForward('error', 'checkout', 'CoreShop', []);
    }

    private function _processRequest()
    {
        $requestData = $this->parseRequestData();

        \Pimcore\Logger::notice('OmniPay [Postfinance]: TransactionID: ' . $requestData['transaction'] . ', Status: ' . $requestData['status']);

        if (empty($requestData['transaction'])) {
            throw new \Exception('OmniPay [Postfinance]: No valid transaction id given');
        }

        $order = \CoreShop\Model\Order::getById($requestData['orderId']);

        if (!$order instanceof \CoreShop\Model\Order) {
            throw new \Exception('OmniPay [Postfinance]: Order with identifier ' . $requestData['transaction'] . ' not found');
        }

        //get current state
        $beforeProcessedState = $order->getOrderState();

        //get transaction
        $orderPayment = $this->getOrderPayment(
            $order,
            $requestData['payId']
        );

        //check if this payment transaction already has been dispatched
        $latestTransaction = $orderPayment->getLastTransactionNote();

        //maybe order already has been processed by server communication. return order and return a healthy order.
        if ($latestTransaction instanceof \Pimcore\Model\Element\Note) {
            $data = $latestTransaction->data;
            if (isset($data['code']) && (int) $data['code']['data'] === $requestData['status']) {
                \Pimcore\Logger::notice('OmniPay [Postfinance]: State (' . $requestData['status'] . ') already has been processed.');
                return $order;
            }
        }

        $orderPayment->addTransactionNote(
            $requestData['payId'],
            $requestData['status'],
            TransactionInfo::getStatusTranslation($requestData['status'], true)
        );

        //get right state
        $state = TransactionInfo::getState($requestData['status']);
        \Pimcore\Logger::notice('OmniPay [Postfinance]: Change order state to: ' . $state['state']);

        $params = [
            'newState'      => $state['state'],
            'newStatus'     => $state['status'],
        ];

        try {
            \CoreShop\Model\Order\State::changeOrderState($order, $params);
        } catch(\Exception $e) {
            // fail silently.
            \Pimcore\Logger::notice('OmniPay [Postfinance]: changeOrderState Error: ' . $e->getMessage());
        }

        //if state has ability to create invoice, do it now!
        if ($state['invoicingPossible'] === TRUE) {
            try {
                $order->createInvoiceForAllItems();
            } catch(\Exception $e) {
                // fail silently.
            }
        }

        /* @fixme!
        $payments = $order->getPayments();
        foreach ($payments as $p) {
            $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentOmnipay($p);
            $dataBrick->setTransactionId($requestData['transaction']);
            $p->save();
        }
        */

        //custom mail rule.
        \CoreShop\Model\Mail\Rule::apply('order', $order, [
            'beforeProcessedState'  => $beforeProcessedState['name'],
            'newState'              => $state['state'],
            'postfinanceStatus'     => $requestData['status']
        ]);

        return $order;

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
}