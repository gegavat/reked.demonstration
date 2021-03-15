<?php

namespace app\models;

use Yii;
use app\models\user\User;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "google_campaign".
 *
 * @property string $id
 * @property string $user_id
 * @property string $account_id
 * @property string $campaign_id
 * @property string $campaign_name
 * @property string $created_at
 * @property string $updated_at
 *
 * @property GoogleAd[] $googleAds
 * @property GoogleAdGroup[] $googleAdGroups
 * @property User $user
 * @property GoogleAccount $account
 * @property GoogleTargeting[] $googleTargetings
 */
class GoogleCampaign extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'google_campaign';
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
    public function getGoogleAds()
    {
        return $this->hasMany(GoogleAd::className(), ['campaign_id' => 'campaign_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGoogleAdGroups()
    {
        return $this->hasMany(GoogleAdGroup::className(), ['campaign_id' => 'campaign_id']);
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
        return $this->hasOne(GoogleAccount::className(), ['account_id' => 'account_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGoogleTargetings()
    {
        return $this->hasMany(GoogleTargeting::className(), ['campaign_id' => 'campaign_id']);
    }
}
