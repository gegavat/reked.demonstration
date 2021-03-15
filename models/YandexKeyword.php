<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "yandex_keyword".
 *
 * @property int $id
 * @property int $user_id
 * @property string $campaign_id
 * @property string $group_id
 * @property string $keyword_id
 * @property string $keyword_text
 *
 * @property User $user
 * @property YandexCampaign $campaign
 * @property YandexAdGroup $group
 */
class YandexKeyword extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yandex_keyword';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'campaign_id', 'group_id', 'keyword_id'/*, 'keyword_text'*/], 'required'],
            [['user_id'], 'integer'],
            [['keyword_text'], 'string'],
//            [['campaign_id', 'group_id', 'keyword_id'], 'string', 'max' => 255],
            [['keyword_id'], 'unique'],
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
            'keyword_id' => 'Keyword ID',
            'keyword_text' => 'Keyword Text',
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
