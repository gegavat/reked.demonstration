<?php
/**
 * @var $payType string
 * @var $sum integer
 * @var $checkId integer
 */

$yaMoneyParams = [
	'url' => 'https://money.yandex.ru/quickpay/confirm.xml',
	'receiver' => Yii::$app->params['yaMoneyWallet'],
	'quickpay-form' => 'shop',
	'targets' => 'Пополнение счета в сервисе Reked',
	'paymentType' => $payType=='yandex-money' ? 'PC' : 'AC',
	'sum' => $sum,
	'formcomment' => 'Пополнение счета в сервисе Reked',
	'short-dest' => 'Пополнение счета в сервисе Reked',
	'label' => 'reked:' . $checkId,
	'comment' => 'Пополнение счета в сервисе Reked. Пользователь: ' . Yii::$app->getUser()->identity->email,
	'successURL' => Yii::$app->params['yaMoneySuccessUrl']
];


$script = <<< JS
$(document).ready(function() {
	$('form#yaPayForm').submit();
});
JS;
$this->registerJs($script);
?>


<form action="<?=$yaMoneyParams['url']?>" method="post" id="yaPayForm">
    <input type="hidden" name="receiver" value="<?=$yaMoneyParams['receiver']?>">
    <input type="hidden" name="quickpay-form" value="<?=$yaMoneyParams['quickpay-form']?>">
    <input type="hidden" name="targets" value="<?=$yaMoneyParams['targets']?>">
    <input type="hidden" name="paymentType" value="<?=$yaMoneyParams['paymentType']?>">
    <input type="hidden" name="sum" value="<?=$yaMoneyParams['sum']?>">
    <input type="hidden" name="formcomment" value="<?=$yaMoneyParams['formcomment']?>">
    <input type="hidden" name="short-dest" value="<?=$yaMoneyParams['short-dest']?>">
    <input type="hidden" name="label" value="<?=$yaMoneyParams['label']?>">
    <input type="hidden" name="comment" value="<?=$yaMoneyParams['comment']?>">
    <input type="hidden" name="successURL" value="<?=$yaMoneyParams['successURL']?>">
</form>