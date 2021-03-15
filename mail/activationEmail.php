<?php
/**
 * @var $this yii\web\View
 * @var $user app\models\user\User
 * @var $password string
 */
use yii\helpers\Html;

echo 'Здравствуйте, '.Html::encode($user->username).'. ';
echo Html::a('Для активации аккаунта перейдите по этой ссылке.',
    Yii::$app->urlManager->createAbsoluteUrl(
        [
            '/site/activate-account',
            'key' => $user->secret_key
        ]
    ));

echo '<br><br>';
echo 'Ваши данные для входа в систему:';
echo '<br>';
echo 'E-mail: ' . $user->email;
echo '<br>';
echo 'Пароль: ' . $password;