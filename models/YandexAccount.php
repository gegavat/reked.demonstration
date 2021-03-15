<?php

namespace app\models;

use Yii;
use app\models\user\User;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "yandex_accounts".
 *
 * @property string $id
 * @property string $user_id
 * @property string $account_id
 * @property string $login
 * @property string $access_token
 * @property string $expires_in
 * @property string $refresh_token
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property YandexCampaign[] $yandexCampaign
 */
class YandexAccount extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yandex_account';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'account_id', 'login', 'access_token', 'expires_in', 'refresh_token'], 'required'],
            [['user_id', 'expires_in'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['login', 'access_token', 'refresh_token'], 'string', 'max' => 255],
            [['login'], 'unique'],
            [['account_id'], 'unique'],
            [['access_token'], 'unique'],
            [['refresh_token'], 'unique'],
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
            'account_id' => 'Account ID',
            'login' => 'Login',
            'access_token' => 'Access Token',
            'expires_in' => 'Expires In',
            'refresh_token' => 'Refresh Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
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
    public function getYandexCampaigns()
    {
        return $this->hasMany(YandexCampaign::className(), ['account_id' => 'account_id']);
    }
}
