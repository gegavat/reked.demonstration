<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "replacement".
 *
 * @property string $id
 * @property string $user_id
 * @property string $repl_identity_id
 * @property string $mark_id
 * @property string $delta
 * @property string $image_name
 *
 * @property User $user
 * @property Mark $mark
 * @property ReplacementIdentity $replIdentity
 */
class Replacement extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'replacement';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'repl_identity_id', 'mark_id'], 'required'],
            [['user_id', 'repl_identity_id', 'mark_id'], 'integer'],
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
            'repl_identity_id' => 'Repl Identity ID',
            'mark_id' => 'Mark ID',
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
    public function getMark()
    {
        return $this->hasOne(Mark::className(), ['id' => 'mark_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplIdentity()
    {
        return $this->hasOne(ReplacementIdentity::className(), ['id' => 'repl_identity_id']);
    }
}
