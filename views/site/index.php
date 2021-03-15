<?php
/* @var $tariffName string */
/* @var $validity string */
/* @var $interval int */
/* @var $enabledDomain int */
/* @var $availableDomain int */
/* @var $enabledPage int */
/* @var $availablePage int */
/* @var $traffic int */
/* @var $availableTraffic int */
/* @var $enabledBidder int */
/* @var $availableBidder int */

use yii\helpers\Url;
?>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['main'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<div class="x_panel reked-tools">
    <div class="x_title">
        <h2>Инструменты сервиса</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        </ul>
        <div class="clearfix"></div>
    </div>

    <div class="x_content">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th class="col-xs-4">Мультилендинг</th>
                <th class="col-xs-4">Геолендинг</th>
                <th class="col-xs-4">Бид-менеджер</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Настройте подменяемые элементы на своем сайте под рекламные объявления Яндекс Директ и Google Ads. Люди, пришедшие к вам из поисковых систем, будут получать на сайте то, что они искали.</td>
                    <td>Изменяйте содержимое вашего сайта в зависимости от региона пользователя. Люди, пришедшие к вам на сайт, будут получать информацию, соответствующую их городу.</td>
                    <td>Доверьте управление ставками на аукционе Яндекс Директа нашему Бид-Менеджеру. Он будет выставлять оптимальные ставки, добиваясь нужного вам объема трафика.</td>
                </tr>
            <tr>
                <td><a href="<?= Url::to(['/account/index']) ?>" class="btn btn-success btn-sm">Перейти к настройке Мультилендинга</a></td>
                <td><a href="<?= Url::to(['/geo-page/index']) ?>" class="btn btn-success btn-sm">Перейти к настройке Геолендинга</a></td>
                <td><a href="<?= Url::to(['/bidder/index']) ?>" class="btn btn-success btn-sm">Перейти к настройке Бид-Менеджера</a></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<hr>

<!-- Таблица со статистикой -->
<div class="x_panel stat_bg">
    <div class="x_title">
        <h2>Ваш тариф: <strong><?=mb_strtoupper($tariffName)?></strong></h2>
        <ul class="nav navbar-right panel_toolbox">
            <li class="tarif-info"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="w_left">

            <div class="progress_title">
                <span class="left">Срок действия тарифа</span>
                <span class="right">до <?= getRusDate($validity) ?></span>
                <div class="clearfix"></div>
            </div>
            <div class="progress">
                <div class="progress-bar <?= ($interval > Yii::$app->params['checkInterval']['prolongation'] )? 'bg-red' : 'bg-green' ?>" role="progressbar" data-transitiongoal="<?= $interval ?>" aria-valuemax="<?= Yii::$app->params['checkInterval']['prolongation'] ?>"></div>
            </div>

            <?php if ( !($tariffName == Yii::$app->params['tariff'][3]) ) : ?>
                <div class="progress_title">
                    <span class="left">Домены (Мультилендинг): активировано / доступно</span>
                    <span class="right"><?= $enabledDomain ?> / <?= $availableDomain ?></span>
                    <div class="clearfix"></div>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-blue" role="progressbar" data-transitiongoal="<?= $enabledDomain ?>" aria-valuemax="<?= $availableDomain ?>"></div>
                </div>

                <div class="progress_title">
                    <span class="left">Страницы (Геолендинг): активировано / доступно</span>
                    <span class="right"><?= $enabledPage ?> / <?= $availablePage ?></span>
                    <div class="clearfix"></div>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-blue-sky" role="progressbar" data-transitiongoal="<?= $enabledPage ?>" aria-valuemax="<?= $availablePage ?>"></div>
                </div>

                <div class="progress_title">
                    <span class="left">Подмен на сайте: обработано / доступно</span>
                    <span class="right"><?= $traffic ?> / <?= $availableTraffic ?></span>
                    <div class="clearfix"></div>
                </div>
                <div class="progress">
                    <div class="progress-bar <?= ($traffic >= $availableTraffic )? 'bg-red' : 'bg-green' ?>" role="progressbar" data-transitiongoal="<?= $traffic ?>" aria-valuemax="<?= $availableTraffic ?>"></div>
                </div>

                <div class="progress_title">
                    <span class="left">Бид-менеджеры: включено / доступно</span>
                    <span class="right"><?= $enabledBidder ?> / <?= $availableBidder ?></span>
                    <div class="clearfix"></div>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-purple" role="progressbar" data-transitiongoal="<?= $enabledBidder ?>" aria-valuemax="<?= $availableBidder ?>"></div>
                </div>
            <?php endif; ?>

        </div>
        <button class="btn btn-default btn-sm"><a href="<?= Url::to(['payment/change-tariff']) ?>">Перейти на другой тариф</a></button>
    </div>
</div>