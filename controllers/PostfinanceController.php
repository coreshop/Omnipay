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

require "PaymentController.php";

use Omnipay\Postfinance\Message\Helper;

/**
 * Class Omnipay_PostfinanceController
 */
class Omnipay_PostfinanceController extends Omnipay_PaymentController
{
    /**
     * This Action listen to server2server communication
     */
    public function paymentReturnServerAction()
    {
        $requestData = $this->parseRequestData();

        $this->disableLayout();
        $this->disableViewAutoRender();

        \Pimcore\Logger::log('OmniPay paymentReturnServer [Postfinance]. TransactionID: ' . $requestData['transaction'] . ', Status: ' . $requestData['status']);

        if (!empty( $requestData['transaction'] )) {

            $order = \CoreShop\Model\Order::getById( $requestData['orderId'] );

            if ($order instanceof \CoreShop\Model\Order) {

                \Pimcore\Logger::notice('OmniPay paymentReturnServer [Postfinance]: create order with: ' . $requestData['transaction']);

                /** @var $state \CoreShop\Model\Order\State $state */
                $state = \CoreShop\Model\Order\State::getByIdentifier( $this->getStateId( $requestData['status'] ) );
                $state->processStep($order);

                $payments = $order->getPayments();

                foreach ($payments as $p) {
                    $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentOmnipay($p);
                    $dataBrick->setTransactionId( $requestData['transaction'] );
                    $p->save();
                }

            } else {
                \Pimcore\Logger::notice('OmniPay paymentReturnServer [Postfinance]: Order with identifier' . $requestData['transaction'] . 'not found');
            }
        } else {

            \Pimcore\Logger::notice('OmniPay paymentReturnServer [Postfinance]: No valid transaction id given');
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

        if (!empty( $requestData['transaction'] )) {

            $order = \CoreShop\Model\Order::getById( $requestData['orderId'] );

            if ($order instanceof \CoreShop\Model\Order) {

                \Pimcore\Logger::notice('OmniPay paymentReturn [Postfinance]: order with: ' . $requestData['transaction']);

                /** @var $state \CoreShop\Model\Order\State $state */
                $state = \CoreShop\Model\Order\State::getByIdentifier( $this->getStateId( $requestData['status'] ) );
                $state->processStep($order);

                $payments = $order->getPayments();

                foreach ($payments as $p) {
                    $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentOmnipay($p);
                    $dataBrick->setTransactionId( $requestData['transaction'] );
                    $p->save();
                }

                $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getConfirmationUrl($order);

            } else {

                \Pimcore\Logger::notice('OmniPay paymentReturn [Postfinance]: Order with identifier' . $requestData['transaction'] . 'not found');
                $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl( 'order with identifier' . $requestData['transaction'] . 'not found' );

            }

        } else {

            \Pimcore\Logger::notice('OmniPay paymentReturn [Postfinance]: No valid transaction id given');
            $redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl( 'no valid transaction id given' );

        }

        $this->redirect( $redirectUrl );
        exit;
    }

    public function getGatewayParams()
    {
        $params = parent::getGatewayParams();

        $language = $this->language;
        $gatewayLanguage = 'en_EN';

        if( !empty( $language ) )
        {
            $gatewayLanguage = $language . '_' . strtoupper( $language );
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
        $orderId = str_replace('order_', '', $transaction );

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

    private function getStateId($code)
    {
        $state = 'ERROR';

        switch( $code )
        {
            case Helper::POSTFINANCE_PAYMENT_REQUESTED:
                $state = 'PAYMENT';
                break;
            case Helper::POSTFINANCE_PAYMENT_PROCESSING:
                $state = 'PAYMENT';
                break;
            case Helper::POSTFINANCE_AUTHORIZED:
                $state = 'PAYMENT_PENDING';
                break;
            case Helper::POSTFINANCE_AUTHORIZED_WAITING:
                $state = 'PAYMENT_PENDING';
                break;
        }

        return $state;
    }
}