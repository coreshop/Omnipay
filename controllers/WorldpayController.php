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
 * Class Omnipay_WorldpayController
 */
class Omnipay_WorldpayController extends Omnipay_PaymentController
{
    public function paymentReturnAction()
    {
        $transaction = $_REQUEST['reference'];
        $orderId = str_replace('order_', '', $transaction );

        $status = $_REQUEST['transStatus'];

        if($status === 'Y') {
            if ($transaction) {
                $order = \CoreShop\Model\Order::getById( $orderId );

                if ($order instanceof \CoreShop\Model\Order) {

                    //get transaction
                    $this->getOrderPayment(
                        $order,
                        $_REQUEST['transId']
                    )->addTransactionNote($_REQUEST['transId'], $status);

                    try {
                        $params = [
                            'newState'      => \CoreShop\Model\Order\State::STATE_PROCESSING,
                            'newStatus'     => \CoreShop\Model\Order\State::STATUS_PROCESSING,
                        ];
                        \CoreShop\Model\Order\State::changeOrderState($order, $params);

                    } catch(\Exception $e) {
                        $message = 'OmniPay Gateway payment [' . $this->getModule()->getName() . ']: Error on OrderChange: ' . $e->getMessage();
                        \Pimcore\Logger::error($message);
                        $this->redirect($this->getModule()->getErrorUrl($e->getMessage()));
                    }

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
