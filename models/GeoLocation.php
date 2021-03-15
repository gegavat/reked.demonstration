<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "geo_location".
 *
 * @property int $id
 * @property int $user_id
 * @property int $page_id
 * @property string $geo_type
 * @property int $geo_id
 * @property string $display_identity
 *
 * @property User $user
 * @property GeoPage $page
 * @property GeoReplacement[] $geoReplacements
 */
class GeoLocation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'geo_location';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'page_id', 'geo_type', 'geo_id'], 'required'],
            [['user_id', 'page_id', 'geo_id'], 'integer'],
            [['geo_type'], 'string'],
            [['display_identity'], 'default', 'value' => null],
            ['display_identity', 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'page_id' => 'Page ID',
            'geo_type' => 'Geo Type',
            'geo_id' => 'Geo ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPage()
    {
        return $this->hasOne(GeoPage::className(), ['id' => 'page_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplacements()
    {
        return $this->hasMany(GeoReplacement::className(), ['location_id' => 'id']);
    }
}
