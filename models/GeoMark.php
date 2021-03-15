<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "geo_mark".
 *
 * @property int $id
 * @property int $user_id
 * @property int $page_id
 * @property string $name
 * @property string $type
 * @property int $img_width
 * @property int $img_height
 * @property string $selector_path
 *
 * @property User $user
 * @property GeoPage $page
 */
class GeoMark extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'geo_mark';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'page_id', 'name', 'type', 'selector_path'], 'required'],
            [['user_id', 'page_id', 'img_width', 'img_height'], 'integer'],
            [['type', 'selector_path'], 'string'],
            [['img_width', 'img_height'], 'default', 'value' => null],
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
            'name' => 'Name',
            'type' => 'Type',
            'img_width' => 'Img Width',
            'img_height' => 'Img Height',
            'selector_path' => 'Selector Path',
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
}
