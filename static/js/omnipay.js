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

$(document).on("coreShopReady", function() {
    pimcore.registerNS("pimcore.plugin.omnipay.plugin");
    pimcore.plugin.omnipay.plugin = Class.create(coreshop.plugin.admin, {

        getClassName: function () {
            return "pimcore.plugin.omnipay.plugin";
        },

        initialize: function () {
            coreshop.plugin.broker.registerPlugin(this);
        },

        uninstall: function () {
            //TODO remove from menu
        },

        coreshopReady: function (coreshop, broker) {
            coreshop.addPluginMenu({
                text: t("coreshop_omnipay"),
                iconCls: "coreshop_icon_omnipay",
                handler: this.openOmnipay
            });
        },

        openOmnipay: function () {
            try {
                pimcore.globalmanager.get("coreshop_omnipay").activate();
            }
            catch (e) {
                //console.log(e);
                pimcore.globalmanager.add("coreshop_omnipay", new pimcore.plugin.omnipay.settings());
            }
        }
    });

    new pimcore.plugin.omnipay.plugin();
});