
<form action="<?=$this->module->getPaymentUrl()?>" method="post">
    <div class="panel panel-smart">
        <div class="panel-heading">
            <h3 class="panel-title"><?=$this->translate($this->module->getName())?></h3>
        </div>
        <div class="panel-body delivery-options">
            <p><?=sprintf($this->translate("Do you want to pay the amount %s with %s?", \CoreShop::getTools()->formatPrice($this->cart->getTotal()), $this->translate($this->module->getName())))?></p>

            <div class="row">
                <div class="col-xs-12">
                    <a href="<?=$this->url(array("lang" => $this->language, "act" => "payment"), "coreshop_checkout")?>" class="btn btn-default pull-left">
                        <?=$this->translate("Back")?>
                    </a>

                    <button type="submit" class="btn btn-white btn-borderd pull-right">
                        <?=$this->translate("Submit Payment")?>
                    </button>
                </div>
            </div>

        </div>
    </div>

</form>