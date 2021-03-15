<?php

namespace app\models;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "yandex_bidder".
 *
 * @property int $id
 * @property int $user_id
 * @property string $account_id
 * @property string $campaign_id
 * @property string $campaign_type
 * @property string $strategy
 * @property string $traffic_volume
 * @property int $step
 * @property int $price
 * @property int $bid
 * @property int $price_limit
 * @property string $status
 *
 * @property User $user
 * @property YandexCampaign $campaign
 */
class YandexBidder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yandex_bidder';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'campaign_id', 'campaign_type', 'strategy', 'step', 'status', 'account_id'], 'required'],
            [['step', 'price', 'bid', 'price_limit'], 'integer'],
            [['campaign_type', 'status', 'strategy'], 'string'],
            ['strategy', 'permittedStrategies'],
            ['traffic_volume', 'permittedTrafficValues'],
            [['traffic_volume', 'price', 'bid', 'price_limit'], 'default', 'value' => null],
            ['price', 'comparePriceAndPriceLimit', 'when' => function($model) {
                return $model->campaign_type === 'search' && $model->strategy === 'max';
            }],
        ];
    }

    public function permittedStrategies($attribute, $params) {
        $values = ['max', 'custom'];
        if ( !in_array($this->$attribute, $values) ) {
            $this->addError($attribute, 'Такой стратегии не существует');
        }
    }

    public function permittedTrafficValues($attribute, $params) {
        if ( !in_array($this->$attribute, Yii::$app->params['bidderTrafficVolumes']) ) {
            $this->addError($attribute, 'Такой объем трафика не допустим');
        }
    }

    public function comparePriceAndPriceLimit($attribute, $params) {
        if ( $this->price > $this->price_limit ) {
            $this->addError($attribute, 'Ограничение ставки не должно быть меньше, чем списываемая цена');
        }
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
            'campaign_type' => 'Campaign Type',
            'strategy' => 'Strategy',
            'traffic_volume' => 'Traffic Volume',
            'step' => 'Step',
            'price' => 'Price',
            'bid' => 'Bid',
            'price_limit' => 'Price Limit',
            'status' => 'Status',
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
    public function getAccount()
    {
        return $this->hasOne(YandexAccount::className(), ['account_id' => 'account_id']);
    }
}
