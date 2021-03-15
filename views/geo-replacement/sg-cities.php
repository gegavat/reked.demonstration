<?php
/**
 * @var $sgCities \app\models\sypexgeo\City
 */
?>

<?php foreach ($sgCities as $sgCity) : ?>
    <label>
        <input name="sg-city" type="radio" value="<?= $sgCity->city_id ?>"> <?= $sgCity->name_ru ?>
    </label><br>
<?php endforeach; ?>