<?php
/**
 * @var $balance int
 * @var $email string
 * @var $paid string
 * @var $dataProvider array
 */

use yii\bootstrap\Modal;
use yiister\gentelella\widgets\grid\GridView;
use yii\widgets\Pjax;
?>

<h3>Финансы
    <small class="text-info">Баланс, пополнение кошелька и история операций</small>
</h3>
<br>

<?php if ( $paid == 1 ) : ?>
<div class="alert alert-success alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <strong>Поздравляем!</strong> Оплата прошла успешно.
</div>
<?php endif; ?>

<?php if ( $paid == 2 ) : ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <strong>Что-то пошло не так!</strong> Оплата не прошла.
</div>
<?php endif; ?>

<div class="row finance">
    <div class="col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><?= $email ?></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                </ul>
                <div class="clearfix"></div>
            </div>

            <div class="x_content">
                <h2>
                    Баланс:
                    <i class="fa fa-rub"></i>
                    <span><?= $balance ?></span>
                </h2>
                <button type="button" class="btn btn-success but-payment" data-balance="<?= $balance ?>">
                    <i class="fa fa-plus"></i>Пополнить кошелек
                </button>
            </div>
        </div>
    </div>
</div>

<hr>
<div class="row">
    <div class="col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>История операций</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

<?php if ( empty($dbOperations) ) : ?>
    <div>Финансовых операций пока не было</div>
<?php else : ?>
    <?php Pjax::begin(); ?>
    <?=
    GridView::widget(
        [
            'dataProvider' => $dataProvider,
            'hover' => true,
            'tableOptions' => [
                'class' => 'table table-striped table-bordered'
            ],
            'columns' => [
                [
                    'attribute' => 'id',
                    'visible' => false
                ],
                [
                    'attribute' => 'created_at',
                    'header' => 'Дата',
                    'format' => ['date', 'dd.MM.yyyy'],
                    'contentOptions' => ['class' => 'col-md-4'],
                ],
                [
                    'header' => 'Сумма',
                    'class' => 'yii\grid\DataColumn',
                    'format' => 'html',
                    'contentOptions' => ['class' => 'col-md-4'],
                    'value' => function ($dbPayment) {
                        if ($dbPayment->operation == 'minus') {
                            $sign = '<i class="fa fa-minus"></i>';
                        } else {
                            $sign = '<i class="fa fa-plus"></i>';
                        }
                        $sum = number_format($dbPayment->sum / Yii::$app->params["payMultiplier"], 2, ",", " ");
                        return $sign.' '.$sum.' '.'<i class="fa fa-rub"></i>';
                    },
                ],
                [
                    'attribute' => 'operation',
                    'header' => 'Операция',
                    'contentOptions' => ['class' => 'col-md-4'],
                    'value' => function ($dbPayment) {
                        switch ($dbPayment->operation) {
                            case 'plus':
                                $operation = "Пополнение кошелька";
                                break;
                            case 'minus':
                                if ( $dbPayment->sum == Yii::$app->params['orderSetCost'] * Yii::$app->params['payMultiplier'] ) {
                                    $operation = "Настройка сервиса";
                                } else {
                                    $operation = "Оплата тарифа";
                                }
                                break;
                            case 'return':
                                $operation = "Возврат";
                                break;
                        }
                        return $operation;
                    },
                ],
            ],
        ]
    );
    ?>
    <?php Pjax::end(); ?>
<?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php /*
<?php Modal::begin([
    'size' => 'modal-md',
    'header' => '<h2>Для пополнения кошелька введите сумму</h2>',
    'id' => 'show_pay',
]); ?>
<?php Modal::end() ?>
*/ ?>
