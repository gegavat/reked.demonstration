<?php
/**
 * @var $image \yii\base\Model
 */
use yii\widgets\ActiveForm;
?>
<p style="font-size: 1.1em">Выберите файл для загрузки</p>
<p>Размеры используемого изображения: <span id="img-sizes"></span></p>

<button id="choose-file" class="btn btn-success">Выбрать файл</button>
<button id="cnc-file" class="btn btn-default">Отмена</button>

<?php $form = ActiveForm::begin([
    'id' => 'form-image',
    'options' => [
        'enctype' => 'multipart/form-data'
    ]
]) ?>
<?= $form->field($image, 'imageFile', ['inputOptions' => ['id' => 'btn-file-img']])->fileInput()->label(false) ?>
<?php ActiveForm::end() ?>

<hr>
<button id="choose-loaded-file" class="btn btn-primary">Выбрать из загруженных</button>
