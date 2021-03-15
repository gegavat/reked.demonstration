<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "replacement_identity".
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $ya_group_id
 * @property string $go_group_id
 * @property string $display_identity
 *
 * @property Replacement[] $replacements
 * @property User $user
 * @property YandexAdGroup $yaGroup
 * @property GoogleAdGroup $goGroup
 */
class ReplacementIdentity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'replacement_identity';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'type'], 'required'],
            [['user_id'], 'integer'],
            [['type'], 'string'],
            [['ya_group_id', 'go_group_id', 'display_identity'], 'default', 'value' => null],
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
            'type' => 'Type',
            'ya_group_id' => 'Ya Group ID',
            'go_group_id' => 'Go Group ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplacements()
    {
        return $this->hasMany(Replacement::className(), ['repl_identity_id' => 'id']);
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
    public function getYaGroup()
    {
        return $this->hasOne(YandexAdGroup::className(), ['group_id' => 'ya_group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGoGroup()
    {
        return $this->hasOne(GoogleAdGroup::className(), ['group_id' => 'go_group_id']);
    }
}
