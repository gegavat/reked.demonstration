<?php
/**
 * @var $this yii\web\View
 * @var $user app\models\user\User
 */
use yii\helpers\Html;

echo 'На сайте my.reked.ru зарегистрировался новый пользователь "' . $user->username . '"';
echo '<br>';
echo 'Время регистрации: ' . date('d.m.Y H:i', $user->created_at);