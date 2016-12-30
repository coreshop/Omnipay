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

namespace Omnipay;

use CoreShop\Model\Configuration;
use CoreShop\Composite\Dispatcher;
use CoreShop\Plugin as CorePlugin;
use Omnipay\Model\Postfinance\Rule\CheckOrderState;
use Omnipay\Shop\Install;
use Omnipay\Shop\Provider;

/**
 * Class Shop
 * @package Omnipay
 */
class Shop
{
    public static $install;

    /**
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function attachEvents()
    {
        self::getInstall()->attachEvents();

        $activeProviders = Configuration::get('OMNIPAY.ACTIVEPROVIDERS');

        if(!is_array($activeProviders)) {
            $activeProviders = [];
        }

        foreach($activeProviders as $provider) {
            $gateway = \Omnipay\Omnipay::getFactory()->create($provider);
            $config = Configuration::get('OMNIPAY.' . strtoupper($provider));

            if(is_null($config)) {
                $config = [];
            }

            $shopProvider = new Provider($gateway, $config);

            \Pimcore::getEventManager()->attach('coreshop.payment.getProvider', function ($e) use ($shopProvider) {
                return $shopProvider;
            });
        }

        \Pimcore::getEventManager()->attach('coreshop.rules.mailRulesOrder.condition.init', function($e) {
            $dispatcher = $e->getTarget();

            if($dispatcher instanceof Dispatcher) {
                $dispatcher->addType(CheckOrderState::class);
            }
        });
    }


    /**
     * @return Install
     */
    public static function getInstall()
    {
        if (!self::$install) {
            self::$install = new Install();
        }
        return self::$install;
    }
}
