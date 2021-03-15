<?php
/**
 * @var $cmpIds array
 * @var $strategy
 * @var $step
 * @var $bid
 * @var $status
 */
?>

<?php /* Выбор стратегии */ ?>
<div class="form-group">
    <label for="strategy">Выберите стратегию:</label>
    <select class="form-control" id="strategy">
        <option value="max" selected>Охват 100%</option>
    </select>
</div>

<div id="stragegy_max">

    <?php /* Выбор шага перебивки ставки */ ?>
    <div class="form-group">
        <label for="bid-step">Перебить рекомендованную ставку на:</label>
        <input type="text" class="bid-step"
               data-value="<?php if(!$status) echo Yii::$app->params['bidParams']['step']; else echo $step ?>"
        >
    </div>

    <?php /* Ставка */ ?>
    <div class="form-group">
        <label for="bid">При ставке не более:</label>
        <input type="text" id="bid" data-value="<?= $bid ?>">
    </div>

</div>

<hr>

<?php if ( !$status ) : ?>
	<button type="button" class="btn btn-primary btn-sm ya_bid_network_change_status" data-mode="activate">
		Активировать
	</button>
<?php endif; ?>
<?php if ( $status === 'disabled' ) : ?>
	<button type="button" class="btn btn-success btn-sm ya_bid_network_change_status" data-mode="enable">
		Включить
	</button>
<?php endif; ?>
<?php if ( $status === 'enabled' ) : ?>
	<button type="button" class="btn btn-danger btn-sm ya_bid_network_change_status" data-mode="disable">
		Выключить
	</button>
<?php endif; ?>
<?php if ( $status ) : ?>
	<button type="button" class="btn btn-primary btn-sm ya_bid_network_change_status" data-mode="update">
		Обновить
	</button>
<?php endif; ?>
<button type="button" class="btn btn-default btn-sm" id="ya-bid-cancel">
    Отмена
</button>
