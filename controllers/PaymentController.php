<?php

namespace app\controllers;

use app\models\AuthAssignment;
use app\models\Balance;
use app\models\EnabledDomain;
use app\models\GeoPage;
use app\models\Payment;
use app\models\TrafficNumber;
use app\models\user\User;
use app\models\user\PasswordChangeForm;
use app\models\YandexBidder;
use app\models\Coupon;
use Yii;
use DateTime;
use yii\data\ActiveDataProvider;

class PaymentController extends AppController {

    public function beforeAction($action) {            
        if ($action->id == 'result')
            $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionIndex() {
        //получаем имя юзера
        $email = Yii::$app->user->identity->email;

        //получаем баланс юзера
        $dbBalance = Balance::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        $balance = number_format($dbBalance->balance / Yii::$app->params['payMultiplier'], 2, ',', ' ');

        //получаем операции с финансами, сортируем по убыванию
        $dbOperations = Payment::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['paid' => 1])
            ->orderBy(['id' => SORT_DESC ])
            ->all();

        //проверка оплаты
        $paid = false;
        if (  Yii::$app->request->get('InvId')) {
            if ( Yii::$app->request->get('InvId') == $dbOperations[0]['id'] ) {
                $paid = 1;
            } else {
                $paid = 2;
            }
        }

        //данные для показа в таблице история операций
        $dataProvider = new ActiveDataProvider([
            'query' => Payment::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['paid' => 1]),
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_ASC
                ]
            ]
        ]);

        \app\assets\PaymentAsset::register($this->view);
        return $this->render('index', compact('balance', 'email', 'dbOperations', 'paid', 'dataProvider'));
    }

    public function actionPay() {
        $dbPay = Yii::$app->request->isPost ? new Payment(['scenario' => 'saveToDb']) : new Payment();

        if ( Yii::$app->request->isPost ) {
            $dbPay->user_id = Yii::$app->user->getId();
            $dbPay->sum = Yii::$app->request->post('Payment')['pay_add_sum'] * Yii::$app->params['payMultiplier'];
            $dbPay->operation = 'plus';
            $dbPay->pay_type = Yii::$app->request->post('Payment')['pay_type'];
            if ( !$dbPay->save() ) {
                Yii::warning('Ошибка проведения платежа');
                exit;
            }

            if ( $dbPay->pay_type == 'yandex-money' || $dbPay->pay_type == 'yandex-card' ) {
                return $this->renderAjax('ya-post-form', [
                    'payType' => $dbPay->pay_type,
                    'sum' => $dbPay->sum / Yii::$app->params['payMultiplier'],
                    'checkId' => $dbPay->id
                ]);
            }


            /*$merchantLogin = "reked.ru";
            $merchantPassword = "QWexJgC63iUsK8qFn1E6";//пароль для тестовых платежей №1. Заменить
            $Description = "Тестовый платеж";//заменить
            $culture = "ru";
            $encoding = "utf-8";
            $isTest = 1;//признак тестового платежа. Убрать

            $dbPay->user_id = Yii::$app->user->getId();
            $dbPay->load( Yii::$app->request->post() );
            $sum = $dbPay->sum;
            $dbPay->sum = $dbPay->sum * Yii::$app->params['payMultiplier'];
            $dbPay->operation = 'plus';
            if ( $dbPay->save()) {
                //контрольная хэш-сумма
                $hash = md5("$merchantLogin:$sum:$dbPay->id:$merchantPassword");
                $url = "https://auth.robokassa.ru/Merchant/Index.aspx?MrchLogin=$merchantLogin&" .
                    "OutSum=$sum&InvId=$dbPay->id&Desc=$Description&SignatureValue=$hash" .
                    "&Culture=$culture&Encoding=$encoding&IsTest=$isTest";
                return $this->redirect($url);
            }//добавить else*/
        }

        return $this->renderAjax('pay', compact('dbPay'));
    }

    // сюда придет оповещение об успешном платеже
    public function actionResult() {
        $request = Yii::$app->request;
        // если ответ об оплате идет с Яндекса
        // проверяется наличие параметра 'notification_type', кот. обязательно должен присутствовать
        if ( $request->isPost && $request->post('notification_type') ) {
            // если оплата на Яндекс кошелек идет с сервиса Reked
            if ( stripos($request->post('label'), 'reked') !== false ) {
                $yaResponse = $request->post();
                $computedHash = sha1(
                    $yaResponse['notification_type'] . '&' .
                    $yaResponse['operation_id'] . '&' .
                    $yaResponse['amount'] . '&' .
                    $yaResponse['currency'] . '&' .
                    $yaResponse['datetime'] . '&' .
                    $yaResponse['sender'] . '&' .
                    $yaResponse['codepro'] . '&' .
                    Yii::$app->params['yaMoneySecret'] . '&' .
                    $yaResponse['label']
                );
                // сверка хэшей
                if ( $yaResponse['sha1_hash'] != $computedHash ) {
                    Yii::warning('Оплата не прошла. Не совпали хэши');
                    exit;
                }
                // получение чека операции
                $dbPay = Payment::find()
                    // убираем из параметра 'label' первые 6 знаков, т.е. 'reked:'
                    ->where(['id' => substr($yaResponse['label'], 6)])
                    ->andWhere(['paid' => 0])
                    ->one();
                $user = User::findOne(['id' => $dbPay->user_id]);
                // проверка зачислен ли платеж на кошелек (код протекции, место в кошельке)
                if ( $yaResponse['unaccepted'] === 'true' ) {
                    Yii::$app->mailer->compose('errorYandexPay', ['username' => $user->username])
                    ->setFrom(Yii::$app->params['supportEmail'])
                    ->setTo($user->email)
                    ->setSubject('Оплата не прошла')
                    ->send();
                    Yii::warning('Оплата не прошла. Введен код протекции');
                    exit;
                }
                // в БД нет чека или оплата по нему уже прошла
                if ( !$dbPay ) {
                    Yii::warning('Оплата не прошла. В БД нет счета или он уже оплачен');
                    exit;
                }
                // перевод статуса чека в "оплачен"
                $dbPay->pay_add_sum = Yii::$app->params['minRefill']; // чтобы избежать проблем с валидацией
                $dbPay->paid = 1;
                $dbPay->update();
                // пополнение баланса пользователя
                $dbBalance = Balance::findOne(['user_id' => $dbPay->user_id]);
                $dbBalance->balance = $dbBalance->balance + $yaResponse['withdraw_amount'] * Yii::$app->params['payMultiplier'];
                $dbBalance->update();
                // уведомление об успешной оплате
                Yii::$app->mailer->compose('successPay', [
                        'username' => $user->username,
                        'sum' => $yaResponse['withdraw_amount']
                    ])
                    ->setFrom(Yii::$app->params['supportEmail'])
                    ->setTo($user->email)
                    ->setSubject('Успешная оплата')
                    ->send();
            }
        }


        /*Array
        (
            [notification_type] => p2p-incoming
            [amount] => 9.95
            [datetime] => 2019-08-12T15:57:33Z
            [codepro] => false
            [withdraw_amount] => 10.00
            [sender] => 410017991386716
            [sha1_hash] => 156c91099db8b9377ed3956c2a1e9efbab7842d0
            [unaccepted] => false
            [operation_label] => 24e3a01b-0011-5000-8000-1e85866a1af2
            [operation_id] => 618940653245123008
            [currency] => 643
            [label] => reked:10148
        )*/


        /*// $merchantPassword1 = "QWexJgC63iUsK8qFn1E6";//пароль для тестовых платежей №1. Заменить
        $merchantPassword2 = "J75g7pDQDt1uS0UnruFS";//пароль для тестовых платежей №2. Заменить

        $sum = Yii::$app->request->get('OutSum');
        $invId = Yii::$app->request->get('InvId');
        $hash = strtoupper( Yii::$app->request->get('SignatureValue') );

        // формируем хэш для сравнения
        // $myHash1 = strtoupper( md5("$sum:$invId:$merchantPassword1") );
        $myHash2 = strtoupper( md5("$sum:$invId:$merchantPassword2") );

        // проверка хэша
        // if ( ($myHash1 != $hash) == ($myHash2 != $hash) ) return 'bad signal 1';
        if ( $myHash2 != $hash ) return 'bad signal 1';
        
        $dbPay = Payment::find()
        ->where(['id' => $invId])
        ->andWhere(['paid' => 0])
        ->one();

        // в БД нет счета или он уже оплачен
        if ( !$dbPay ) return 'bad signal 2';

        $dbPay->paid = 1;
        $dbPay->update();

        $dbBalance = Balance::find()->where(['user_id' => $dbPay->user_id])->one();
        $dbBalance->balance = $dbBalance->balance + $sum * Yii::$app->params['payMultiplier'];
        $dbBalance->update();

        return 'OK'.$invId;*/
    }

    public function actionCouponApply($coupon) {
        if ( strlen($coupon) > 6 ) return 'error-length';
        $dbCoupon = Coupon::find()
            ->where(['coupon' => $coupon])
            ->andWhere(['used' => 0])
            ->one();
        if ( !$dbCoupon ) return 'error-coupon';
        $dbCoupon->used = 1;
        $dbCoupon->user_id = Yii::$app->user->getId();
        $dbCoupon->update();
        $dbBalance = Balance::findOne(['user_id' => Yii::$app->user->getId()]);
        $dbBalance->balance = $dbBalance->balance + $dbCoupon->value;
        $dbBalance->update();
        $dbPay = new Payment(['scenario' => 'saveToDb']);
        $dbPay->user_id = Yii::$app->user->getId();
        $dbPay->sum = $dbCoupon->value;
        $dbPay->operation = 'plus';
        $dbPay->pay_type = 'coupon';
        $dbPay->paid = 1;
        $dbPay->save();
        return 'success';
    }

    public function actionChangeTariff($change_tariff = false){

        $tariffUser = AuthAssignment::find()->where(['user_id' => Yii::$app->user->getId()])->one();

        if ( Yii::$app->request->isAjax ) {
            // return 'ok';
            $costTariff = Yii::$app->params['costTariff'][$change_tariff] * Yii::$app->params['payMultiplier'];
            //пересчет остатка
            $today = new DateTime();
            $updTariffUser = new DateTime();
            $updTariffUser->setTimestamp($tariffUser->updated_at);
            $interval = $today->diff($updTariffUser)->format('%a');
            if ( $interval < Yii::$app->params['checkInterval']['prolongation'] ) {
                $sumReturn = (Yii::$app->params['checkInterval']['prolongation'] - $interval) *
                    round(Yii::$app->params['costTariff'][$tariffUser->item_name] /
                    Yii::$app->params['checkInterval']['prolongation'] ) * Yii::$app->params['payMultiplier'];
            } else {
                $sumReturn = 0;
            }
            //проверка средств на счету
            $dbBalance = Balance::find()->where(['user_id' => Yii::$app->user->getId()])->one();
            $balance = $dbBalance->balance + $sumReturn;
            if ( $balance < $costTariff ) {
                //денег не хватает
                return 'error-money';
            }
            //денег хватает
            //запись операции возврата остатка
            if ( $sumReturn !== 0 ){
                $dbPay = new Payment(['scenario' => 'saveToDb']);
                $dbPay->user_id = Yii::$app->user->getId();
                $dbPay->sum = $sumReturn;
                $dbPay->operation = 'return';
                $dbPay->paid = 1;
                $dbPay->pay_type = 'undefined';
                $dbPay->save();
            }

            //запись операции списания средств
            $dbPay = new Payment(['scenario' => 'saveToDb']);
            $dbPay->user_id = Yii::$app->user->getId();
            $dbPay->sum = $costTariff;
            $dbPay->operation = 'minus';
            $dbPay->paid = 1;
            $dbPay->pay_type = 'undefined';
            $dbPay->save();

            //изменение баланса
            $dbBalance->balance = $dbBalance->balance + $sumReturn - $costTariff;
            $dbBalance->update();

            //обнуление количества переходов
            $dbTrafficNumber = TrafficNumber::find()->where(['user_id' => Yii::$app->user->getId()])->one();
            $dbTrafficNumber->traffic_number = 0;
            $dbTrafficNumber->update();

            //изменение тарифа
            $tariffUser->item_name = $change_tariff;
            $tariffUser->update();

            //включение активности тарифа
            $dbUser = User::find()->where(['id' => Yii::$app->user->getId()])->one();
            $dbUser->tariff_activity = 1;
            $dbUser->update();

            // массив с инф-цией по отключению доменов, страниц или биддеров
            $delDPB = [];
            //проверка количества включенных доменнов (мультилендинг) текущего тарифа и выбранного
            $dbEnabledDomains = EnabledDomain::find()->where(['user_id' => Yii::$app->user->getId()])->all();
            if ( count($dbEnabledDomains) > Yii::$app->params['permitedDomainNumber'][$change_tariff] ){
                foreach ($dbEnabledDomains as $dbEnabledDomain) {
                    $dbEnabledDomain->delete();
                }
                $delDPB[] = 'd';
            }
            //проверка количества включенных страниц (геолендинг) текущего тарифа и выбранного
            $dbEnabledPages = GeoPage::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['enabled' => 1])->all();
            if ( count($dbEnabledPages) > Yii::$app->params['permitedPageNumber'][$change_tariff] ){
                foreach ($dbEnabledPages as $dbEnabledPage) {
                    $dbEnabledPage->enabled = 0;
                    $dbEnabledPage->update();
                }
                $delDPB[] = 'p';
            }
            //проверка количества включенных биддеров текущего тарифа и выбранного
            $dbEnabledBidders = YandexBidder::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['status' => 'enabled'])->all();
            if ( count($dbEnabledBidders) > Yii::$app->params['permitedBidderNumber'][$change_tariff] ){
                foreach ($dbEnabledBidders as $dbEnabledBidder){
                    $dbEnabledBidder->status = 'disabled';
                    $dbEnabledBidder->update(false);
                }
                $delDPB[] = 'b';
            }

            if ( empty($delDPB) ) {
                return 'ok';
            } else {
                return 'del-' . implode('', $delDPB);
            }

            /*if ( isset($offDomain) && isset($offBidder) ) return 'off_all';
            if ( isset($offDomain) ) return $offDomain;
            if ( isset($offBidder) ) return $offBidder;

            return 'ok';*/
        }

        $tariffUser = $tariffUser->item_name;

        \app\assets\PaymentAsset::register($this->view);
        return $this->render('change-tariff', compact ('tariffUser'));
    }

    public function actionPersonalArea() {
        \app\assets\PaymentAsset::register($this->view);
        return $this->render('personal-area', compact('passChange'));
    }

    //смена пароля
    public function actionPasswordChange(){
        $user = User::find()->where(['id' => Yii::$app->user->getId()])->one();
        $model = new PasswordChangeForm($user);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->changePassword()) {
                return $this->redirect(['/payment/personal-area', 'passChange' => 'done' ]);
            } else {
                return $this->redirect(['/payment/personal-area', 'passChange' => 'error']);
            }
        } else {
            return $this->renderAjax('password-change', compact('model'));
        }
    }

    // вкл/выкл отображения видео-подсказок
    public function actionDisplayVideoTip() {
        if (!Yii::$app->request->isAjax) exit;
        $dbUser = User::findOne([Yii::$app->user->getId()]);
        $dbUser->video_tip = !$dbUser->video_tip;
        $dbUser->update();
        return $dbUser->video_tip;
    }

    // получение сообщений и продление тарифа
    public function actionPersonalOperation($status, $operation){
        if (!Yii::$app->request->isAjax) exit;
        $dbUser = User::find()->where(['id' => Yii::$app->user->getId()])->one();
        $dbUser->$operation = $status;
        $dbUser->update();
    }

    //продление тарифа
    public function actionTariffProlongation(){
        if (!Yii::$app->request->isAjax) exit;

        //определяем переменные
        $tariffUser = AuthAssignment::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        $costTariff = Yii::$app->params['costTariff'][$tariffUser->item_name] * Yii::$app->params['payMultiplier'];
        $today = new DateTime();

        //пересчет остатка
//        $today = new DateTime();
//        $updTariffUser = new DateTime();
//        $updTariffUser->setTimestamp($tariffUser->updated_at);
//        $interval = $today->diff($updTariffUser)->format('%a');

        //проверка средств на счету
        $dbBalance = Balance::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        if ( $dbBalance->balance < $costTariff ) {
            //денег не хватает
            return 'error-money';
        }
        //денег хватает

        //запись операции списания средств
        $dbPay = new Payment(['scenario' => 'saveToDb']);
        $dbPay->user_id = Yii::$app->user->getId();
        $dbPay->sum = $costTariff;
        $dbPay->operation = 'minus';
        $dbPay->paid = 1;
        $dbPay->pay_type = 'undefined';
        $dbPay->save();

        //изменение баланса
        $dbBalance->balance = $dbBalance->balance - $costTariff;
        $dbBalance->update();

        //обнуление количества переходов
        $dbTrafficNumber = TrafficNumber::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        $dbTrafficNumber->traffic_number = 0;
        $dbTrafficNumber->update();

        //изменение даты активации тарифа
        $tariffUser->updated_at = $today->format('U');
        $tariffUser->update();

        //включение активности тарифа
        $dbUser = User::find()->where(['id' => Yii::$app->user->getId()])->one();
        $dbUser->tariff_activity = 1;
        $dbUser->update();

        return 'ok';
    }

    // заказ настройки сервиса
    public function actionOrderSet() {
        if (!Yii::$app->request->isAjax) exit;
        $dbBalance = Balance::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        if ( $dbBalance->balance < Yii::$app->params['orderSetCost'] * Yii::$app->params['payMultiplier'] ) {
            //денег не хватает
            return 'error-money';
        }
        //денег хватает
        //запись операции списания средств
        $dbPay = new Payment(['scenario' => 'saveToDb']);
        $dbPay->user_id = Yii::$app->user->getId();
        $dbPay->sum = Yii::$app->params['orderSetCost'] * Yii::$app->params['payMultiplier'];
        $dbPay->operation = 'minus';
        $dbPay->paid = 1;
        $dbPay->pay_type = 'undefined';
        $dbPay->save();

        //изменение баланса
        $dbBalance->balance = $dbBalance->balance - Yii::$app->params['orderSetCost'] * Yii::$app->params['payMultiplier'];
        $dbBalance->update();

        // уведомление на почту о необходимости позвонить клиенту
        Yii::$app->mailer->compose('orderSetNotification', ['user' => Yii::$app->user->identity])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name.' (отправлено роботом).'])
            ->setTo(Yii::$app->params['supportEmail'])
            ->setSubject('Заказ настройки сервиса')
            ->send();

        return 'ok';
    }
}