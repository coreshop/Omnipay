/**
 * Omnipay
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (http://www.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.plugin.omnipay.settings");
pimcore.plugin.omnipay.settings = Class.create({

    providerPanels : {},

    initialize: function () {
        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: "/plugin/Omnipay/admin/get",
            success: function (response)
            {
                this.data = Ext.decode(response.responseText);

                this.getPanel();

                Ext.Ajax.request({
                    url: "/plugin/Omnipay/admin/get-active-providers",
                    success: function (response)
                    {
                        var result = Ext.decode(response.responseText);

                        Ext.each(result, function(provider) {
                            this.addProviderPanel(provider.name, provider);
                        }.bind(this));

                    }.bind(this)
                });

            }.bind(this)
        });
    },

    getValue: function (name, key) {
        var current = null;

        if(this.data.values.hasOwnProperty(name)) {
            current = this.data.values[name];

            if(current.hasOwnProperty(key)) {
                current = current[key];
            }
        }

        if (typeof current != "object" && typeof current != "array" && typeof current != "function") {
            return current;
        }

        return "";
    },

    getPanel : function() {
        if (!this.panel) {
            this.providerStore = new Ext.data.Store({
                fields : [
                    'name'
                ],
                proxy: {
                    type: 'ajax',
                    url: '/plugin/Omnipay/admin/get-providers',
                    reader: {
                        type: 'json',
                        rootProperty : 'data'
                    }
                }
            });
            this.providerStore.load();

            this.panel = Ext.create('Ext.tab.Panel', {
                id: "coreshop_omnipay",
                title: t("coreshop_omnipay"),
                iconCls: "coreshop_icon_omnipay",
                bodyPadding: 20,
                border: false,
                layout: "fit",
                closable: true,
                buttons: [
                    {
                        text: "Save",
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply"
                    }
                ],
                items: [{
                    xtype : 'panel',
                    title: t("coreshop_omnipay_settings"),
                    iconCls: "coreshop_icon_omnipay",
                    border: false,
                    items : [{
                        xtype : 'combo',
                        fieldLabel : t('coreshop_omnipay_provider'),
                        store : this.providerStore,
                        displayField: 'name',
                        valueField: 'name',
                        forceSelection: true,
                        triggerAction: 'all',
                        name: 'providerToAdd'
                    }, {
                        xtype : 'button',
                        text: t('coreshop_omnipay_provider_add'),
                        handler: function(btn) {
                            var provider = btn.up("panel").down("combo").getValue();

                            Ext.Ajax.request({
                                url: "/plugin/Omnipay/admin/add-provider",
                                params : {provider : provider},
                                success: function (response)
                                {
                                    var data = Ext.decode(response.responseText);

                                    if(data.success) {
                                        this.addProviderPanel(provider, data);

                                        this.providerStore.load();
                                    }
                                    else {
                                        pimcore.helpers.showNotification(t("error"), "", "error");
                                    }

                                }.bind(this)
                            });

                        }.bind(this),
                        iconCls: 'pimcore_icon_apply'
                    }]
                }]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("coreshop_omnipay");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("coreshop_omnipay");
            }.bind(this));
        }

        return this.panel;
    },

    addProviderPanel : function(name, data) {
        var me = this;
        var items = [];

        for (var key in data.settings) {
            if (data.settings.hasOwnProperty(key)) {
                var providerName = ('OMNIPAY.' + name).toUpperCase();

                items.push({
                    fieldLabel: key,
                    name: key,
                    value: this.getValue(providerName, key)
                });
            }
        }

        var panel = new Ext.form.Panel({
            title: name,
            bodyPadding: 10,
            border: false,
            defaultType: 'textfield',
            defaults: {
                forceLayout: true
            },
            closable : true,
            listeners : {
                beforeclose : function(panel, eOpts) {
                    Ext.MessageBox.confirm(
                        t("coreshop_omnipay_remove"),
                        t("coreshop_omnipay_remove_provider"),
                        function (buttonValue) {
                            if (buttonValue === "yes") {
                                Ext.Ajax.request({
                                    url: "/plugin/Omnipay/admin/remove-provider",
                                    params: {provider: name},
                                    success: function (response) {
                                        var data = Ext.decode(response.responseText);

                                        if (data.success) {
                                            delete me.providerPanels[name];
                                            panel.destroy();
                                        }
                                        else {
                                            pimcore.helpers.showNotification(t("error"), "", "error");
                                        }

                                    }.bind(this)
                                });
                            }
                        }.bind(this)
                    );

                    return false;
                }
            },
            fieldDefaults: {
                labelWidth: 250
            },
            items : items
        });

        this.providerPanels[name] = panel;

        this.panel.add(panel);

        return panel;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("coreshop_omnipay");
    },

    save: function () {
        var values = {};

        for (var key in this.providerPanels) {
            if (this.providerPanels.hasOwnProperty(key)) {
                var form = this.providerPanels[key];

                values['OMNIPAY.' + key.toUpperCase()] = form.getForm().getFieldValues();
            }
        }

        Ext.Ajax.request({
            url: "/plugin/Omnipay/admin/set",
            method: "post",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("coreshop_omnipay_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("coreshop_omnipay_save_error"),
                            "error", t(res.message));
                    }
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("coreshop_omnipay_save_error"), "error");
                }
            }
        });
    }
});