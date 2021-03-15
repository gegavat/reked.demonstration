<?php

namespace app\models;

use app\models\user\User;
use Yii;

/**
 * This is the model class for table "mark".
 *
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $url
 * @property string $type
 * @property string $img_width
 * @property string $img_height
 * @property string $selector_path
 *
 * @property User $user
 * @property Replacement[] $replacements
 */
class Mark extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mark';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'name', 'url', 'type', 'selector_path'], 'required'],
            [['user_id', 'img_width', 'img_height'], 'integer'],
            [['url', 'type', 'selector_path'], 'string'],
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
            'name' => 'Name',
            'url' => 'Url',
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
    public function getReplacements()
    {
        return $this->hasMany(Replacement::className(), ['mark_id' => 'id']);
    }
}
