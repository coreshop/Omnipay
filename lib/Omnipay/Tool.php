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

class Tool
{
    public static function getSupportedGateways()
    {
        $package = json_decode(file_get_contents(PIMCORE_PLUGINS_PATH.'/Omnipay/composer.json'), TRUE);
        return $package['extra']['gateways'];

    }
}
