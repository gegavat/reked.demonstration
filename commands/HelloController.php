<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return int Exit code
     */
    public function actionIndex($message = 'hello world')
    {
        echo $message . "\n";

        return ExitCode::OK;
    }

    public function actionTest()
    {
        $send = Yii::$app->mailer->compose('console/test')
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo('gegavat2007@rambler.ru')
            ->setSubject('Тема сообщения')
//            ->setTextBody('Текст сообщения')
//            ->setHtmlBody('<b>текст сообщения в формате HTML</b>')
            ->send();

        echo $send;

        return ExitCode::OK;
    }

    public function actionSypexGeoCountryInsert() {
        // вставка стран
        $countries = tsvToArray(__DIR__ . "/../../country.tsv");
        $print = [];
        foreach ($countries as $country) {
            if ( $country[1] == 'RU' || $country[1] == 'UA' ) {
                $db_country = new \app\models\sypexgeo\Country();
                $db_country->country_id = $country[0];
                $db_country->country_iso = $country[1];
                $db_country->continent_iso = $country[2];
                $db_country->name_ru = $country[3];
                $db_country->name_en = $country[4];
                if ( $db_country->save() )
                    $print[] = "Добавлена страна - $country[1]";
            }
        }
        print_r($print);
    }

    public function actionSypexGeoRegionInsert() {
        // вставка регионов
        $regions = tsvToArray(__DIR__ . "/../../region.tsv");
        $print = [];
        foreach ($regions as $region) {
            if ( $region[2] == 'RU' || $region[2] == 'UA' ) {
                $db_region = new \app\models\sypexgeo\Region();
                $db_region->region_id = $region[0];
                $db_region->region_iso = $region[1];
                $db_region->country_iso = $region[2];
                $db_region->name_ru = $region[3];
                $db_region->name_en = $region[4];
                if ( $db_region->save() )
                    $print[] = "Добавлен регион - $region[3]";
            }
        }
        print_r($print);
    }

    public function actionSypexGeoCityInsert() {
        // вставка городов
        $cities = tsvToArray(__DIR__ . "/../../city.tsv");
        $print = [];
        $regions = \app\models\sypexgeo\Region::find()->all();
        $regions = array_column($regions, 'region_id');
        foreach ($cities as $city) {
            if ( in_array($city[1], $regions) ) {
                $db_city = new \app\models\sypexgeo\City();
                $db_city->city_id = $city[0];
                $db_city->region_id = $city[1];
                $db_city->name_ru = $city[2];
                $db_city->name_en = $city[3];
                if ( $db_city->save() ) {
                    $print[] = "Добавлен город - $city[2]";
                } else {
                    $print[] = "Ошибка: city_id = $city[0]";
                }
            }
        }
        debugToFile($print);
    }

}
