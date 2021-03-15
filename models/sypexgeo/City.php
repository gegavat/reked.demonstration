<?php

namespace app\models\sypexgeo;

use Yii;

/**
 * This is the model class for table "city" of db "sypexgeo"
 *
 * @property int $id
 * @property int $city_id
 * @property int $region_id
 * @property string $name_ru
 * @property string $name_en
 *
 *
 */
class City extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'city';
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
                    'city_id',
                    'region_id',
                    'name_ru',
                    'name_en',
                ],
                'required'
            ],
            [
                [
                    'city_id',
                ],
                'unique'
            ],
        ];
    }

    public function getRegion()
    {
        return $this->hasOne(Region::className(), ['region_id' => 'region_id']);
    }

}
