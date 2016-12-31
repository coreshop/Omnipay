<?php

namespace Omnipay\Model\Postfinance;

use Omnipay\Postfinance\Message\Helper;
use CoreShop\Model\Order\State;

class TransactionInfo {

    protected static $STATUS_TRANSLATION = [
        'STATUS_1'  => 'Cancelled by client',
        'STATUS_2'  => 'Authorization refused',
        'STATUS_3'  => '',
        'STATUS_4'  => 'Order stored',
        'STATUS_40' => 'Stored waiting external result',
        'STATUS_41' => 'Waiting client payment',
        'STATUS_46' => 'Waiting for identification)',
        'STATUS_5'  => 'Authorized',
        'STATUS_50' => 'Authorized waiting external result',
        'STATUS_51' => 'Authorization waiting',
        'STATUS_52' => 'Authorization not known',
        'STATUS_55' => 'Stand-by',
        'STATUS_56' => 'OK with scheduled payments',
        'STATUS_57' => 'Error in scheduled payments',
        'STATUS_59' => 'Authorized to get manually',
        'STATUS_6'  => 'Authorized and cancelled',
        'STATUS_61' => 'Author. deletion waiting',
        'STATUS_62' => 'Author. deletion uncertain',
        'STATUS_63' => 'Author. deletion refused',
        'STATUS_64' => 'Authorized and cancelled',
        'STATUS_7'  => 'Payment deleted',
        'STATUS_71' => 'Payment deletion pending',
        'STATUS_72' => 'Payment deletion uncertain',
        'STATUS_73' => 'Payment deletion refused',
        'STATUS_74' => 'Payment deleted',
        'STATUS_75' => 'Deletion processed by merchant',
        'STATUS_8'  => 'Refund',
        'STATUS_81' => 'Refund pending',
        'STATUS_82' => 'Refund uncertain',
        'STATUS_83' => 'Refund refused',
        'STATUS_84' => 'Payment declined by the acquirer',
        'STATUS_85' => 'Refund processed by merchant',
        'STATUS_9'  => 'Payment requested',
        'STATUS_91' => 'Payment processing',
        'STATUS_92' => 'Payment uncertain',
        'STATUS_93' => 'Payment refused',
        'STATUS_94' => 'Refund declined by the acquirer',
        'STATUS_95' => 'Payment processed by merchant',
        'STATUS_99' => 'Being processed'
    ];

    public static function getStatusTranslation($status, $addCode = false)
    {
        if (is_numeric($status)) {
            $status = 'STATUS_' . $status;
        }

        $hasStatus = isset(self::$STATUS_TRANSLATION[$status]);
        $transStatus = $hasStatus ? self::$STATUS_TRANSLATION[$status] : $status;

        if( $addCode && $hasStatus) {
            $transStatus = $transStatus . ' (' . str_replace('STATUS_', '', $status) . ')';
        }

        return $transStatus;
    }

    public static function getState($code)
    {
        $state = State::STATE_CANCELED;
        $status = State::STATUS_CANCELED;
        $invoicingPossible = false;

        switch($code)
        {
            //1
            case Helper::POSTFINANCE_AUTH_REFUSED:
            case Helper::POSTFINANCE_PAYMENT_CANCELED_BY_CUSTOMER:
            case Helper::POSTFINANCE_PAYMENT_REFUSED:
                $state = State::STATE_CANCELED;
                $status = State::STATE_CANCELED;
                break;

            //5
            case Helper::POSTFINANCE_AUTHORIZED:
            case Helper::POSTFINANCE_AUTHORIZED_WAITING:
            case Helper::POSTFINANCE_AUTHORIZED_UNKNOWN:
            case Helper::POSTFINANCE_AUTHORIZED_TO_GET_MANUALLY:
                $state = State::STATE_PENDING_PAYMENT;
                $status = State::STATUS_PENDING_PAYMENT;
                break;

            //7
            case Helper::POSTFINANCE_PAYMENT_DELETED:
            case Helper::POSTFINANCE_PAYMENT_DELETED_WAITING:
            case Helper::POSTFINANCE_PAYMENT_DELETED_UNCERTAIN:
            case Helper::POSTFINANCE_PAYMENT_DELETED_REFUSED:
            case Helper::POSTFINANCE_PAYMENT_DELETED_OK:
            case Helper::POSTFINANCE_PAYMENT_DELETED_PROCESSED_MERCHANT:
                $state = State::STATE_CANCELED;
                $status = State::STATE_CANCELED;
                break;

            //9
            case Helper::POSTFINANCE_PAYMENT_REQUESTED:
            case Helper::POSTFINANCE_PAYMENT_PROCESSING:
            case Helper::POSTFINANCE_PAYMENT_UNCERTAIN:
                $state =  State::STATE_PROCESSING;
                $status = State::STATUS_PROCESSING;
                $invoicingPossible = true;
                break;

        }

        return ['state' => $state, 'status' => $status, 'invoicingPossible' => $invoicingPossible];
    }

    public static function isAuthorized($code)
    {
        return in_array(
            $code,
            [
                Helper::POSTFINANCE_AUTHORIZED,
                Helper::POSTFINANCE_AUTHORIZED_WAITING,
                Helper::POSTFINANCE_AUTHORIZED_UNKNOWN,
                Helper::POSTFINANCE_AUTHORIZED_TO_GET_MANUALLY
            ]
        );
    }
}