<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\LoginForm */
/* @var $form ActiveForm */
?>
<div class="main-login">

    <div class="jumbotron">

        <h1>Вход</h1>
        <hr>

        <div class="log-form">
            <?php $form = ActiveForm::begin(); ?>

            <?php if($model->scenario === 'loginWithEmail'): ?>
                <?= $form->field($model, 'email')
                    ->textInput(['placeholder' => 'Ваш e-mail', 'style'=>'height:46px'])->label(false) ?>
            <?php else: ?>
                <?= $form->field($model, 'username')
                    ->textInput(['placeholder' => 'Ваш ник', 'style'=>'height:46px'])->label(false) ?>
            <?php endif; ?>
            <?= $form->field($model, 'password')
                ->passwordInput(['placeholder' => 'Пароль', 'style'=>'height:46px'])->label(false) ?>
			<?php /*= $form->field($model, 'rememberMe')->checkbox() */ ?>

            <div class="form-group">
                <?= Html::submitButton('Войти', ['class' => 'btn btn-success log-btn']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        
        <div class="alr-log">
			<span>Я еще не зарегистрирован.</span>
			<a href="<?= Url::to(['/site/reg']) ?>">Регистрация</a>
        </div>
        <div class="no-pswrd">
            <a href="<?= Url::to(['/site/send-email']) ?>">Забыли пароль?</a>
        </div>

    </div>

</div><!-- main-login -->
