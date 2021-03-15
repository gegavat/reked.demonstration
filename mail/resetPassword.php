<?php
/**
 * Created by PhpStorm.
 * User: илья
 * Date: 22.12.2018
 * Time: 1:01
 * @var $user \app\models\User
 */

use yii\helpers\Html;

echo 'Здравствуйте, '.Html::encode($user->username).'. ';

echo Html::a('Для смены пароля перейдите по этой ссылке.',
    Yii::$app->urlManager->createAbsoluteUrl(
        [
            '/site/reset-password',
            'key' => $user->secret_key
        ]
    ));