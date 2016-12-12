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

namespace Omnipay\Shop;

use CoreShop\Model\Cart;
use CoreShop\Model\Order;
use CoreShop\Model\Plugin\Payment as CorePayment;
use CoreShop\Plugin as CorePlugin;
use Omnipay\Common\AbstractGateway;

/**
 * Class Shop
 * @package Omnipay
 */
class Provider extends CorePayment
{
    /**
     * @var AbstractGateway
     */
    public $gateway;

    /**
     * @var array
     */
    public $settings;

    /**
     * Provider constructor.
     * @param $gateway
     * @param array $settings
     */
    public function __construct($gateway, array $settings)
    {
        $this->gateway = $gateway;
        $this->settings = $settings;

        $this->gateway->initialize($settings);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getGateway()->getShortName();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return "/plugins/Omnipay/static/img/omnipay.jpg";
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return "omnipay" . $this->getGateway()->getShortName();
    }

    /**
     * Get Payment Fee
     *
     * @param Cart $cart
     * @param boolean $useTaxes Use Taxes?
     * @return float
     */
    public function getPaymentFee(Cart $cart, $useTaxes = true)
    {
        return 0;
    }

    /**
     * Process Validation for Payment
     *
     * @param Cart $cart
     * @return mixed
     */
    public function process(Cart $cart)
    {
        return $this->getProcessValidationUrl();
    }

    /**
     * Get url for confirmation link
     *
     * @param Order $order
     * @return string
     */
    public function getConfirmationUrl($order)
    {
        return $this->url($this->getIdentifier(), "confirmation", array("order" => $order->getId()));
    }

    /**
     * get url for validation link
     *
     * @return string
     */
    public function getProcessValidationUrl()
    {
        return $this->url($this->getIdentifier(), "validate");
    }

    /**
     * get url payment link
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->url($this->getIdentifier(), "payment");
    }

    /**
     * get error url
     *
     * @param String $errorMessage
     * @return string
     */
    public function getErrorUrl( $errorMessage = "")
    {
        return $this->url($this->getIdentifier(), "error", array("error" => $errorMessage));
    }

    /**
     * @return AbstractGateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @param AbstractGateway $gateway
     */
    public function setGateway($gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * assemble route with zend router.
     *
     * @param $module string module name
     * @param $action string action name
     * @param $params array additional params
     *
     * @return string
     */
    public function url($module, $action, $params = [])
    {
        $params = array_merge($params, array("gateway" => $this->getGateway()->getShortName(), "mod" => "Omnipay", "act" => $action, "lang" => (string) \Zend_Registry::get("Zend_Locale")));

        $url = \CoreShop::getTools()->url($params, "coreshop_omnipay_payment");
        $url = str_replace("/omnipay/", "/Omnipay/", $url);
        $url = str_replace("/" . strtolower( $this->getGateway()->getName() ), "/" . $this->getGateway()->getName(), $url);

        return $url;
    }
}