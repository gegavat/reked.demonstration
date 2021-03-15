<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\grid\GridView;

/**
 * @var $dataProvider yii\data\ActiveDataProvider
 * @var $model app\models\user\LoginForm
 */
?>
<div class="rk-admin-view">

    <div class="">

        <h2 style="font-size: 3em">От имени кого нужно авторизоваться?</h2>
        <hr>

        <?php
            echo GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
//                    ['class' => 'yii\grid\SerialColumn'],
//                    ['class' => 'yii\grid\RadioButtonColumn'],
//                    'id',
                    'username',
                    'email',
                    'phone_number',
                    [
                        'attribute' => 'created_at',
                        'label' => 'Зарегистрирован',
                        'value' => 'created_at',
                        'format' => ['date', 'php:d.m.Y H:i']
                    ],
                    [
                        'format' => 'raw',
                        'value' => function($data) {
                            return Html::a('войти',
                                [
                                    '/site/rk-admin-list',
                                    'id' => $data->getAttribute('id'),
                                ],
                                ['class' => 'btn btn-default btn-xs']);
                        }
                    ]
                ]
            ]);
        ?>


    </div>

</div>
