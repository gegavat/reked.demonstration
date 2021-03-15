<?php
/**
 * @var $pages \app\models\GeoPage
 * @var $script string
 */
?>

<h3>Вставка кода</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['geo-insert-code'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<div class="x_panel emb-code-wrap">
    <div class="x_title">
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        </ul>
        <div>
            <h2>
                Код ниже необходимо вставить перед закрывающим тегом
                <strong><i><?= htmlspecialchars('</head>') ?></i></strong>
                на всех страницах ваших сайтов
            </h2>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div id="embed_code"><?= htmlspecialchars ($script); ?></div>
        <i id="copy_emb_code" data-clipboard-target="#embed_code">скопировать код в буфер</i>
    </div>
</div>

<div class="x_panel">
    <div class="x_title">
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        </ul>
        <div>
            <h3>Наличие кода</h3>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="x_content">
        <div class="table-responsive">
            <table class="table table-bordered jambo_table bulk_action">
                <thead>
                <tr>
                    <th class="col-md-9">Страница</th>
                    <th class="col-md-3">Статус</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pages as $page) : ?>
                    <tr>
                        <td><?= $page->page ?></td>
                        <td class="page_status" data-page="<?= $page->page ?>"></td>
                    </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
</div>