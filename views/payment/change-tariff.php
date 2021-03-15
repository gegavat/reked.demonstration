<?php
/**
 * @var $tariffUser string
 */

?>

<h3>Тарифы сервиса
    <small class="text-info">Выберите новый тариф</small>
</h3>
<br>

<div class="x_panel" id="table-tariffs">
    <div class="x_title">
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <table class="table table-striped">
            <thead>
            <tr>
                <th></th>
                <?php for ($i=0, $size=count(Yii::$app->params['tariff']); $i<$size; ++$i) : ?>
                    <th><h5 style="font-weight: 600" align="center"><?= strtoupper (Yii::$app->params['tariff'][$i]) ?></h5></th>
                <?php endfor; ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Количество доменов для Мультилендинга, доступных для активации <i class="fa fa-question-circle" title="Мультилендинг будет работать только на страницах активированных доменов"></i></td>
                <?php for ($i=0, $size=count(Yii::$app->params['permitedDomainNumber']); $i<$size; ++$i) : ?>
                    <?php if (Yii::$app->params['permitedDomainNumber'][Yii::$app->params['tariff'][$i]]<1000000) : ?>
                        <td align="center"><?= Yii::$app->params['permitedDomainNumber'][Yii::$app->params['tariff'][$i]] ?></td>
                    <?php else: ?>
                        <td align="center">без ограничений</td>
                    <?php endif; ?>
                <?php endfor; ?>
            </tr>
            <tr>
                <td>Количество страниц для Геолендинга, доступных для активации <i class="fa fa-question-circle" title="Геолендинг будет работать только на активированных страницах"></i></td>
                <?php for ($i=0, $size=count(Yii::$app->params['permitedPageNumber']); $i<$size; ++$i) : ?>
                    <?php if (Yii::$app->params['permitedPageNumber'][Yii::$app->params['tariff'][$i]]<1000000) : ?>
                        <td align="center"><?= Yii::$app->params['permitedPageNumber'][Yii::$app->params['tariff'][$i]] ?></td>
                    <?php else: ?>
                        <td align="center">без ограничений</td>
                    <?php endif; ?>
                <?php endfor; ?>
            </tr>
            <tr>
                <td>Количество обрабатываемых переходов
                    <i class="fa fa-question-circle" title="Сколько раз наш сервис выполнит подмену содержимого на ваших страницах.
                    Обнуляется каждые  <?= Yii::$app->params['checkInterval']['prolongation'] ?> дней"></i>
                </td>
                <?php for ($i=0, $size=count(Yii::$app->params['permitedTrafficNumber']); $i<$size; ++$i) : ?>
                    <?php if (Yii::$app->params['permitedTrafficNumber'][Yii::$app->params['tariff'][$i]]<1000000) : ?>
                        <td align="center"><?= Yii::$app->params['permitedTrafficNumber'][Yii::$app->params['tariff'][$i]] ?></td>
                    <?php else: ?>
                        <td align="center">без ограничений</td>
                    <?php endif; ?>
                <?php endfor; ?>
            </tr>
            <tr>
                <td>Количество активных бид-менеджеров <i class="fa fa-question-circle" title="К какому количеству рекламных кампаний можно будет прикрепить бид-менеджер"></i></td>
                <?php for ($i=0, $size=count(Yii::$app->params['permitedBidderNumber']); $i<$size; ++$i) : ?>
                    <?php if (Yii::$app->params['permitedBidderNumber'][Yii::$app->params['tariff'][$i]]<1000000) : ?>
                        <td align="center"><?= Yii::$app->params['permitedBidderNumber'][Yii::$app->params['tariff'][$i]] ?></td>
                    <?php else: ?>
                        <td align="center">без ограничений</td>
                    <?php endif; ?>
                <?php endfor; ?>
            </tr>
            <tr>
                <td>Стоимость за <?= Yii::$app->params['checkInterval']['prolongation'] ?> дней</td>
                <?php for ($i=0, $size=count(Yii::$app->params['costTariff']); $i<$size; ++$i) : ?>
                    <td align="center"><?= Yii::$app->params['costTariff'][Yii::$app->params['tariff'][$i]] ?> <i class="fa fa-rub"></i></td>
                <?php endfor; ?>
            </tr>
            <tr>
                <td></td>
                <?php for ($i=0, $size=count(Yii::$app->params['tariff']); $i<$size; ++$i) : ?>
                    <td align="center">
                        <?php if ($i === 0) : ?>
                            <?php if($tariffUser == Yii::$app->params['tariff'][0]) : ?>
                                <button class="btn btn-default btn-sm disabled">действующий тариф</button>
                            <?php else : ?>
                                <button class="btn btn-default btn-sm disabled">не доступен</button>
                            <?php endif; ?>
                        <?php else : ?>
                            <button
                                    type="button"
                                    class="btn btn-success btn-sm but-tariff"
                                    data-change_tariff="<?= Yii::$app->params['tariff'][$i] ?>"
                                <?php if ($tariffUser == Yii::$app->params['tariff'][$i]) : ?>
                                <?= "disabled"; ?>
                                    >действующий тариф
                                <?php else : ?>
                                    >перейти на тариф
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </td>
                <?php endfor; ?>
            </tr>
            </tbody>
        </table>
    </div>
</div>