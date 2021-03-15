<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\SupportContact */

$this->title = 'Связь с техподдержкой';
?>

<div class="col-xs-12 col-lg-6">

    <div class="row">
        <h2>Связь с техподдержкой Reked</h2>

        <?php if (Yii::$app->session->hasFlash('contactFormSubmitted')): ?>

            <div class="alert alert-success alert-dismissible fade in" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                Спасибо за обращение. Ответим вам в ближайшее время!
            </div>

        <?php else: ?>

            <?php $form = ActiveForm::begin([
                'id' => 'support-form',
                'options' => ['class' => 'form-horizontal'], /* класс формы */
                /* 'fieldConfig' => [
                    'template' => "<div class=\"col-lg-3\">{label}</div>\n<div class=\"col-lg-9\">{input}</div>\n<div class=\"col-lg-12 col-lg-offset-3 \">{error}</div>"
                ], */
            ]); ?>
            <?= $form->field($model, 'name') ?>
            <?= $form->field($model, 'email') ?>
            <?= $form->field($model, 'subject') ?>
            <?= $form->field($model, 'body')->textArea(['rows' => 6]) ?>
            <div class="form-group">
                <?= Html::submitButton('Отправить сообщение', ['class' => 'btn btn-default waves-effect btn-color-orange btn-color-orange-long', 'name' => 'contact-button']) ?>
            </div>

            <?php ActiveForm::end(); ?>

        <?php endif; ?>

    </div>
</div>

<div class="clearfix"></div>