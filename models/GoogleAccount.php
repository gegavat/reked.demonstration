<?php

namespace app\models;

use Yii;
use app\models\user\User;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "google_account".
 *
 * @property string $id
 * @property string $user_id
 * @property string $account_id
 * @property string $login
 * @property string $refresh_token
 * @property string $mcc
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property GoogleCampaign[] $googleCampaign
 */
class GoogleAccount extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'google_account';
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
            [['user_id', 'account_id', 'login', 'refresh_token'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['login', 'refresh_token'], 'string', 'max' => 255],
            [['account_id'], 'unique'],
            ['mcc', 'boolean'],
            ['mcc', 'default', 'value' => false],
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
            'refresh_token' => 'Refresh Token',
            'mcc' => 'Mcc',
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
    public function getGoogleCampaigns()
    {
        return $this->hasMany(GoogleCampaign::className(), ['account_id' => 'account_id']);
    }
}
