<?php

namespace app\models\user;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user".
 *
 * @property string $id
 * @property int $username
 * @property string $email
 * @property string $phone_number
 * @property string $password_hash
 * @property int $status
 * @property string $auth_key
 * @property int $created_at
 * @property int $updated_at
 * @property string $secret_key
 * @property string $user_hash
 * @property string $tariff_activity
 * @property string $prolongation
 * @property string $send_message
 * @property string $video_tip
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_NOT_ACTIVE = 1;
    const STATUS_ACTIVE = 10;

    public $password;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'email', 'password'], 'trim'],
            [['username', 'email', 'status'], 'required'],
            ['email', 'required'],
            ['phone_number', 'trim'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            ['password', 'required', 'on' => 'create'],
            // ['username', 'unique', 'message' => 'Это имя занято'],
            ['email', 'unique', 'message' => 'Эта почта уже зарегистрирована'],
            [['secret_key', 'user_hash'], 'unique'],
            [['tariff_activity', 'prolongation', 'send_message'], 'boolean'],
            ['tariff_activity', 'default', 'value' => 0],
            [['prolongation', 'send_message', 'video_tip'], 'default', 'value' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password Hash',
            'status' => 'Status',
            'auth_key' => 'Auth Key',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    // Поведения
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ]
            ],
        ];
    }

    // Поиск
    public static function findByUsername($username) {
        return static::findOne([
            'username' => $username
        ]);
    }

    public static function findByEmail($email) {
        return static::findOne([
            'email' => $email
        ]);
    }

    public static function findBySecretKey($key) {
        if (!static::isSecretKeyExpire($key)) {
            return null;
        }
        return static::findOne([
            'secret_key' => $key,
        ]);
    }

    // Хелперы
    public function setPassword($password) {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey() {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function generateSecretKey() {
        $this->secret_key = Yii::$app->security->generateRandomString().'_'.time();
    }

    public function removeSecretKey() {
        $this->secret_key = null;
    }

    public static function isSecretKeyExpire($key) {
        if ( empty($key) ) {
            return false;
        }
        $expire = Yii::$app->params['secretKeyExpire'];
        $parts = explode('_', $key);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }

    // Аутентификация пользователей
    public static function findIdentity($id)
    {
        return static::findOne([
            'id' => $id,
            'status' => self::STATUS_ACTIVE
        ]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

}
