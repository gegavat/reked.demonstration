<?php
/**
 * @var $domains array
 * @var $enabledDomains array
 */
use yii\bootstrap\Modal;
use yii\widgets\Pjax;
use app\components\Parser;
?>

<h3>Активация мультилендинга
    <?php /* <small class="text-info">Вставьте код на страницы ваших сайтов и укажите домены для активации проекта</small> */ ?>
</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['repl-activate'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<div class="x_panel enab-domain-table">
    <div class="x_title">
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        </ul>
        <div>
            <h2>Домены, на которых будут срабатывать подмены</h2>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <table class="table table-bordered jambo_table bulk_action" id="enabled_domains">
            <thead>
                <tr>
                    <th class="col-md-2">Статус</th>
                    <th class="col-md-10">Домен</th>
                <tr>
            </thead>
            <tbody>
            <?php foreach ($domains as $domain) : ?>
                <tr>
                    <td data-domain="<?= $domain ?>">
                        <input type="checkbox" class="check_domain" name="check1"
                            <?php
                                if (Parser::isEnabledDomain($domain, $enabledDomains)) echo ' checked';
                            ?>
                        >
                    </td>
                    <td>
                        <?= $domain ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

