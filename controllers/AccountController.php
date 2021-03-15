<?php

namespace app\controllers;

use Yii;
use app\components\YandexApi;
use app\components\GoogleApi;
use app\models\YandexAccount;
use app\models\GoogleAccount;

class AccountController extends AppController {

    public function actionIndex() {
        $userId = Yii::$app->user->getId();
        $yaAccounts = YandexAccount::findAll(['user_id' => $userId]);
        $gAccounts = GoogleAccount::findAll(['user_id' => $userId]);
//        debug ( \yii\helpers\Url::base(true) );

        \app\assets\AccountAsset::register($this->view);
        return $this->render('index', compact('yaAccounts', 'gAccounts'));
    }

    public function actionGetYandexToken () {

        if ( Yii::$app->request->get('code') ) {
            // Формируем тело POST-запроса с указанием кода подтверждения
            $query = [
                'grant_type' => 'authorization_code',
                'code' => Yii::$app->request->get('code'),
                'client_id' => Yii::$app->params['yandexOAuthParam']['id'],
                'client_secret' => Yii::$app->params['yandexOAuthParam']['secret'],
            ];
            $query = http_build_query($query);

            // Формируем заголовки POST-запроса
            $header = "Content-type: application/x-www-form-urlencoded";

            // Выполняем POST-запрос и получаем/декодируем результат
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => $header,
                    'content' => $query
                ]
            ];
            // debug ($opts);
            $context = stream_context_create($opts);
            $result = file_get_contents('https://oauth.yandex.ru/token', false, $context);
            $result = json_decode($result);

            $api = new YandexApi($result->access_token);
            $apiAcc = $api->requestAccount();
            
            /*// если аккаунт уже сохранен в БД, выдаем ошибку, закрываем окно
            if ( YandexAccount::find()->where(['account_id' => $apiAcc->ClientId])->exists() )
				return "<script>alert('Этот аккаунт уже добавлен в систему'); window.close();</script>";*/

            // если аккаунт уже сохранен в БД, обновляем его токен, если нет сохраняем его
            if ( $updateAcc = YandexAccount::find()->where(['account_id' => $apiAcc->ClientId])->one() ) {
                if ( $updateAcc->user_id != Yii::$app->user->getId() )
                    return "<script>alert('Этот аккаунт уже добавлен в систему'); window.close();</script>";
                $updateAcc->access_token = $result->access_token;
                $updateAcc->expires_in = $result->expires_in;
                $updateAcc->refresh_token = $result->refresh_token;
                $updateAcc->update();
            } else {
                $dbAcc = new YandexAccount();
                $dbAcc->user_id = Yii::$app->user->getId();
                $dbAcc->account_id = $apiAcc->ClientId;
                $dbAcc->login = $apiAcc->Login . "@yandex.ru";
                $dbAcc->access_token = $result->access_token;
                $dbAcc->expires_in = $result->expires_in;
                $dbAcc->refresh_token = $result->refresh_token;
                $dbAcc->save();
            }
            return "<script>window.close();</script>";
        }

        // Если скрипт был вызван без указания параметра "code",
        // пользователю отображается ссылка на страницу запроса доступа
        else
            return $this->renderAjax('yandex-access-request');

    }

    public function actionGetGoogleToken() {
		// логика сохранения аккаунтов внутри mcc-аккаунта
        if ( Yii::$app->request->get('managerId') ) {
            $accIds = explode('-', Yii::$app->request->get('accIds'));
            $dbMcc = GoogleAccount::find()->where(['account_id' => Yii::$app->request->get('managerId')])->one();
            foreach ( $accIds as $accId ) {
                $dbAcc = new GoogleAccount();
                $dbAcc->user_id = Yii::$app->user->getId();
                $dbAcc->account_id = $accId;
                $dbAcc->login = $dbMcc->login;
                $dbAcc->refresh_token = $dbMcc->refresh_token;
                $dbAcc->save();
            }
		// основная логика сохранения аккаунтов
        } else {
            $api = new GoogleApi();
            $apiAcc = $api->requestAccountInfo();
            
            // логика проверки наличия аккаунта в БД
            // для простого аккаунта проверяется наличие в БД id загружаемого аккаунта
            if ($accId = $apiAcc->accId) {
				if ( GoogleAccount::find()->where(['account_id' => $accId])->exists() )
					return "<script>alert('Этот аккаунт уже добавлен в систему другим пользователем'); window.close();</script>";
			}
			// для mcc-аккаунта проверяется соответствие пользователя загруженного акк-та с текущим пользователем
			if ($accId = $apiAcc->mccAcc->managerId) {
				if ( GoogleAccount::find()->where(['account_id' => $accId])->exists() ) {
					if ( Yii::$app->user->getId() != GoogleAccount::find()->where(['account_id' => $accId])->one()->user_id )
						return "<script>alert('Этот аккаунт уже добавлен в систему другим пользователем'); window.close();</script>";
				}
			}

			// сохраняем аккаунт в БД
			// повторная попытка сохр-ния mcc-аккаунта не пройдет валидацию
			$dbAcc = new GoogleAccount();
			$dbAcc->user_id = Yii::$app->user->getId();
			$dbAcc->account_id = $apiAcc->accId ? $apiAcc->accId : $apiAcc->mccAcc->managerId;
			$dbAcc->login = $apiAcc->email;
			$dbAcc->refresh_token = $api->getRefreshToken();
			$dbAcc->mcc = !(boolean)$apiAcc->accId;
			$dbAcc->save();

			// вид выбора аккаунта внутри mcc-аккаунта
            if ( !$apiAcc->accId ) {
                return $this->renderAjax('google-choose-account', [
					'apiAcc' => $apiAcc,
					'dbAccs' => GoogleAccount::find()->where(['login' => $apiAcc->email])->andWhere(['mcc' => false])->all()
                ]);
            }
        }
        // закрытие окна при успешном сохранении
        return "<script>window.close();</script>";
    }

    public function actionDelYaAccount($ya_account) {
        if ( !Yii::$app->request->isAjax ) exit;
        YandexAccount::find()->where(['account_id' => $ya_account])->one()->delete();
        return 'Аккаунт удален';
    }

    public function actionDelGAccount($g_account) {
        if ( !Yii::$app->request->isAjax ) exit;
        $dbAcc = GoogleAccount::find()->where(['account_id' => $g_account])->one();
        $allDbAccs = GoogleAccount::find()->where(['login' => $dbAcc->login])->all();
        /**
         * если кол-во акк-ов больше 1 - этот аккаунт принадлежит MCC-аккаунту
         * если кол-во акк-ов 2 - надо удалить аккаунт и MCC-аккаунт
         */
        if ( count($allDbAccs) === 2 )
			GoogleAccount::deleteAll(['login' => $dbAcc->login]);
        $dbAcc->delete();
        return 'Аккаунт удален';
    }

}
