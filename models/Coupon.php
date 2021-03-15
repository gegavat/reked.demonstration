<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "coupon".
 *
 * @property int $id
 * @property string $coupon
 * @property string $value сумма купона, умноженная на 1000000
 * @property int $used
 * @property int $user_id пользователь, использовавший купон
 * @property int $time_using время использования купона
 */
class Coupon extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupon';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['time_using'],
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
            [['coupon', 'value'], 'required'],
            [['value', 'used', 'user_id', 'time_using'], 'integer'],
            [['coupon'], 'string', 'max' => 6],
            [['coupon'], 'unique'],
            [['used'], 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'coupon' => 'Coupon',
            'value' => 'Value',
            'used' => 'Used',
            'user_id' => 'User ID',
            'time_using' => 'Time Using',
        ];
    }
}
