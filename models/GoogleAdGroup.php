<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "google_ad_group".
 *
 * @property string $id
 * @property string $user_id
 * @property string $campaign_id
 * @property string $group_id
 * @property string $group_name
 *
 * @property GoogleAd[] $googleAds
 * @property User $user
 * @property GoogleCampaign $campaign
 * @property GoogleTargeting[] $googleTargetings
 * @property ReplacementIdentity[] $replacementIdentities
 */
class GoogleAdGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'google_ad_group';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'campaign_id', 'group_id', 'group_name'], 'required'],
            [['user_id'], 'integer'],
            [['group_id'], 'unique'],
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
            'campaign_id' => 'Campaign ID',
            'group_id' => 'Group ID',
            'group_name' => 'Group Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGoogleAds()
    {
        return $this->hasMany(GoogleAd::className(), ['group_id' => 'group_id']);
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
    public function getCampaign()
    {
        return $this->hasOne(GoogleCampaign::className(), ['campaign_id' => 'campaign_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGoogleTargetings()
    {
        return $this->hasMany(GoogleTargeting::className(), ['group_id' => 'group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplacementIdentity()
    {
        return $this->hasOne(ReplacementIdentity::className(), ['go_group_id' => 'group_id']);
    }
}
