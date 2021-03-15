<?php
/**
 * Description: Скрипт продления тарифа по истечению срока действия
 */

namespace app\commands;

use app\models\AuthAssignment;
use app\models\Balance;
use app\models\Payment;
use app\models\TrafficNumber;
use app\models\user\User;
use yii\console\Controller;
use yii\console\ExitCode;
use DateTime;
use Yii;
use DateInterval;

class MoneyBotController extends Controller {

    public function actionIndex(){

        $dbUsers = User::find()->where(['status' => 10])->andWhere(['tariff_activity' => 1])->all();
        foreach ($dbUsers as $dbUser){

            $tariffUser = AuthAssignment::find()->where(['user_id' => $dbUser->id])->one();

            //вычисление количества дней активности тарифа
            $today = new DateTime();
            $updTariffUser = new DateTime();
            $updTariffUser->setTimestamp($tariffUser->updated_at);
            $interval = $today->diff($updTariffUser)->format('%a');
//            echo $dbUser->username." - ".$updTariffUser->format('d.m.Y')." - ".$interval."\n";

            if ( $interval == Yii::$app->params['checkInterval']['alarm'] ) {
                //если тариф триал
                if ( $tariffUser->item_name == Yii::$app->params['tariff'][0] ){
                    //уведомление о необходимости сменить тариф
                    if ( $dbUser->send_message ) $this->mail('trialChange', $dbUser->username, $dbUser->email);
                    continue;
                }
                //для остальных тарифов
                //запрос баланса
                $dbBalance = Balance::find()->where(['user_id' => $dbUser->id])->one();
                //стоимость тарифов
                $costTariff = Yii::$app->params['costTariff'][$tariffUser->item_name] * Yii::$app->params['payMultiplier'];
                //денег хватает
                if ( $dbBalance->balance >= $costTariff ) continue;

                //денег не хватает

                //уведомление о необходимости пополнить баланс
                if ( $dbUser->send_message ) $this->mail('tariffRecharge', $dbUser->username, $dbUser->email);
                continue;
            }

            if ( $interval == Yii::$app->params['checkInterval']['prolongation'] ){
                //если тариф триал
                if ( $tariffUser->item_name == Yii::$app->params['tariff'][0] ){
                    //Уведомление об отключении работы системы
                    if ( $dbUser->send_message ) $this->mail('trialOff', $dbUser->username, $dbUser->email);

                    //отключение тарифа
                    $dbUser->tariff_activity = 0;
                    $dbUser->update();
                    continue;
                }

                //если автопродление выключено - следующая итерация
                if ( $dbUser->prolongation == 0 ) {
                    //уведомление об отключении работы системы
                    if ( $dbUser->send_message )$this->mail('tariffOffProlong', $dbUser->username, $dbUser->email);

                    //отключение тарифа
                    $dbUser->tariff_activity = 0;
                    $dbUser->update();
                    continue;
                }

                //запрос баланса
                $dbBalance = Balance::find()->where(['user_id' => $dbUser->id])->one();
                //стоимость тарифов
                $costTariff = Yii::$app->params['costTariff'][$tariffUser->item_name] * Yii::$app->params['payMultiplier'];
                //денег не хватает
                if ( $dbBalance->balance < $costTariff ) {
                    //Уведомление об отключении работы системы
                    if ( $dbUser->send_message ) $this->mail('tariffOffMoney', $dbUser->username, $dbUser->email);

                    //отключение тарифа
                    $dbUser->tariff_activity = 0;
                    $dbUser->update();
                    continue;
                }

                //денег хватает
                //cписываем сумму с баланса
                $dbBalance->balance = $dbBalance->balance - $costTariff;
                $dbBalance->update();

                //запись оплаты в таблицу payment
                $dbPay = new Payment();
                $dbPay->user_id = $dbUser->id;
                $dbPay->sum = $costTariff;
                $dbPay->operation = 'minus';
                $dbPay->paid = 1;
                $dbPay->save();

                //обнуляем количество переходов
                $dbTrafficNumber = TrafficNumber::find()->where(['user_id' => $dbUser->id])->one();
                $dbTrafficNumber->traffic_number = 0;
                $dbTrafficNumber->update();

                //добавляем +30 дней к updated_at
                $newDate = $updTariffUser->add(new DateInterval('P'.Yii::$app->params['checkInterval']['prolongation'].'D'));
                $tariffUser->updated_at = $newDate->format('U');
                $tariffUser->detachBehaviors();
                $tariffUser->update();

                //уведомление о списании денег?
                
                continue;
            }
        }
        return ExitCode::OK;
    }

    protected function mail($type, $username, $mail){

        switch ($type){
            case 'trialChange':
                $theme = 'Срок действия пробного периода скоро закончится';
                break;
            case 'tariffRecharge':
                $theme = 'Срок действия тарифа скоро закончится';
                break;
            case 'trialOff':
                $theme = 'Срок действия пробного периода закончился';
                break;
            case 'tariffOffProlong':
                $theme = 'Срок действия тарифа закончился';
                break;
            case 'tariffOffMoney':
                $theme = 'Не достаточно средств на счету';
                break;
        }

        Yii::$app->mailer->compose('console/'.$type, ['username' => $username])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($mail)
            ->setSubject($theme)
//            ->setTextBody('Текст сообщения')
//            ->setHtmlBody('<b>текст сообщения в формате HTML</b>')
            ->send();
    }

    public function actionTest(){
        $today = new DateTime('2019-05-22 00:00');
        $updTariffUser = new DateTime('2019-05-01 16:00');
        $interval = $today->diff($updTariffUser)->format('%a');
        debug($interval);
    }

    public function actionTest2(){
        $dbUsers = User::find()->where(['status' => 10])->andWhere(['tariff_activity' => 1])->all();
        foreach ($dbUsers as $dbUser){
            if ( $dbUser->send_message ) echo $dbUser->username." - ".$dbUser->send_message."\n";
        }
    }

    public function actionTest3(){
        Yii::$app->mailer->compose('console/trialChange', ['username' => 'Вася'])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo('info@reked.ru')
            ->setSubject('Срок действия пробного периода скоро закончится')
//            ->setTextBody('Текст сообщения')
//            ->setHtmlBody('<b>текст сообщения в формате HTML</b>')
            ->send();
        echo 'complite';
    }

}