<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "geo_replacement".
 *
 * @property int $id
 * @property int $user_id
 * @property int $page_id
 * @property int $mark_id
 * @property int $location_id
 * @property string $delta
 * @property string $image_name
 *
 * @property User $user
 * @property GeoPage $page
 * @property GeoMark $mark
 */
class GeoReplacement extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'geo_replacement';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'page_id', 'mark_id', 'location_id'], 'required'],
            [['user_id', 'page_id', 'mark_id', 'location_id'], 'integer'],
            [['delta', 'image_name'], 'string'],
            [['delta', 'image_name'], 'default', 'value' => null],
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
            'mark_id' => 'Mark ID',
            'location_id' => 'Location ID',
            'delta' => 'Delta',
            'image_name' => 'Image Name',
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
    public function getMark()
    {
        return $this->hasOne(GeoMark::className(), ['id' => 'mark_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLocation()
    {
        return $this->hasOne(GeoLocation::className(), ['id' => 'location_id']);
    }
}
