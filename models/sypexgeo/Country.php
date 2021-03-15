<?php

namespace app\models\sypexgeo;

use Yii;

/**
 * This is the model class for table "country" of db "sypexgeo"
 *
 * @property int $id
 * @property int $country_id
 * @property string $country_iso
 * @property string $continent_iso
 * @property string $name_ru
 * @property string $name_en
 *
 *
 */
class Country extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'country';
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
                    'country_id',
                    'country_iso',
                    'continent_iso',
                    'name_ru',
                    'name_en',
                ],
                'required'
            ],
            [
                [
                    'country_id',
                    'country_iso',
                ],
                'unique'
            ],
        ];
    }

}
