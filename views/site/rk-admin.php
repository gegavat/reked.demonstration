<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\user\LoginForm */
/* @var $form ActiveForm */
?>
<div class="main-login">

    <div class="jumbotron">

        <h1>Администрирование</h1>
        <hr>

        <div class="log-form">
            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model, 'username')
                    ->textInput(['placeholder' => 'Ник администратора', 'style'=>'height:46px'])->label(false) ?>

            <?= $form->field($model, 'password')
                ->passwordInput(['placeholder' => 'Пароль', 'style'=>'height:46px'])->label(false) ?>

            <div class="form-group">
                <?= Html::submitButton('Войти', ['class' => 'btn btn-success log-btn']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>

    </div>

</div><!-- main-login -->
