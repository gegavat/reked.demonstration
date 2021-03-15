<?php

namespace app\models\user;

use yii\base\Model;
use Yii;

class RegForm extends Model {

    public $username;
    public $email;
    public $phone_number;
    public $status;

    protected $password;

    public function rules() {
        return [
            [['username', 'email', 'phone_number'], 'trim'],
            [['username', 'email', 'phone_number'],'required'],
            ['phone_number', 'checkPhoneNumber'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            // ['username', 'unique',
            //     'targetClass' => User::className(),
            //     'message' => 'Это имя уже занято.'],
            ['email', 'email'],
            ['email', 'unique',
                'targetClass' => User::className(),
                'message' => 'Эта почта уже занята.'],
            ['status', 'default', 'value' => User::STATUS_ACTIVE, 'on' => 'default'],
            ['status', 'in', 'range' =>[
                User::STATUS_NOT_ACTIVE,
                User::STATUS_ACTIVE
            ]],
            ['status', 'default', 'value' => User::STATUS_NOT_ACTIVE, 'on' => 'emailActivation']
        ];
    }

    public function checkPhoneNumber($attribute, $params)
    {
        // проверка на введение номера целиком
        if ( stripos($this->$attribute, '_') )
            $this->addError($attribute, 'Введен некорректный номер телефона');
        // проверка на начало маски (антиспам - боты вводят просто цифры)
        if ( stripos($this->$attribute, '+7') !== 0 )
            $this->addError($attribute, 'Неправильный формат номера');
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Ваше имя',
            'email' => 'Эл. почта',
            'phone_number' => 'Номер телефона',
        ];
    }

    public function reg() {
        $this->password = genPassword();
        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->phone_number = $this->phone_number;
        $user->status = $this->status;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->user_hash = getRandomString(); // добавляется для использования в js-скрипте, кот. отдается клиентам
        // $user->tariff_activity = 0; // добавляется по умолчанию через правила валидации модели User
        // $user->prolongation = 1;    // добавляется по умолчанию через правила валидации модели User
        // $user->send_message = 1;    // добавляется по умолчанию через правила валидации модели User
        // $user->video_tip = 1;       // добавляется по умолчанию через правила валидации модели User
        if($this->scenario === 'emailActivation')
            $user->generateSecretKey();
        return $user->save() ? $user : null;
    }

    public function sendActivationEmail($user) {
        return Yii::$app->mailer->compose('activationEmail', ['user' => $user, 'password' => $this->password])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name.' (отправлено роботом).'])
            ->setTo($this->email)
            ->setSubject('Активация для '.Yii::$app->name)
            ->send();
    }

    public function regNotification($user) {
        return Yii::$app->mailer->compose('regNotification', ['user' => $user])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name.' (отправлено роботом).'])
            ->setTo(Yii::$app->params['supportEmail'])
            ->setSubject('Успешная регистрация нового пользователя')
            ->send();
    }

}
