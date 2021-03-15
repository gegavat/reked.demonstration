<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "google_targeting".
 *
 * @property string $id
 * @property string $user_id
 * @property string $campaign_id
 * @property string $group_id
 * @property string $targeting_id
 * @property string $targeting_type
 * @property string $targeting_value
 *
 * @property User $user
 * @property GoogleCampaign $campaign
 * @property GoogleAdGroup $group
 */
class GoogleTargeting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'google_targeting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'campaign_id', 'group_id', 'targeting_id', 'targeting_type'], 'required'],
            [['user_id'], 'integer'],
            [['targeting_type', 'targeting_value'], 'string'],
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
            'targeting_type' => 'Targeting Type',
            'targeting_value' => 'Targeting Value',
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
