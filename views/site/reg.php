<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\widgets\MaskedInput;

/* @var $this yii\web\View */
/* @var $model app\models\user\RegForm */
/* @var $form ActiveForm */
?>
<div class="main-reg">

    <div class="jumbotron">

        <h1>Регистрация</h1>
        <h4>Чтобы воспользоваться бесплатным тестовым периодом, зарегистрируйтесь!</h4>
        <hr>

        <?php $form = ActiveForm::begin(); ?>

        <div class="reg-form">
            <?= $form->field($model, 'username')
                ->textInput(['placeholder' => 'Ваше имя *', 'style'=>'height:46px'])->label(false) ?>
            <?= $form->field($model, 'email')
                ->textInput(['placeholder' => 'Эл. почта *', 'style'=>'height:46px'])->label(false) ?>
            <?= $form->field($model, 'phone_number')
                ->widget(MaskedInput::className(), [
                    'mask' => '+7 (999) 999 9999',
                ])
                ->textInput(['placeholder' => 'Номер телефона *', 'style'=>'height:46px'])->label(false) ?>
            <?php /*
            <?= $form->field($model, 'password')
                ->passwordInput(['placeholder' => 'Пароль *', 'style'=>'height:46px'])->label(false) ?>
            <?= $form->field($model, 'password_verification')
                ->passwordInput(['placeholder' => 'Повторите пароль *', 'style'=>'height:46px'])->label(false) ?>
            */ ?>
        </div>

        <div class="form-group">
            <?= Html::submitButton('Зарегистрироваться', ['class' => 'btn btn-success reg-btn']) ?>
        </div>
        <?php ActiveForm::end(); ?>

        <div class="alr-reg">
            <?php if($model->scenario === 'emailActivation'): ?>
                <div><i>* На указанную эл.почту будет отправлено письмо для активации аккаунта.</i></div><br>
            <?php endif; ?>
			<span>Я уже зарегистрирован.</span>
			<a href="<?= Url::to(['/site/login']) ?>">Войти</a>
        </div>

    </div>

</div><!-- main-reg -->
