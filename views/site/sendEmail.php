<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\SendEmailForm */
/* @var $form ActiveForm */
?>
<div class="site-sendEmail">

    <div class="jumbotron">

        <div style="text-align: left">Укажите e-mail, указанный при регистрации. На него придет ссылка для восстановления пароля</div>
        <hr>

        <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model, 'email')
                ->textInput(['placeholder' => 'e-mail', 'style'=>'height:46px'])->label(false) ?>

            <div class="form-group">
                <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary em-btn']) ?>
            </div>
        <?php ActiveForm::end(); ?>

    </div>

</div><!-- site-sendEmail -->
