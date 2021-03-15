<?php
/**
 * @var $pages \app\models\GeoPage
 * @var $replPageId int
 */
?>
<p class="no-marks">
    Страница <i><?= array_column($pages, 'page', 'id' )[$replPageId] ?></i> не размечена.
    <br>
    <a href="#" id="link_to_mark">
        Перейти к разметеке страницы
    </a>
</p>