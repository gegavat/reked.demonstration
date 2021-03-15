<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "yandex_ad".
 *
 * @property int $id
 * @property int $user_id
 * @property string $campaign_id
 * @property string $group_id
 * @property string $ad_type
 * @property string $ad_id
 * @property string $ad_href
 * @property string $ad_title
 * @property string $ad_title2
 * @property string $ad_text
 * @property string $ad_creative_url
 *
 * @property User $user
 * @property YandexCampaign $campaign
 * @property YandexAdGroup $group
 */
class YandexAd extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yandex_ad';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'campaign_id', 'group_id', 'ad_type', 'ad_id', 'ad_href'], 'required'],
            [['user_id'], 'integer'],
            [['ad_type', 'ad_href', 'ad_creative_url'], 'string'],
            [[/*'campaign_id', 'group_id', 'ad_id', */'ad_title', 'ad_title2', 'ad_text'], 'string', 'max' => 255],
            [['ad_id'], 'unique'],
            [['ad_type'], 'in', 'range' => ['TextAd', 'TextImageAd', 'TextAdBuilderAd']],
//            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
//            [['campaign_id'], 'exist', 'skipOnError' => true, 'targetClass' => YandexCampaign::className(), 'targetAttribute' => ['campaign_id' => 'campaign_id']],
//            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => YandexAdGroup::className(), 'targetAttribute' => ['group_id' => 'group_id']],
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
            'ad_title' => 'Ad Title',
            'ad_title2' => 'Ad Title2',
            'ad_text' => 'Ad Text',
            'ad_creative_url' => 'Ad Creative Url',
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
        return $this->hasOne(YandexCampaign::className(), ['campaign_id' => 'campaign_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(YandexAdGroup::className(), ['group_id' => 'group_id']);
    }
}
