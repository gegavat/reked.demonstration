<?php
/**
 * @var $sgRegions \app\models\sypexgeo\Region
 */
?>

<?php foreach ($sgRegions as $sgRegion) : ?>
    <label>
        <input name="sg-region" type="radio" value="<?= $sgRegion->region_id ?>"> <?= $sgRegion->name_ru ?>
    </label><br>
<?php endforeach; ?>