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

namespace Omnipay\Controller;


/**
 * Class Omnipay_PaymentController
 */
class Payment extends \CoreShop\Controller\Action\Payment
{
    protected $gateway;

    public function init()
    {
        $this->gateway = $this->getParam("gateway");

        $activeProviders = \CoreShop\Model\Configuration::get("OMNIPAY.ACTIVEPROVIDERS");

        if(!is_array($activeProviders)) {
            $activeProviders = array();
        }

        if(!in_array($this->gateway, $activeProviders)) {
            throw new \Exception("Not supported");
        }

        parent::init();

        $gatewayName = strtolower($this->getModule()->getGateway()->getShortName());

        $pluginPath = PIMCORE_PLUGINS_PATH . "/Omnipay/views/scripts/" . $gatewayName;

        $this->view->setScriptPath(
            array_merge(
                array(
                    $pluginPath,
                    /*CORESHOP_TEMPLATE_BASE.'/scripts/omnipay/payment',
                    CORESHOP_TEMPLATE_BASE.'/scripts/coreshop/omnipay/payment',
                    CORESHOP_TEMPLATE_PATH.'/scripts/omnipay/payment',
                    CORESHOP_TEMPLATE_PATH.'/scripts/coreshop/omnipay/payment',
                    PIMCORE_WEBSITE_PATH.'/views/scripts/omnipay/payment',
                    PIMCORE_WEBSITE_PATH.'/views/scripts/coreshop/omnipay/payment',*/
                    CORESHOP_TEMPLATE_BASE.'/scripts/omnipay/' . $gatewayName,
                    CORESHOP_TEMPLATE_BASE.'/scripts/coreshop/omnipay/' . $gatewayName,
                    CORESHOP_TEMPLATE_PATH.'/scripts/omnipay/' . $gatewayName,
                    CORESHOP_TEMPLATE_PATH.'/scripts/coreshop/omnipay/' . $gatewayName,
                    PIMCORE_WEBSITE_PATH.'/views/scripts/omnipay/' . $gatewayName,
                    PIMCORE_WEBSITE_PATH.'/views/scripts/coreshop/omnipay/' . $gatewayName,
                ),
                $this->view->getScriptPaths()
            )
        );
    }

    /**
     * @return \Omnipay\Shop\Provider
     */
    public function getModule()
    {
        if (is_null($this->module)) {
            $this->module = \CoreShop::getPaymentProvider("omnipay" . $this->gateway);
        }

        return $this->module;
    }
}
