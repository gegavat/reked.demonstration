<?php
/**
 * @var $balance int
 * @var $user string
 * @var $paid string
 * @var $dataProvider array
 */

use \yii\bootstrap\Modal;
use yii\helpers\Url;

$tariff = \app\models\AuthAssignment::find()->where(['user_id' => Yii::$app->user->getId()])->one();
?>

<br>

<?php if ( Yii::$app->request->get('passChange') == 'done' ) : ?>
    <div class="alert alert-success alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
        </button>
        <strong>Успешно!</strong> Пароль изменен.
    </div>
<?php endif; ?>

<?php if ( Yii::$app->request->get('passChange') == 'error' ) : ?>
    <div class="alert alert-danger alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
        </button>
        <strong>Ошибка!</strong> Текущий пароль введен не верно. Попробуйте еще раз.
    </div>
<?php endif; ?>

<div class="row" id="personal_area">
    <div class="col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Персональные данные</h2>
                <div class="clearfix"></div>
            </div>

            <div class="x_content">
                <h4>Имя: <b><?= Yii::$app->user->identity->username ?></b></h4>
                <h4>Email: <b><?= Yii::$app->user->identity->email ?></b></h4>
                <h4>Дата регистрации: <b><?= getRusDate(Yii::$app->user->identity->created_at) ?></b></h4>
                <h4>
                    Действующий тариф: <b><?= strtoupper($tariff->item_name) ?></b>
                    <a id="pers_area_change_tariff" href="<?=Url::to(['/payment/change-tariff'])?>" class="btn btn-primary">Сменить тариф</a>
                </h4>
                <hr>
                <h4>
                    <input type="checkbox" class="flat" id="video_tip" <?= Yii::$app->user->identity->video_tip? 'checked="checked"' : '' ?> />
                    Отображать видеоподсказки
                </h4>
                <h4>
                    <input type="checkbox" class="flat console" id="send_message" <?= Yii::$app->user->identity->send_message? 'checked="checked"' : '' ?> />
                    Получать уведомления от системы на email
                </h4>
                <h4>
                    <input type="checkbox" class="flat console" id="prolongation" <?= Yii::$app->user->identity->prolongation? 'checked="checked"' : '' ?> />
                    Автоматически продлевать тариф
                </h4>
                <button type="button" class="btn btn-success" id="but-password_change">
                    Изменить пароль
                </button>
            </div>
        </div>
    </div>
</div>

<?php Modal::begin([
    'size' => 'modal-lg',
    'header' => '<h2>Изменение пароля</h2>',
    'id' => 'show-password_change',
]); ?>
<?php Modal::end() ?>