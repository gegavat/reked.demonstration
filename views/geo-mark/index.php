<?php
/**
 * @var $marks \app\models\GeoMark
 * @var $pages \app\models\GeoPage
 * @var $geo_mark_page string
 */

use yii\helpers\Url;
use yii\widgets\Pjax;
use yii\web\View;
use app\components\Parser;

$script = <<< JS
document.domain = 'reked.ru';
JS;
$this->registerJs($script, View::POS_HEAD);
?>

<h3>Разметка страниц
    <small class="text-info">Выберите подменяемые элементы на страницах сайта. Просто кликните по необходимым элементам мышью</small>
</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['geo-mark'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<?php Pjax::begin([
    'id' => 'pjax_geo_mark',
    'timeout' => 5000,
    'enablePushState' => false
]) ?>

<?php if ( !empty($marks) ) : ?>

    <div class="x_panel">
        <div class="x_title">
            <ul class="nav navbar-right panel_toolbox">
                <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
            </ul>
            <div class="row">
                <div>
                    <h3>Подменяемые элементы:</h3>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>

        <div class="x_content">
            <div class="table-responsive" id="geo_marks">
                <table class="table table-hov table-striped">
                    <thead>
                    <tr>
                        <th class="col-md-1">Тип</th>
                        <th class="col-md-6">Название</th>
                        <th class="col-md-1">Посмотреть</th>
                        <th class="col-md-1">Переместить</th>
                        <th class="col-md-1">Удалить</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($marks as $mark) : ?>
                        <tr>
                            <td>
                                <?php if ($mark->type === 'txt') : ?>
                                    <span class="glyphicon glyphicon-text-size" aria-hidden="true"></span>
                                <?php else : ?>
                                    <span class="glyphicon glyphicon-picture" aria-hidden="true"></span>
                                <?php endif; ?>
                            </td>
                            <td><input type="text" class="geo_mark_name form-control" data-id="<?= $mark->id ?>" value="<?= $mark->name ?>"></td>
                            <td><span data-selector_path="<?= $mark->selector_path ?>" class="geo_mark_watch geo_mark_icons glyphicon glyphicon-eye-open" aria-hidden="true"></span></td>
                            <td><span data-id="<?= $mark->id ?>" class="geo_mark_move geo_mark_icons glyphicon glyphicon-move" aria-hidden="true"></span></td>
                            <td><span data-id="<?= $mark->id ?>" class="geo_mark_del geo_mark_icons glyphicon glyphicon-trash" aria-hidden="true"></span></td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<a id="btn_pjax_geo_mark" style="display: none" href="<?= Yii::$app->request->url; ?>">Refresh</a>
<?php Pjax::end() ?>

<div class="x_panel geo_mark-page">

    <div class="x_title">
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
        </ul>
        <div class="row">
            <div>
                <h3>Размечаемая страница:</h3>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="x_content">
        <div class="form-group">
            <select class="form-control" id="geo_mark_select">
                <option disabled>Выберите размечаемую страницу</option>
                <?php foreach($pages as $page) : ?>
                    <option <?php if($page == $geo_mark_page) echo ' selected ' ?> value=<?= $page ?>><?= $page ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="embed-responsive embed-responsive-16by9" id="insert_frame">
            <iframe
                id="geo_mark_frame"
                class="embed-responsive-item"
                src="<?= Parser::getFramePage($geo_mark_page) ?>"
                allowfullscreen
            ></iframe>
        </div>
    </div>
</div>

<!-- элементы для перемещения подмен -->
<div id="bckgr_lock">
    <!--
        <a id="bckgr_unlock" style="><img src="web/images/cancel.svg" style="width:50px"></a>
    -->
</div>
<div id="bckgr_lock_message" class="alert alert-info" role="alert">
    <div class="container">
        <p>Выберите элемент, на который хотите переместить эту подмену</p>
        <a id="bckgr_unlock" class="btn btn-default btn-sm">Отмена</a>
    </div>
</div>

