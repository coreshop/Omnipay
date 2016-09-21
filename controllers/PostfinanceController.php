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

/**
 * Class Omnipay_PostfinanceController
 */
class Omnipay_PostfinanceController extends Omnipay_PaymentController
{
    public function paymentReturnAction()
    {
        /**
         * @var $transaction
         * CoreShop transaction ID
         *
         */
        $transaction = $_REQUEST['orderID'];

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

        \Pimcore\Logger::log('Payment return from Postfinance. TransactionID: ' . $transaction . ', Status: ' . $status, 'notice');

        if($status === 5) {
            if (!empty($transaction)) {
                $cart = \CoreShop\Model\Cart::findByCustomIdentifier($transaction);

                if ($cart instanceof \CoreShop\Model\Cart) {
                    $order = $cart->createOrder(\CoreShop\Model\Order\State::getById(\CoreShop\Model\Configuration::get("SYSTEM.ORDERSTATE.PAYMENT")), $this->getModule(), $this->cart->getTotal(), $this->view->language);

                    $payments = $order->getPayments();

                    foreach ($payments as $p) {
                        $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentOmnipay($p);
                        $dataBrick->setTransactionId($transaction);

                        $p->save();
                    }

                    $this->view->redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getConfirmationUrl($order);
                } else {
                    $this->view->redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl();
                }
            } else {
                $this->view->redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl();
            }
        } else {
            $this->view->redirectUrl = Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl();
        }

        $this->disableLayout();
    }
}