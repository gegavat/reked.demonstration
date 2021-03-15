<?php

namespace app\models\sypexgeo;

use Yii;

/**
 * This is the model class for table "region" of db "sypexgeo"
 *
 * @property int $id
 * @property int $region_id
 * @property string $region_iso
 * @property string $country_iso
 * @property string $name_ru
 * @property string $name_en
 *
 *
 */
class Region extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'region';
    }

    public static function getDb() {
        return Yii::$app->get('db_sypexgeo');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                [
                    'region_id',
                    'region_iso',
                    'country_iso',
                    'name_ru',
                    'name_en',
                ],
                'required'
            ],
            [
                [
                    'region_id',
                    'region_iso',
                ],
                'unique'
            ],
        ];
    }

    public function getCountry()
    {
        return $this->hasOne(Country::className(), ['country_iso' => 'country_iso']);
    }

}
