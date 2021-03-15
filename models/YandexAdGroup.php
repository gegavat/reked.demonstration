<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "yandex_ad_group".
 *
 * @property int $id
 * @property int $user_id
 * @property string $campaign_id
 * @property string $group_id
 * @property string $group_name
 *
 * @property ReplacementIdentity[] $replacementIdentities
 * @property YandexAd[] $yandexAds
 * @property User $user
 * @property YandexCampaign $campaign
 * @property YandexKeyword[] $yandexKeywords
 */
class YandexAdGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yandex_ad_group';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'campaign_id', 'group_id', 'group_name'], 'required'],
            [['user_id'], 'integer'],
            [[/*'campaign_id', 'group_id',*/ 'group_name'], 'string', 'max' => 255],
            [['group_id'], 'unique'],
//            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
//            [['campaign_id'], 'exist', 'skipOnError' => true, 'targetClass' => YandexCampaign::className(), 'targetAttribute' => ['campaign_id' => 'campaign_id']],
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
    public function getReplacementIdentity()
    {
        return $this->hasOne(ReplacementIdentity::className(), ['ya_group_id' => 'group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getYandexAds()
    {
        return $this->hasMany(YandexAd::className(), ['group_id' => 'group_id']);
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
    public function getYandexKeywords()
    {
        return $this->hasMany(YandexKeyword::className(), ['group_id' => 'group_id']);
    }
}
