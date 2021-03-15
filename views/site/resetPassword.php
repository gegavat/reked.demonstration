<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ResetPasswordForm */
/* @var $form ActiveForm */
?>
<div class="site-resetPassword">

    <div style="text-align: left">Введите новый пароль</div>
    <hr>

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'password')
            ->passwordInput(['placeholder' => 'Пароль', 'style'=>'height:46px'])->label(false) ?>
    
        <div class="form-group">
            <?= Html::submitButton('Изменить', ['class' => 'btn btn-primary em-btn']) ?>
        </div>
    <?php ActiveForm::end(); ?>

</div><!-- site-resetPassword -->
