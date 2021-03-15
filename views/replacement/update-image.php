<?php
/**
 * @var $replacement \app\models\Replacement
 */
?>
<p style="font-size: 1.1em">Загрузите новое изображение или удалите старое</p>
<div class="update-image">
    <img src="<?= getUserImageUrl() . $replacement->image_name ?>" alt="<?= $replacement->id ?>">
</div>

<hr>
<button id="btn-upd-img" class="btn btn-primary">Загрузить</button>
<button id="btn-del-img" class="btn btn-danger">Удалить</button>
<button id="btn-cnc-img" class="btn btn-default">Отмена</button>