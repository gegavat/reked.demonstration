<?php

namespace app\models;

use app\models\user\User;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "payment".
 *
 * @property int $id
 * @property int $user_id
 * @property int $sum
 * @property string $operation
 * @property string $pay_type
 * @property int $paid
 * @property int $created_at
 *
 * @property User $user
 */
class Payment extends \yii\db\ActiveRecord
{
    public $pay_add_sum;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payment';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['created_at'],
                ]
            ]
        ];
    }

    const SCENARIO_SAVETODB = 'saveToDb';
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['pay_add_sum', 'pay_type'];
        $scenarios[self::SCENARIO_SAVETODB] = ['user_id', 'sum', 'operation', 'pay_type', 'paid', 'created_at'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['pay_add_sum', 'required', 'message' => 'Необходимо указать сумму пополнения'],
            [
                'pay_add_sum',
                'integer',
                'message' => 'Необходимо ввести целое число',
                'min' => Yii::$app->params['minRefill'],
                'max' => Yii::$app->params['maxRefill'],
                'tooSmall' => 'Сумма пополнения не должна быть меньше ' . Yii::$app->params['minRefill'] . ' руб.',
                'tooBig' => 'Сумма пополнения не должна быть больше ' . Yii::$app->params['maxRefill'] . ' руб.',
            ],
            ['pay_type', 'required', 'message' => 'Необходимо выбрать способ оплаты'],
            ['pay_type', 'permittedPayTypes'],
            ['sum', 'required'],
            [
                'sum',
                'integer',
                'min' => Yii::$app->params['minRefill'] * Yii::$app->params['payMultiplier'],
                'max' => Yii::$app->params['maxRefill'] * Yii::$app->params['payMultiplier']
            ],
            [['user_id', 'operation'], 'required'],
            [['user_id', 'paid'], 'integer'],
            [['operation'], 'string'],
            [['created_at'], 'safe'],
            [['paid'], 'default', 'value' => 0],
        ];
    }

    public function permittedPayTypes($attribute, $params) {
        $values = ['yandex-money', 'yandex-card', 'coupon', 'undefined'];
        if ( !in_array($this->$attribute, $values) ) {
            $this->addError($attribute, 'Несуществующий тип пополнения счета');
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
            'sum' => 'Введите сумму',
            'operation' => 'Operation',
            'paid' => 'Paid',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
