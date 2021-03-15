<?php
/**
 * @var $imageNames array
 */
?>

<p style="font-size: 1.2em">Выберите изображение</p>
<div class="gallery">
    <div class="loaded-image">
        <?php foreach ( $imageNames as $imageName ) : ?>
            <img src="<?= getUserImageUrl() . $imageName ?>" data-selected="false">
        <?php endforeach; ?>
    </div>
</div>

<hr>
<button id="btn-choose-loadimg" class="btn btn-primary">Выбрать</button>
<button id="btn-cnc-loadimg" class="btn btn-default">Отмена</button>