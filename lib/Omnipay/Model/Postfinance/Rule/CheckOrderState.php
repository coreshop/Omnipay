<?php

namespace Omnipay\Model\Postfinance\Rule;

use CoreShop\Model\Mail\Rule\Condition\AbstractCondition;
use CoreShop\Model;
use CoreShop\Model\Mail\Rule;
use Pimcore\Model\AbstractModel;
use Omnipay\Model\Postfinance\TransactionInfo;

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
     * If user has changed the operation value in postfinance backend to "authorized", payment state stays to PENDING_PAYMENT:
     * But it should be possible to send a confirmation to user even state has not changed internally.
     *
     * @param AbstractModel $object
     * @param array $params
     * @param Rule $rule
     *
     * @return boolean
     */
    public function checkCondition(AbstractModel $object, $params = [], Rule $rule)
    {
        if ($object instanceof Model\Order) {

            $paramsToExist = [
                'newState',
                'postfinanceStatus'
            ];

            foreach($paramsToExist as $paramToExist) {
                if(!array_key_exists($paramToExist, $params)) {
                    return false;
                }
            }

            $objectBeforeProcessedState = $params['beforeProcessedState'];
            $newState = $params['newState'];
            $postFinanceStatus = $params['postfinanceStatus'];
            if ($objectBeforeProcessedState === Model\Order\State::STATE_PENDING_PAYMENT &&
                $newState === Model\Order\State::STATE_PENDING_PAYMENT &&
                TransactionInfo::isAuthorized($postFinanceStatus)) {
                return true;
            }
        }

        return false;
    }
}