<?php
/**
 * @var $pages \app\models\GeoPage
 */
?>

<h3>Активация геолендинга</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['geo-repl-activate'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<div class="x_panel enab-page-table">
    <div class="x_title">
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        </ul>
        <div>
            <h2>Страницы, на которых будут срабатывать подмены</h2>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <table class="table table-bordered jambo_table bulk_action" id="enabled_pages">
            <thead>
                <tr>
                    <th class="col-md-2">Статус</th>
                    <th class="col-md-10">Страница</th>
                <tr>
            </thead>
            <tbody>
            <?php foreach ($pages as $page) : ?>
                <tr>
                    <td data-page_id="<?= $page->id ?>">
                        <input type="checkbox" class="check_page" name="check1"
                            <?php
                                if ( $page->enabled ) echo ' checked';
                            ?>
                        >
                    </td>
                    <td>
                        <?= $page->page ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

