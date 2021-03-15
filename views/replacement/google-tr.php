<?php
/**
 * @var $targetings array
 * @var $curAds array
 */

use app\components\Parser;

$curAd = reset($curAds);

?>

<td class="ad-container">
    <div
        <?php if ( $curAd->ad_preview_url ) : ?>
            class="ad-google-image-container"
            style="background-image: url(<?= Parser::getFramePage($curAd->ad_preview_url) ?>)"
        <?php endif; ?>
    >
        <?php if ( $curAd->ad_header ) : ?>
            <div class="ad-tit"><?= $curAd->ad_header ?></div>
        <?php endif; ?>

        <?php if ( $curAd->ad_header2 ) : ?>
            <div class="ad-tit"><?= $curAd->ad_header2 ?></div>
        <?php endif; ?>

        <?php if ( $curAd->ad_description ) : ?>
            <div><?= $curAd->ad_description ?></div>
        <?php endif; ?>
    </div>

    <div>
        <a href="<?= $curAd->ad_href ?>"> <?= $curAd->ad_href ?> </a>
    </div>

    <?php if ( count($curAds) > 1 ) : ?>
        <div class="all-ads-link">
            <a href="#" class="data-ads" data-ads="<?= Parser::getAdsJson($curAds, 'google') ?>">
                все объявления в группе
            </a>
        </div>
    <?php endif; ?>

</td>

<td>
    <?php $kwrd_count = 0; ?>
    <?php foreach ( $targetings as $targeting ) : ?>
        <?php if ( $kwrd_count > 5 ) : ?>
            <?php
                echo '...';
                break;
            ?>
        <?php endif; ?>
        <?php
            echo $targeting->targeting_value;
            if ( next($targetings) ) {
                echo ',<br>';
            }
        ?>
        <?php $kwrd_count++; ?>
    <?php endforeach; ?>
</td>

