<?php
/**
 * Description: скрипт удаления не оплаченых чеков из таблицы payment
 */

namespace app\commands;

use app\models\Payment;
use yii\console\Controller;
use yii\console\ExitCode;
use DateTime;
use Yii;
use DateInterval;

class DeletePaidController extends Controller {

    /**
     * время в секундах, через которое запись будет удалена
     * 1 месяц
     */
    const REMOVAL_PERIOD = 2592000;

    public function actionIndex(){
        $dbPayments = Payment::find()
            ->where(['paid' => 0])
            ->andWhere(['<', 'created_at', time()-self::REMOVAL_PERIOD])
            ->all();
        foreach ($dbPayments as $dbPayment)
            $dbPayment->delete();
        return ExitCode::OK;
    }

}