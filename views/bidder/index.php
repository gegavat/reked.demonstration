<?php
/**
 * @var $dbYaAccounts \app\models\YandexAccount
 * @var $bidderYaAccounts object
 */

use yii\bootstrap\Modal;

//debug ($bidderYaAccounts);
?>

<h3>Бид-менеджер для Яндекс Директа
    <?php if ( !empty($dbYaAccounts) ) : ?>
        <small class="text-info">Выберите кампании для которых хотите настроить автоматическое управление ставками</small>
    <?php else : ?>
        <small class="text-info">Для начала добавьте аккаунт рекламной системы Яндекс Директ</small>
    <?php endif; ?>
</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['bidder'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<!-- Таблица добавленных аккаунтов Директа -->
<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="x_panel">
            <div class="x_title">
                <h2>Добавленные аккаунты Яндекс Директ</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <?php if ( !empty($dbYaAccounts) ) : ?>
                    <table class="table">
                        <thead>
                        <tr>
                            <th class="col-md-6">Аккаунт</th>
                            <th class="col-md-4">Добавлен</th>
                            <th class="col-md-2">Удалить</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $dbYaAccounts as $yaAccount ) : ?>
                            <tr>
                                <td><?= $yaAccount->login ?></td>
                                <td><?= getRusDate($yaAccount->created_at) ?></td>
                                <td><i class="glyphicon glyphicon-trash ya-account-remove-icons" data-ya_account="<?= $yaAccount->account_id ?>"></i></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div>Аккаунты не найдены...</div>
                    <br>
                <?php endif; ?>
                <button type="button" class="btn btn-default btn-sm" id="but-ad-ya-acc" data-client_id="<?= \Yii::$app->params['yandexOAuthParam']['id'] ?>">
                    <i class="fa fa-plus"></i>Добавить аккаунт
                </button>
            </div>
        </div>
    </div>
</div>

<hr>

<?php if ( empty($bidderYaAccounts->search) && empty($bidderYaAccounts->network) && !empty($dbYaAccounts) ) : ?>
    <div class="text-danger">В Яндексе нет активных рекламных кампаний...</div>
<?php endif; ?>

<!-- Таблицы по бид-менеджерам -->
<?php if ( !empty($dbYaAccounts) ) : ?>
    <?php if ( !empty($bidderYaAccounts->search) ) : ?>
        <div class="x_panel search_cmps">
            <div class="x_title">
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                </ul>
                <div>
                    <h2>Настроить бид-менеджер для кампаний с типами <strong>Поиск, Поиск+Сети</strong></h2>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="x_content">
                <table class="table table-bordered jambo_table bulk_action">
                    <thead>
                    <tr class="headings">
                        <th class="">
                            <input type="checkbox" id="check-all" class="flat">
                        </th>
                        <th class="column-title col-xs-4">Название кампании</th>
                        <th class="column-title col-xs-4">Статус кампании</th>
                        <th class="column-title last col-xs-4">Состояние бид-менеджера</th>
                        <th class="bulk-actions" colspan="3">
                            <a class="ya_bid_mod_search_show">Настроить бид-менеджер для выбранных кампаний</a>
                        </th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($bidderYaAccounts->search as $yaAccount) : ?>

                        <tr>
                            <td colspan="4" class="acc_name">
                                <?= $yaAccount->accountLogin ?>
                            </td>
                        </tr>

                        <?php foreach ($yaAccount->apiCmps as $cmp) : ?>

                            <tr>
                                <td class="a-center ">
                                    <input type="checkbox" class="flat" name="table_records"
                                           data-ya_cmp_id="<?= $cmp->Id ?>"
                                           data-ya_acc_id="<?= $yaAccount->accountId ?>"
                                    >
                                </td>
                                <td class="col-xs-4">
                                    <?= $cmp->Name ?>
                                </td>
                                <td class="col-xs-4">
                                    <?= $cmp->StatusClarification ?>
                                </td>
                                <td class="last col-xs-4 bidder-status">
                                    <?php if ($cmp->BidderStatus === 'enabled') : ?>
                                        <div class="text-success">Включен</div>
                                    <?php elseif ($cmp->BidderStatus === 'disabled') : ?>
                                        <div class="text-danger">Выключен</div>
                                    <?php else : ?>
                                        <div class="text-primary">Не настроен</div>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endforeach; ?>

                    </tbody>

                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($bidderYaAccounts->network)) : ?>
        <div class="x_panel network_cmps">
            <div class="x_title">
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                </ul>
                <div>
                    <h2>Настроить бид-менеджер для кампаний с типом <strong>Сети</strong></h2>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="x_content">
                <table class="table table-bordered jambo_table bulk_action">
                    <thead>
                    <tr class="headings">
                        <th class="">
                            <input type="checkbox" id="check-all" class="flat">
                        </th>
                        <th class="column-title col-xs-4">Название кампании</th>
                        <th class="column-title col-xs-4">Статус кампании</th>
                        <th class="column-title last col-xs-4">Состояние бид-менеджера</th>
                        <th class="bulk-actions" colspan="3">
                            <a class="ya_bid_mod_network_show">Настроить бид-менеджер для выбранных кампаний</a>
                        </th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($bidderYaAccounts->network as $yaAccount) : ?>

                        <tr>
                            <td colspan="4" class="acc_name">
                                <?= $yaAccount->accountLogin ?>
                            </td>
                        </tr>

                        <?php foreach ($yaAccount->apiCmps as $cmp) : ?>

                            <tr>
                                <td class="a-center ">
                                    <input type="checkbox" class="flat" name="table_records"
                                           data-ya_cmp_id="<?= $cmp->Id ?>"
                                           data-ya_acc_id="<?= $yaAccount->accountId ?>"
                                    >
                                </td>
                                <td class="col-xs-4">
                                    <?= $cmp->Name ?>
                                </td>
                                <td class="col-xs-4">
                                    <?= $cmp->StatusClarification ?>
                                </td>
                                <td class="last col-xs-4 bidder-status">
                                    <?php if ($cmp->BidderStatus === 'enabled') : ?>
                                        <div class="text-success">Включен</div>
                                    <?php elseif ($cmp->BidderStatus === 'disabled') : ?>
                                        <div class="text-danger">Выключен</div>
                                    <?php else : ?>
                                        <div class="text-primary">Не настроен</div>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endforeach; ?>

                    </tbody>

                </table>
            </div>
        </div>
<?php endif; ?>
<?php endif; ?>

<?php Modal::begin([
//    'size' => 'modal-lg',
    'header' => '<h2>Настройка бид-менеджера</h2>',
    'id' => 'ya_bid_modal',
    'clientOptions' => ['backdrop' => 'static', 'keyboard' => false],
//    'closeButton' => false
]); ?>

<?php Modal::end() ?>
