<?php
/**
 * @var $keywords array
 * @var $curAds array
 */

use app\components\Parser;

$curAd = reset($curAds);

?>
<td class="ad-container">
    <div class="ad-in-cont">
        <?php if ( $curAd->ad_type == 'TextAd' ) : ?>
            <div class="ad-tit"><?= $curAd->ad_title ?></div>
            <?php if ( $curAd->ad_title2 ) : ?>
                <div class="ad-tit"><?= $curAd->ad_title2 ?></div>
            <?php endif; ?>
            <div><?= $curAd->ad_text ?></div>

        <?php elseif ( $curAd->ad_type == 'TextAdBuilderAd' || $curAd->ad_type == 'TextImageAd' ) : ?>
            <div class="ad-creative-container" style="background-image: url(<?= $curAd->ad_creative_url ?>)">
            </div>
        <?php endif; ?>

        <div>
            <a href="<?= $curAd->ad_href ?>"> <?= $curAd->ad_href ?> </a>
        </div>
    </div>

    <?php if ( count($curAds) > 1 ) : ?>
        <div class="all-ads-link">
            <a href="#" class="data-ads" data-ads="<?= Parser::getAdsJson($curAds, 'yandex') ?>">
                все объявления в группе
            </a>
        </div>
    <?php endif; ?>

</td>

<td>
    <?php $kwrd_count = 0; ?>
    <?php foreach ( $keywords as $kwrd ) : ?>
        <?php if ( $kwrd_count > 5 ) : ?>
            <?php
                echo '...';
                break;
            ?>
        <?php endif; ?>
        <?php
            echo $kwrd->keyword_text;
            if ( next($keywords) ) {
                echo ',<br>';
            }
        ?>
        <?php $kwrd_count++; ?>
    <?php endforeach; ?>
</td>

