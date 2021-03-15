<?php
/**
 * @var $dbPay \app\models\Payment
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$minSum = Yii::$app->params['minRefill'];
$maxSum = Yii::$app->params['maxRefill'];

$script = <<< JS
    $(".pay_add_sum").TouchSpin({
        min: $minSum,
        max: $maxSum,
        // step: 10,
        // boostat: 5,
        // maxboostedstep: 10,
        buttondown_class: 'btn btn-default',
        buttonup_class: 'btn btn-default',
        postfix: 'руб.'
    });
JS;
$this->registerJs($script);

?>

<H2>
    Баланс: <i class="fa fa-rub"></i> <span id="balance"></span>
</H2>

<?php
    $form = ActiveForm::begin([
        'id' => 'payment',
        'options' => ['class' => 'form-horizontal'],
    ]);
    $payHtml = <<< PH
        <br>
        <div class="pay_img" data-value="yandex-money" id="yandex-money-img"></div>
        <div class="pay_img" data-value="yandex-card" id="yandex-card-img"></div>
        <div class="pay_img" data-value="robokassa" id="robokassa-img" style="display:none"></div>
        <div class="clearfix"></div>
PH;

?>
    <?= $form->field($dbPay, 'pay_add_sum')->textInput(['class' => 'pay_add_sum'])->label('Введите сумму для пополнения:') ?>
    <?= $form
        ->field($dbPay, 'pay_type', [
            'template' => "{label}\n$payHtml\n{input}\n{hint}\n{error}"
        ])
        ->textInput(['class' => 'pay_type'])
        ->label('Выберите способ оплаты:')
    ?>
    <?= Html::submitButton('Пополнить', ['class' => 'btn btn-success align-bottom']) ?>
    <a href="#" id="display_coupon">Применить купон</a>
<?php ActiveForm::end() ?>

<div id="coupon_container">
    <input type="text" class="form-control" id="input_coupon" placeholder="Введите купон:">
    <button class="btn btn-default btn-xs" id="btn_coupon">Применить</button>
</div>