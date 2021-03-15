<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "google_ad".
 *
 * @property string $id
 * @property string $user_id
 * @property string $campaign_id
 * @property string $group_id
 * @property string $ad_type
 * @property string $ad_id
 * @property string $ad_href
 * @property string $ad_header
 * @property string $ad_header2
 * @property string $ad_description
 * @property string $ad_preview_url
 *
 * @property User $user
 * @property GoogleCampaign $campaign
 * @property GoogleAdGroup $group
 */
class GoogleAd extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'google_ad';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'campaign_id', 'group_id', 'ad_type', 'ad_id', 'ad_href'], 'required'],
            [['user_id'], 'integer'],
            [['ad_type', 'ad_href', 'ad_header', 'ad_header2', 'ad_description', 'ad_preview_url'], 'string'],
            [['ad_header', 'ad_header2', 'ad_description', 'ad_preview_url'], 'default', 'value' => null],
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
            'ad_type' => 'Ad Type',
            'ad_id' => 'Ad ID',
            'ad_href' => 'Ad Href',
            'ad_header' => 'Ad Header',
            'ad_header2' => 'Ad Header2',
            'ad_description' => 'Ad Description',
            'ad_preview_url' => 'Ad Preview Url',
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
    public function getCampaign()
    {
        return $this->hasOne(GoogleCampaign::className(), ['campaign_id' => 'campaign_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(GoogleAdGroup::className(), ['group_id' => 'group_id']);
    }
}
