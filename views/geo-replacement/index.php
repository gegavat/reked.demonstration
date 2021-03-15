<?php
/**
 * @var $pages \app\models\GeoPage
 * @var $replPageId int
 * @var $locations \app\models\GeoLocation
 * @var $marks \app\models\GeoMark
 * @var $pageReplacements \app\models\GeoReplacement
 * @var $sgCountries \app\models\sypexgeo\Country
 */

use yii\helpers\Url;
use app\components\Parser;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;
use yii\widgets\LinkPager;

?>

<h3>Настройка подменяемых элементов
    <small class="text-info">Здесь нужно заполнить таблицу с подменами для размеченных страниц</small>
</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['geo-replacement'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<?php Pjax::begin([
    'id' => 'pjax_geo_replacement',
    'timeout' => 20000,
    'enablePushState' => true
]) ?>

<div id="repl_cont" class="repl-wrap">
    <div class="row">
        <div class="col-xs-12 col-sm-8 col-lg-4">
            <h4>Страница сайта:</h4>
            <div class="form-group">
                <select class="form-control" id="geo_replacement_page">
                    <option disabled>Выберите размеченную страницу</option>
                    <?php foreach ( $pages as $page ) : ?>
                        <option value="<?= $page->id ?>" <?php if ($replPageId == $page->id) echo ' selected' ?>>
                            <?= $page->page ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <br>

    <?php
    // проверка размечена ли данная страница
    if ( empty($marks) ) :
        echo $this->render('no-marks', compact ('replPageId', 'pages'));
    ?>

    <?php else : ?>
        <div class="x_panel">

            <div class="x_content">
                <table class="table table-bordered table-striped">

                    <col>
                    <?php $col_size = getColumnSize($marks, 58.3); ?>
                    <?php foreach ( $marks as $mark ) : ?>
                        <?php if ( $mark->type === 'txt' ) : ?>
                            <col style="width: <?= $col_size->txt ?>%">
                        <?php endif; ?>
                        <?php if ( $mark->type === 'img' ) : ?>
                            <col style="width: <?= $col_size->img ?>%">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <col>

                    <thead>
                    <tr>
                        <th scope="col" rowspan="2" class="col-md-3">Локации</th>
                        <th colspan="<?= count($marks) ?>" scope="colgroup" class="col-md-8">Подменяемые элементы</th>
                        <th scope="col" rowspan="2" class="col-md-1"></th>
                    </tr>
                    <tr>
                        <?php foreach ($marks as $mark) : ?>
                            <th scope="col" class="col-md-2"><?= $mark->name ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>

                    <tbody>
                        <?php
                        $numb_txt_repl = 0; // номер текстовой подмены
                        ?>
                        <?php foreach ($locations as $location) : ?>
                            <tr>
                                <td><?= Parser::getLocation($location->geo_type, $location->geo_id) ?></td>

                                <?php foreach ($marks as $mark) : ?>
                                    <?php
                                    $replacement = Parser::getCurReplacementFromReplacementsByLocationAndMark($pageReplacements, $location, $mark);
                                    //debug($replacement);
                                    ?>
                                    <td>
                                        <div class="td-container">

                                            <?php if ( $mark->type === 'img' ) : ?>
                                                <?php if ( isset($replacement->image_name) ) : ?>
                                                    <div class="repl-img"
                                                         data-mark_id="<?= $mark->id ?>"
                                                         data-location_id="<?= $location->id ?>"
                                                         data-selected="false"
                                                         data-width="<?= $mark->img_width ?>"
                                                         data-height="<?= $mark->img_height ?>"
                                                         data-mode="update"
                                                    >
                                                        <img src="<?= getUserImageUrl() . $replacement->image_name ?>"
                                                             alt="<?= 'replacement-' . $replacement->id  ?>"
                                                        >
                                                    </div>
                                                <?php else :  ?>
                                                    <div class="repl-img"
                                                         data-mark_id="<?= $mark->id ?>"
                                                         data-location_id="<?= $location->id ?>"
                                                         data-selected="false"
                                                         data-width="<?= $mark->img_width ?>"
                                                         data-height="<?= $mark->img_height ?>"
                                                         data-mode="save"
                                                    >
                                                        <img src="/web/images/no-image.png" alt="no-image">
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if ( $mark->type === 'txt' ) : ?>

                                                <span class="save-ok glyphicon glyphicon-ok" data-numb_txt_repl="<?= $numb_txt_repl ?>"></span>
                                                <div class="txt-area"
                                                     data-location_id="<?= $location->id ?>"
                                                     data-numb_txt_repl="<?= $numb_txt_repl ?>"
                                                     data-mark_id="<?= $mark->id ?>"
                                                     data-delta="<?= $replacement ? Parser::insertJsonIntoHtml($replacement->delta) : '' ?>"
                                                >
                                                </div>

                                                <div class="editor-tbr-<?= $numb_txt_repl ?>">
                                                    <span class="ql-formats">
                                                        <button class="ql-bold"></button>
                                                        <button class="ql-italic"></button>
                                                        <button class="ql-underline"></button>
                                                        <button class="ql-header" value="1"></button>
                                                        <button class="ql-header" value="2"></button>
                                                        <select class="ql-size">
                                                            <option value="8px">8px</option>
                                                            <option value="10px">10px</option>
                                                            <option value="12px" selected>normal</option>
                                                            <option value="14px">14px</option>
                                                            <option value="16px">16px</option>
                                                            <option value="18px">18px</option>
                                                            <option value="20px">20px</option>
                                                            <option value="22px">22px</option>
                                                            <option value="24px">24px</option>
                                                            <option value="48px">48px</option>
                                                        </select>
                                                    </span>
                                                </div>
                                                <?php $numb_txt_repl++; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <span class="repl-icon repl-watch glyphicon glyphicon-eye-open"
                                          data-location_id="<?= $location->id ?>"
                                          data-toggle="tooltip" data-placement="bottom" title="Просмотреть"
                                    ></span>
                                    <span class="repl-icon repl-del glyphicon glyphicon-trash"
                                          data-location_id="<?= $location->id ?>"
                                          data-toggle="tooltip" data-placement="bottom" title="Удалить локацию"
                                    ></span>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="<?= count($marks) + 2 ?>">
                                <button class="btn btn-default btn-sm location-add">
                                    <i class="fa fa-plus"></i>
                                    Добавить локацию
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<? /*
<?= LinkPager::widget([
    'pagination' => $paginationPages,
]); ?>
<br>
<?php if ( count($adGroups) >= 10 ) : ?>
    <ul class="pagination page-number">
        <li class="pagin_elem <?php if ($pageNumber == 10) echo ' active' ?>" data-page_numb="10"><a href="#">10</a></li>
        <li class="pagin_elem <?php if ($pageNumber == 30) echo ' active' ?>" data-page_numb="30"><a href="#">30</a></li>
        <li class="pagin_elem <?php if ($pageNumber == 50) echo ' active' ?>" data-page_numb="50"><a href="#">50</a></li>
        <li class="disabled"><span>записей на странице</span></li>
    </ul>
<?php endif; ?>
*/ ?>

<a id="btn_pjax_geo_replacement" style="display: none" href="<?= Yii::$app->request->getUrl() ?>">Refresh</a>
<?php Pjax::end() ?>

<?php Modal::begin([
    'size' => 'modal-lg',
    'header' => '<h2>Добавление локации</h2>',
    'id' => 'geo-location-modal'
]); ?>
    <div class="mod-loc-h">Укажите страну</div>
    <div class="mod-loc-cont mod-country-cont">
        <form>
            <div class="radio sg-country-list">
                <?php foreach ($sgCountries as $sgCountry) : ?>
                    <label>
                        <input name="sg-country" type="radio" data-country_id="<?= $sgCountry->country_id ?>" value="<?= $sgCountry->country_iso ?>"> <?= $sgCountry->name_ru ?>
                    </label><br>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
    <div class="mod-loc-h">Укажите регион</div>
    <div class="mod-loc-cont">
        <form>
            <div class="radio sg-region-list">
            </div>
        </form>
    </div>
    <div class="mod-loc-h">Укажите город</div>
    <div class="mod-loc-cont">
        <form>
            <div class="radio sg-city-list">
            </div>
        </form>
    </div>
    <br>
    <div>
        <button class="btn btn-success geo_loc_mod_add">Добавить локацию</button>
        <button class="btn btn-default geo_loc_mod_close">Отмена</button>
    </div>
<?php Modal::end() ?>

<?php Modal::begin([
    'size' => 'modal-lg',
    'header' => '<h2>Загрузка изображений</h2>',
    'id' => 'loading-image',
    'clientOptions' => ['backdrop' => 'static', 'keyboard' => false],
    'closeButton' => false
]); ?>
<?php Modal::end() ?>


