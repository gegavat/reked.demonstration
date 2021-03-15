<?php
/* @var $yaAccounts \app\models\YandexAccount */
/* @var $gAccounts \app\models\GoogleAccount */
/**
 * Вид отображения кампаний
*/
use yii\bootstrap\Modal;
use yii\widgets\Pjax;
use yii\helpers\Url;
?>

<h3>Загрузка кампаний
    <small class="text-info">Загрузите кампании из рекламных систем</small>
</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['campaign'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<?php Pjax::begin([
    'id' => 'pjax_campaign',
    'timeout' => 5000,
    'enablePushState' => false
]) ?>

<?php if ( !empty($yaAccounts) ) : foreach ( $yaAccounts as $yaAccount ) : ?>
    <div class="x_panel">
        <div class="x_title">
            <ul class="nav navbar-right panel_toolbox">
                <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
            </ul>
            <div class="row">
                <div>
                    <h2><?= $yaAccount->login ?></h2>
                </div>
            </div>
            <div class="row add-cmp">
                <div>
                    <button type="button" class="btn btn-success btn-sm get-ya-cmp" data-ya_account="<?= $yaAccount->account_id ?>" data-login="<?= $yaAccount->login ?>">
                        <i class="fa fa-plus"></i>Добавить кампании
                    </button>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php if ( !empty($yaAccount->yandexCampaigns) ) : ?>
			<div class="x_content">
				<table class="table table-hov">
					<thead>
					<tr>
						<th class="">Название кампании</th>
						<th class="">Групп объявлений</th>
						<th class="">Синхронизировано</th>
						<th class="">Обновить</th>
						<th class="">Удалить</th>
					</tr>
					</thead>
					<tbody>
						<?php foreach ( $yaAccount->yandexCampaigns as $cmp ) : ?>
					
						<tr>
							<td class="col-xs-4"><?= $cmp->campaign_name ?></td>
							<td class="col-xs-3"><?= count($cmp->yandexAdGroups) ?></td>
							<td class="col-xs-3"><?= elapsed_time($cmp->updated_at) ?></td>
							<td class="col-xs-1"><i class="glyphicon glyphicon-refresh ya-cmp-update" data-ya_account="<?= $yaAccount->account_id ?>" data-ya_cmp="<?=$cmp->campaign_id?>"></i></td>
							<td class="col-xs-1"><i class="glyphicon glyphicon-trash ya-cmp-delete" data-ya_cmp="<?=$cmp->campaign_id?>"></i></td>
						</tr>

                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <br><br>
<?php endforeach; endif; ?>

<?php if ( !empty($gAccounts) ) : foreach ( $gAccounts as $gAccount ) : ?>
    <div class="x_panel">
        <div class="x_title">
            <ul class="nav navbar-right panel_toolbox">
                <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
            </ul>
            <div class="row">
                <div>
                    <h2>
                        <?= $gAccount->login ?>
                        (<?= $gAccount->account_id ?>)
                    </h2>
                </div>
            </div>
            <div class="row add-cmp">
                <div>
                    <button type="button" class="btn btn-success btn-sm get-g-cmp" data-g_account="<?= $gAccount->account_id ?>" data-login="<?= $gAccount->login ?>">
                        <i class="fa fa-plus"></i>Добавить кампании
                    </button>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php if ( !empty($gAccount->googleCampaigns) ) : ?>
			<div class="x_content">
				<table class="table table-hov">
					<thead>
					<tr>
						<th class="">Название кампании</th>
						<th class="">Групп объявлений</th>
						<th class="">Синхронизировано</th>
						<th class="">Обновить</th>
						<th class="">Удалить</th>
					</tr>
					</thead>
					<tbody>
						
					<?php foreach ( $gAccount->googleCampaigns as $cmp ) : ?>
					
						<tr>
							<td class="col-xs-4"><?= $cmp->campaign_name ?></td>
							<td class="col-xs-3"><?= count($cmp->googleAdGroups) ?></td>
							<td class="col-xs-3"><?= elapsed_time($cmp->updated_at) ?></td>
							<td class="col-xs-1"><i class="glyphicon glyphicon-refresh g-cmp-update" data-g_account="<?= $gAccount->account_id ?>" data-g_cmp="<?=$cmp->campaign_id?>"></i></td>
							<td class="col-xs-1"><i class="glyphicon glyphicon-trash g-cmp-delete" data-g_cmp="<?=$cmp->campaign_id?>"></i></td>
						</tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <br><br>
<?php endforeach; endif; ?>

<a id="btn_pjax_campaign" style="display: none" href="<?= Yii::$app->request->url; ?>">Refresh</a>
<?php Pjax::end() ?>

<?php Modal::begin([
    'size' => 'modal-lg',
    /*'options' => [
        'style' => [
            'margin-top' => '5%'
        ]
    ],*/
    'header' => '<h2>Загрузка кампаний - <span style="font-weight: 600"></span></h2>',
    'id' => 'show-get-cmp',
]); ?>
<?php Modal::end() ?>
