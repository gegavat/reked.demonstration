<?php

namespace app\controllers;

use app\components\BitrixApi;
use app\models\GeoPage;
use app\models\SupportContact;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\user\RegForm;
use app\models\user\LoginForm;
use app\models\user\User;
use app\models\user\SendEmailForm;
use app\models\user\ResetPasswordForm;
use app\models\user\AccountActivation;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\data\ActiveDataProvider;
use app\models\AuthAssignment;
use app\models\EnabledDomain;
use app\models\TrafficNumber;
use app\models\YandexBidder;
use DateTime;
use DateInterval;

class SiteController extends AppController {

    // public function beforeAction($action) {
    //     // если пользователь авторизован, не пускать его на указ. список экшенов
    //     if ( in_array($action->id, Yii::$app->params['listGuestAllow']) ) {
    //         if ( !Yii::$app->user->isGuest )
    //             return $this->goHome();
    //     }
    //     return parent::beforeAction($action);
    // }

    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
                'view' => '@vendor/yiister/yii2-gentelella/views/error.php',
                'layout' => 'auth'
            ]
        ];
    }

    public function actionRkAdmin() {
        $model = new LoginForm();
        if ( $model->load(Yii::$app->request->post()) && $model->loginAdmin() )
            return $this->redirect(Url::to(['/site/rk-admin-list']));
        $this->layout = 'auth';
        return $this->render('rk-admin', compact('model'));
    }

    public function actionRkAdminList() {
        if ( Yii::$app->user->getId() !== 1 )
            return $this->goHome();
        if ( $userId = Yii::$app->request->get('id') ) {
            Yii::$app->user->login(User::findIdentity($userId));
            return $this->goHome();
        }
        $dataProvider = new ActiveDataProvider([
            'query' => User::find()->where(['!=','id', 1]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        $this->layout = 'auth';
        return $this->render('rk-admin-list', compact('dataProvider'));
    }


    public function actionIndex() {
        $userId = Yii::$app->user->getId();
        //тариф
        $tariff = AuthAssignment::find()->where(['user_id' => $userId])->one();
        $tariffName = $tariff->item_name;
        //срок действия тарифа и сколько дней рабоает тариф
        $today = new DateTime();
        $updTariffUser = new DateTime();
        $updTariffUser->setTimestamp($tariff->updated_at);
        $interval = $today->diff($updTariffUser)->format('%a');
        $validity = $updTariffUser->add(new DateInterval('P30D'))->format('U');
        //домены (мультилендинг): активно / доступно
        $enabledDomain = EnabledDomain::find()->where(['user_id' => $userId])->count();
        $availableDomain = Yii::$app->params['permitedDomainNumber'][$tariffName];
        //страницы (геолендинг): активно / доступно
        $enabledPage = GeoPage::find()->where(['user_id' => $userId])->andWhere(['enabled' => 1])->count();
        $availablePage = Yii::$app->params['permitedPageNumber'][$tariffName];
        //количество переходов активно / доступно
        $traffic = TrafficNumber::find()->where(['user_id' => $userId])->one();
        $traffic = $traffic->traffic_number;
        $availableTraffic = Yii::$app->params['permitedTrafficNumber'][$tariffName];
        //количество биддеров активно / доступно
        $enabledBidder = YandexBidder::find()->where(['user_id' => $userId])->andWhere(['status' => 'enabled'])->count();
        $availableBidder = Yii::$app->params['permitedBidderNumber'][$tariffName];

        return $this->render('index', compact(
            'tariffName',
            'validity',
            'interval',
            'enabledDomain',
            'availableDomain',
            'enabledPage',
            'availablePage',
            'traffic',
            'availableTraffic',
            'enabledBidder',
            'availableBidder'
        ));
    }

    public function actionReg() {
        $emailActivation = Yii::$app->params['emailActivation'];
        $model = $emailActivation ? new RegForm(['scenario' => 'emailActivation']) : new RegForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()):
            if ($user = $model->reg()):
                if ($user->status === User::STATUS_ACTIVE):
                    if (Yii::$app->getUser()->login($user)):
                        return $this->goHome();
                    endif;
                else:
                    if($model->sendActivationEmail($user)):
                        Yii::$app->session->setFlash('success', 'Письмо с активацией отправлено на e-mail <strong>'.Html::encode($user->email).'</strong> (проверьте папку спам).');
                        $model->regNotification($user);
                    else:
                        Yii::$app->session->setFlash('error', 'Ошибка. Письмо не отправлено.');
                        Yii::error('Ошибка отправки письма.');
                    endif;
                    return $this->redirect(['site/reg']);
                    // return $this->refresh();
                endif;
            else:
                Yii::$app->session->setFlash('error', 'Возникла ошибка при регистрации.');
                Yii::error('Ошибка при регистрации');
                return $this->redirect(['site/reg']);
                // return $this->refresh();
            endif;
        endif;
        $this->layout = 'auth';
        return $this->render(
            'reg',
            [
                'model' => $model
            ]
        );
    }

    public function actionActivateAccount($key) {
        try {
            $user = new AccountActivation($key);
        }
        catch(InvalidParamException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            Yii::error($e->getMessage());
            return $this->redirect(Url::to(['/site/login']));
//            throw new BadRequestHttpException($e->getMessage());
        }
        if($user->activateAccount()):
            Yii::$app->session->setFlash('success', 'Активация прошла успешно. <strong>'.Html::encode($user->username).',</strong> вы зарегистрированы!');
            $activatedUser = User::findIdentity($user->getUserId());
            Yii::$app->user->login($activatedUser);
            // bitrix crm - new lead
//            BitrixApi::addLead();
            return $this->redirect(Url::to(['/site/index']));
        else:
            Yii::$app->session->setFlash('error', 'Ошибка активации.');
            Yii::error('Ошибка при активации.');
            return $this->redirect(Url::to(['/site/login']));
        endif;
    }

    public function actionLogin() {
        $loginWithEmail = Yii::$app->params['loginWithEmail'];
        $model = $loginWithEmail ? new LoginForm(['scenario' => 'loginWithEmail']) : new LoginForm();

        if ( $model->load(Yii::$app->request->post()) && $model->login() )
            return $this->goBack();

        $this->layout = 'auth';
        return $this->render('login', compact('model'));
    }
    
    public function actionLogout() {
		Yii::$app->user->logout();
        return $this->redirect('//reked.ru');
    }

    public function actionAbout() {
        return $this->render('about');
    }

    public function actionSendEmail() {
        $model = new SendEmailForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                if($model->sendEmail()):
                    Yii::$app->getSession()->setFlash('warning', 'Проверьте e-mail.');
                    return $this->goHome();
                else:
                    Yii::$app->getSession()->setFlash('error', 'Нельзя сбросить пароль.');
                endif;
            }
        }
        $this->layout = 'auth';
        return $this->render('sendEmail', [
            'model' => $model,
        ]);
    }

    public function actionResetPassword($key) {
        try {
            $model = new ResetPasswordForm($key);
        }
        catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate() && $model->resetPassword()) {
                Yii::$app->getSession()->setFlash('warning', 'Пароль изменен.');
                return $this->redirect(['/site/login']);
            }
        }
        $this->layout = 'auth';
        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    public function actionSupportContact() {
        $model = new SupportContact();
        /* получаем данные из формы и запускаем функцию отправки contact, если все хорошо, выводим сообщение об удачной отправке сообщения на почту */
        if ( $model->load(Yii::$app->request->post()) && $model->contact() ) {
            Yii::$app->session->setFlash('contactFormSubmitted');
            return $this->refresh();
            /* иначе выводим форму обратной связи */
        } else {
            return $this->render('support-contact', [
                'model' => $model,
            ]);
        }

    }

}
