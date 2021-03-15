<?php
/* @var $pages \app\models\GeoPage */
/**
 * Вид отображения кампаний
*/
use yii\bootstrap\Modal;
use yii\widgets\Pjax;
use yii\helpers\Url;
?>

<h3>Добавление страниц</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['geo-page'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<div class="x_panel geo_page_add_block">
    <div class="x_title">
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        </ul>
        <div>
            <h2>
                Добавьте страницы сайта, на которых будут работать гео-подмены
            </h2>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
	    <div class="row">
            <div class="col-xs-2">
                <select class="form-control" id="geo_page_protocol">
                    <option value="http://">http://</option>
                    <option value="https://">https://</option>
                </select>
            </div>
	        <div class="col-xs-8">
				<input type="text" class="form-control" placeholder="Ссылка на страницу сайта" id="geo_page_url">
			</div>
			<div class="col-xs-2">
                <button class="btn btn-success btn-block" id="geo_page_add">
                    <?php /* <i class="fa fa-plus"></i> */ ?>
                    Добавить
                </button>
			</div>
		</div>
    </div>
</div>

<?php Pjax::begin([
    'id' => 'pjax_geo_page',
    'timeout' => 5000,
    'enablePushState' => false
]) ?>
<?php if ( !empty($pages) ) : ?>
<hr>
<div class="x_panel">
    <div class="x_title">
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        </ul>
        <div>
            <h2>
                Добавленные страницы
            </h2>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
	    <div class="table-responsive">
        <table class="table table-bordered jambo_table bulk_action">
            <thead>
            <tr>
                <th class="col-md-10">Страница</th>
                <th class="col-md-2">Удаление</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $pages as $page ) : ?>
                <tr>
                    <td><?= $page->page ?></td>
                    <td><span class="glyphicon glyphicon-trash geo_page_del" data-page_id="<?=$page->id?>" aria-hidden="true"></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    	</div>
    </div>
</div>
<?php endif; ?>
<a id="btn_pjax_geo_page" style="display: none" href="<?= Yii::$app->request->url; ?>">Refresh</a>
<?php Pjax::end() ?>
