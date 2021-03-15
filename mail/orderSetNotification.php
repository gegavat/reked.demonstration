<?php
/**
 * @var $this yii\web\View
 * @var $user app\models\user\User
 */

echo 'Заказ на настройку сервиса от пользователя "' . $user->email . '"';
echo '<br>';
echo 'Время Заказа: ' . date('d.m.Y H:i');