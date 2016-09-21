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

use CoreShop\Model;

/**
 * Class Omnipay_AdminController
 */
class Omnipay_AdminController extends \CoreShop\Plugin\Controller\Admin
{
    public function getProvidersAction(){
        $gateways = \Omnipay\Tool::getSupportedGateways();
        $available = [];
        $activeProviders = Model\Configuration::get("OMNIPAY.ACTIVEPROVIDERS");

        if(!is_array($activeProviders)) {
            $activeProviders = [];
        }

        foreach($gateways as $gateway) {
            $class = \Omnipay\Common\Helper::getGatewayClassName($gateway);
            if (\Pimcore\Tool::classExists($class)) {
                if(!in_array($gateway, $activeProviders)) {
                    $available[] = [
                        "name" => $gateway
                    ];
                }
            }
        }

        $this->_helper->json(array("data" => $available));
    }

    public function getProviderOptionsAction()
    {
        $provider = $this->getParam("provider");

        $gateway = \Omnipay\Omnipay::getFactory()->create($provider);

        $this->_helper->json(array("options" => $gateway->getParameters()));
    }

    public function getActiveProvidersAction()
    {
        $activeProviders = Model\Configuration::get("OMNIPAY.ACTIVEPROVIDERS");
        $result = [];

        if(is_array($activeProviders)) {
            foreach ($activeProviders as $provider) {
                $result[] = $this->getProviderArray($provider);
            }
        }

        $this->_helper->json($result);
    }

    public function addProviderAction()
    {
        $gateway = $this->getParam("provider");
        $gateways = \Omnipay\Tool::getSupportedGateways();

        if(in_array($gateway, $gateways)) {
            $activeProviders = Model\Configuration::get("OMNIPAY.ACTIVEPROVIDERS");

            if(!is_array($activeProviders)) {
                $activeProviders = [];
            }

            if(!in_array($gateway, $activeProviders)) {

                $activeProviders[] = $gateway;

                Model\Configuration::set("OMNIPAY.ACTIVEPROVIDERS", $activeProviders);

                $gateway = \Omnipay\Omnipay::getFactory()->create($gateway);

                $this->_helper->json(array("success" => true, "name" => $gateway, "settings" => $gateway->getParameters()));
            }
        }

        $this->_helper->json(array("success" => false));
    }

    public function removeProviderAction() {
        $gateway = $this->getParam("provider");

        if(in_array($gateway, \Omnipay\Omnipay::getFactory()->getSupportedGateways())) {
            $activeProviders = Model\Configuration::get("OMNIPAY.ACTIVEPROVIDERS");

            if(!is_array($activeProviders)) {
                $activeProviders = [];
            }

            if(in_array($gateway, $activeProviders)) {

                $index = array_search($gateway, $activeProviders);

                if($index >= 0) {
                    unset($activeProviders[$index]);
                }

                Model\Configuration::set("OMNIPAY.ACTIVEPROVIDERS", $activeProviders);


                $this->_helper->json(array("success" => true));
            }
        }

        $this->_helper->json(array("success" => false));
    }

    /**
     * @param $name
     * @return array
     */
    protected function getProviderArray($name) {
        $gateway = \Omnipay\Omnipay::getFactory()->create($name);

        $data = [
            'name' => $name,
            'settings' => $gateway->getParameters()
        ];

        return $data;
    }

    public function getAction()
    {
        $config = new Model\Configuration\Listing();
        $config->setFilter(function ($entry) {
            if (startsWith($entry['key'], "OMNIPAY.")) {
                return true;
            }

            return false;
        });

        $valueArray = array();

        foreach ($config->getConfigurations() as $c) {
            $valueArray[$c->getKey()] = $c->getData();
        }

        $response = array(
            "values" => $valueArray,
        );

        $this->_helper->json($response);
        $this->_helper->json(false);
    }

    public function setAction()
    {
        $values = \Zend_Json::decode($this->getParam("data"));

        $values = array_htmlspecialchars($values);

        foreach ($values as $key => $value) {
            Model\Configuration::set($key, $value);
        }

        $this->_helper->json(array("success" => true));
    }
}
