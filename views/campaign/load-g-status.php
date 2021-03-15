<?php
/**
 * @var $loadStatuses array
 */
?>
<?php foreach ( $loadStatuses as $loadStatus ) : ?>
    <?php if ( $cmpName = $loadStatus->errors->noAdGroups->campaignName ) : ?>
        <div class="text-danger">В кампании "<?=$cmpName?>" нет групп объявлений. Она не загружена!</div>
    <?php continue; endif; ?>
    <div>
        Из кампании <strong>"<?=$loadStatus->campaignName?>"</strong> загружено <br>
        групп объявлений: <?=count($loadStatus->adGroupIds)?>, <br>
        объявлений: <?=count($loadStatus->adIds)?>, <br>
        таргетингов: <?=count($loadStatus->targetingIds)?>.
    </div>

    <?php if ( !empty($loadStatus->errors->noAds->adGroupIds) ) : ?>
        <div class="text-danger">
            В некоторых группах объявлений нет объявлений!
            Для корректной работы нашей системы рекомендуем донастроить вашу рекламную кампанию.
        </div>
    <?php endif; ?>

    <?php if ( !empty($loadStatus->errors->noTargetings->adGroupIds) ) : ?>
        <div class="text-danger">
            В некоторых группах объявлений не настроены таргетинги (ключевые слова)!
            Для корректной работы нашей системы рекомендуем исправить ошибки в вашей рекламной кампании.
        </div>
    <?php endif; ?>
    <hr><br>
<?php endforeach; ?>
