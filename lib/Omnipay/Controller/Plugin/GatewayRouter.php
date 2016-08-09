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

namespace Omnipay\Controller\Plugin;
use Pimcore\Model\Staticroute;

/**
 * Class TemplateRouter
 * @package Omnipay\Controller\Plugin
 */
class GatewayRouter extends \Zend_Controller_Plugin_Abstract
{
    /**
     * Checks if Controller is available in Template and use it.
     *
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeShutdown(\Zend_Controller_Request_Abstract $request)
    {
        $gatewayRequest = clone $request;
        if ($request->getModuleName() === 'Omnipay') {
            $frontController = \Zend_Controller_Front::getInstance();
            $route = Staticroute::getCurrentRoute();

            if($route) {
                if($route->getName() === "coreshop_omnipay_payment") {
                    $gateway = ucfirst($request->getParam("gateway"));
                    $gatewayRequest->setControllerName($gateway);

                    if ($frontController->getDispatcher()->isDispatchable($gatewayRequest)) {
                        $request->setControllerName($gateway);
                    }
                }
            }
        }
    }
}
