<?php
namespace app\models;

use Yii;
use yii\base\Model;

class SupportContact extends Model {
    public $name, $email, $subject, $body, $verifyCode;

    public function rules()
    {
        return [
            [ ['name', 'email', 'subject', 'body'], 'required'],
            ['email', 'email'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Имя',
            'email' => 'Электронная почта',
            'subject' => 'Тема',
            'body' => 'Сообщение',
        ];
    }

    public function contact()
    {
        if ( $this->validate() ) {
            Yii::$app->mailer->compose('supportContact', [
                    'name' => $this->name,
                    'email' => $this->email,
                    'subject' => $this->subject,
                    'body' => $this->body
                ])
                ->setFrom(Yii::$app->params['supportEmail'])
                ->setTo(Yii::$app->params['supportEmail'])
                ->setSubject('Обращение в ТехПоддержку my_reked_ru')
                ->send();
            return true;
        } else {
            return false;
        }
    }
}