<?php
/**
 * @var $model \yii\base\Model
 */
use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>
<h2>Test Model Form</h2>

<?php $form = ActiveForm::begin([
    'id' => 'form-image',
    //'options' => [
        //'enctype' => 'multipart/form-data'
    //]
]) ?>

	<?= $form->field($model, 'name')->textInput() ?>
	<?= $form->field($model, 'lastName')->textInput() ?>
	<?= $form->field($model, 'imageFile')->fileInput() ?>
	
	<?= Html::submitButton('Отправить', ['class' => 'btn btn-success']) ?>
<?php ActiveForm::end() ?>

<?php
debug ($model, false);
?>
