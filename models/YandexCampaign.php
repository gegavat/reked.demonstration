<?php

namespace app\models;

use Yii;
use app\models\user\User;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "yandex_campaign".
 *
 * @property string $id
 * @property string $user_id
 * @property string $account_id
 * @property string $campaign_id
 * @property string $campaign_name
 * @property string $created_at
 * @property string $updated_at
 *
 * @property YandexAd[] $yandexAds
 * @property YandexAdGroup[] $yandexAdGroups
 * @property User $user
 * @property YandexAccount $account
 * @property YandexKeyword[] $yandexKeywords
 */
class YandexCampaign extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yandex_campaign';
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
            [['user_id', 'account_id', 'campaign_id', 'campaign_name'], 'required'],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['campaign_name'], 'string', 'max' => 255],
            [['campaign_id'], 'unique'],
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
            'campaign_id' => 'Campaign ID',
            'campaign_name' => 'Campaign Name',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getYandexAds()
    {
        return $this->hasMany(YandexAd::className(), ['campaign_id' => 'campaign_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getYandexAdGroups()
    {
        return $this->hasMany(YandexAdGroup::className(), ['campaign_id' => 'campaign_id']);
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
    public function getAccount()
    {
        return $this->hasOne(YandexAccount::className(), ['account_id' => 'account_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getYandexKeywords()
    {
        return $this->hasMany(YandexKeyword::className(), ['campaign_id' => 'campaign_id']);
    }
}
