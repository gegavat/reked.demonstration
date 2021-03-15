<?php
/**
 * @var $cmpIds array
 * @var $strategy
 * @var $traffic_volume
 * @var $step
 * @var $price
 * @var $bid
 * @var $price_limit
 * @var $status
 */

//debug ($status);
?>

<?php /* Выбор стратегии */ ?>
<div class="form-group">
    <label for="strategy">Выберите стратегию:</label>
    <select class="form-control" id="strategy">
        <?php if ( !$strategy && $status ) : ?>
            <option value="" disabled selected></option>
        <?php endif; ?>
        <option value="max" <?php if ($strategy==='max' || !$status) echo 'selected' ?>>Максимальный объем трафика</option>
        <option value="custom" <?php if ($strategy==='custom') echo 'selected' ?>>Установка объема трафика вручную</option>
    </select>
</div>

<div id="stragegy_max" <?php if ((!$strategy || $strategy==='custom') && $status) echo 'style="display: none"' ?>>

    <?php /* Выбор шага перебивки ставки (стратегии Max и Custom) */ ?>
    <div class="form-group">
        <label for="bid-step">Перебить прогноз ставки на:</label>
        <input type="text" class="bid-step"
               data-value="<?php if(!$status) echo Yii::$app->params['bidParams']['step']; else echo $step ?>"
        >
    </div>

    <?php /* Списываемая цена (стратегия Max) */ ?>
    <div class="form-group">
        <label for="price">При списываемой цене не более:</label>
        <input type="text" id="price" data-value="<?= $price ?>">
    </div>

    <?php /* Ограничение ставки (стратегия Max) */ ?>
    <div class="form-group">
        <label for="price-limit">Не делать ставки больше, чем:</label>
        <input type="text" id="price-limit"
               data-value="<?php if(!$status || $strategy==='custom') echo Yii::$app->params['bidParams']['priceLimit']; else echo $price_limit ?>"
        >
    </div>

</div>

<div id="stragegy_custom" <?php if (!$strategy || $strategy==='max') echo 'style="display: none"' ?>>

    <?php /* Выбор объема трафика (стратегия Custom) */ ?>
    <div class="form-group">
        <label>Выберите необходимый объем трафика:</label>
        <br>
        <div class="btn-group btn-group-sm traffic_volume">
            <?php foreach ( Yii::$app->params['bidderTrafficVolumes'] as $trafVolume ) : ?>
                <button class="btn <?php if($traffic_volume==$trafVolume) echo 'btn-primary'; else echo 'btn-default' ?>" type="button"><?=$trafVolume?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <?php /* Выбор шага перебивки ставки (стратегии Max и Custom) */ ?>
    <div class="form-group">
        <label for="bid-step">Перебить прогноз ставки на:</label>
        <input type="text" class="bid-step"
               data-value="<?php if(!$status) echo Yii::$app->params['bidParams']['step']; else echo $step ?>"
        >
    </div>

    <?php /* Ставка (стратегия Custom) */ ?>
    <div class="form-group">
        <label for="bid">При ставке не более:</label>
        <input type="text" id="bid" data-value="<?= $bid ?>">
    </div>

</div>

<hr>

<?php if ( !$status ) : ?>
	<button type="button" class="btn btn-primary btn-sm ya_bid_search_change_status" data-mode="activate">
		Активировать
	</button>
<?php endif; ?>
<?php if ( $status === 'disabled' ) : ?>
	<button type="button" class="btn btn-success btn-sm ya_bid_search_change_status" data-mode="enable">
		Включить
	</button>
<?php endif; ?>
<?php if ( $status === 'enabled' ) : ?>
	<button type="button" class="btn btn-danger btn-sm ya_bid_search_change_status" data-mode="disable">
		Выключить
	</button>
<?php endif; ?>
<?php if ( $status ) : ?>
	<button type="button" class="btn btn-primary btn-sm ya_bid_search_change_status" data-mode="update">
		Обновить
	</button>
<?php endif; ?>
<button type="button" class="btn btn-default btn-sm" id="ya-bid-cancel">
    Отмена
</button>
