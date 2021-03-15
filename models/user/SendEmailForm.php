<?php

namespace app\models\user;

use Yii;
use yii\base\Model;

class SendEmailForm extends Model {

    public $email;

    public function rules() {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'exist',
                'targetClass' => User::className(),
                'filter' => [
                    'status' => User::STATUS_ACTIVE
                ],
                'message' => 'Данный емайл не зарегистрирован.'
            ],
        ];
    }

    public function attributeLabels() {
        return [
            'email' => 'e-mail'
        ];
    }

    public function sendEmail()
    {
        /* @var $user User */
        $user = User::findOne(
            [
                'status' => User::STATUS_ACTIVE,
                'email' => $this->email
            ]
        );
        if($user):
            $user->generateSecretKey();
            if($user->save()):
                return Yii::$app->mailer->compose('resetPassword', ['user' => $user])
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name.' (отправлено роботом)'])
                    ->setTo($this->email)
                    ->setSubject('Сброс пароля для '.Yii::$app->name)
                    ->send();
            endif;
        endif;
        return false;
    }

}