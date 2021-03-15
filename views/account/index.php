<?php
/* @var $yaAccounts \yii\db\ActiveRecord */
/* @var $gAccounts \yii\db\ActiveRecord */

use yii\widgets\Pjax;
use yii\helpers\Url;
?>
<h3>Добавление аккаунтов
    <small class="text-info">Добавьте аккаунты Яндекс Директ и Google Ads</small>
</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['account'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<div class="row">
    <div class="col-sm-6 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Яндекс Директ</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <button type="button" class="btn btn-success" id="but-ad-ya-acc" data-client_id="<?= \Yii::$app->params['yandexOAuthParam']['id'] ?>">
                    <i class="fa fa-plus"></i>Добавить аккаунт
                </button>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Google Ads</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <button type="button" class="btn btn-success" id="but-ad-go-acc" data-link="<?= Url::base(true) . Url::to(['account/get-google-token']) ?>">
                    <i class="fa fa-plus"></i>Добавить аккаунт
                </button>
            </div>
        </div>
    </div>
</div>

<hr>

<!-- Таблица добавленных аккаунтов -->
<?php Pjax::begin([
    'id' => 'pjax_account',
    'timeout' => 5000,
    'enablePushState' => false
]) ?>
    <?php if ( !empty($yaAccounts) || !empty($gAccounts) ) : ?>
        <div class="row">
            <div class="col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Добавленные аккаунты</h2>
                        <ul class="nav navbar-right panel_toolbox">
                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table class="table table-bordered jambo_table bulk_action">
                            <thead>
                            <tr>
                                <th class="col-md-6">Аккаунт</th>
                                <th class="col-md-4">Добавлен</th>
                                <th class="col-md-2">Удалить</th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php if ( !empty($yaAccounts) ) : foreach ( $yaAccounts as $yaAccount ) : ?>
                                <tr>
                                    <td><?= $yaAccount->login ?></td>
                                    <td><?= getRusDate($yaAccount->created_at) ?></td>
                                    <td><i class="glyphicon glyphicon-trash ya-account-remove-icons" data-ya_account="<?= $yaAccount->account_id ?>"></i></td>
                                </tr>
                            <?php endforeach; endif; ?>

                            <?php if ( !empty($gAccounts) ) : foreach ( $gAccounts as $gAccount ) : ?>
                            <?php if ( $gAccount->mcc ) continue; ?>
                                <tr>
                                    <td><?= $gAccount->login ?> (<?= $gAccount->account_id ?>)</td>
                                    <td><?= getRusDate($gAccount->created_at) ?></td>
                                    <td><i class="glyphicon glyphicon-trash g-account-remove-icons" data-g_account="<?= $gAccount->account_id ?>"></i></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <br><br>
<a id="btn_pjax_account" style="display: none" href="<?= Yii::$app->request->url; ?>">Refresh</a>
<?php Pjax::end() ?>
