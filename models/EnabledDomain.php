<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "enabled_domain".
 *
 * @property int $id
 * @property int $user_id
 * @property string $domain
 *
 * @property User $user
 */
class EnabledDomain extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'enabled_domain';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'domain'], 'required'],
            [['user_id'], 'integer'],
            [['domain'], 'string', 'max' => 255],
//            [['domain'], 'unique'],
            ['domain', 'uniqueByUser'],
        ];
    }

    public function uniqueByUser($attribute, $params)
    {
        $enabledDomains = EnabledDomain::findAll(['user_id' => Yii::$app->user->getId()]);
        foreach ($enabledDomains as $enabledDomain) {
            if ( $enabledDomain->domain === $this->$attribute )
                $this->addError($attribute, 'Такой домен уже активирован для этого пользователя');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'domain' => 'Domain',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
