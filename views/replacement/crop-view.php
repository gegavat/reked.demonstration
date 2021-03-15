<?php
/**
 * @var $image \yii\base\Model
 */
?>

<h4>Обрежьте и сохраните изображение</h4>

<div class="crop-image">
    <img id="jcrop-img" src="<?= Yii::getAlias('@image_url') . '/temp/' . $image->imageName ?>" alt="crop-image">
</div>

<hr>
<button class="apply-img-crop btn btn-success">Обрезать и сохранить</button>
<button class="apply-img-nocrop btn btn-primary">Сохранить без обрезки</button>
<button class="apply-img-cnc btn btn-default">Отмена</button>
