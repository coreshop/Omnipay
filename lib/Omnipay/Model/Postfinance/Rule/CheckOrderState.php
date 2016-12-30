<?php

namespace Omnipay\Model\Postfinance\Rule;

use CoreShop\Model\Mail\Rule\Condition\AbstractCondition;
use CoreShop\Model;
use CoreShop\Model\Mail\Rule;
use Pimcore\Model\AbstractModel;

/**
 * Class CheckOrderState
 * @package Omnipay\Model\Postfinance\Rule
 */
class CheckOrderState extends AbstractCondition
{
    /**
     * @var string
     */
    public static $type = 'postFinanceCheckOrderState';

    /**
     * @param AbstractModel $object
     * @param array $params
     * @param Rule $rule
     *
     * @return boolean
     */
    public function checkCondition(AbstractModel $object, $params = [], Rule $rule)
    {
        if($object instanceof Model\Order) {
            return true;
        }

        return false;
    }
}