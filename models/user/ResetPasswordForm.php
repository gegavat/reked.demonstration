<?php

namespace app\models\user;

use Yii;
use yii\base\Model;
use yii\base\InvalidParamException;

class ResetPasswordForm extends Model {

    public $password;
    private $_user;

    public function rules() {
        return [
            ['password', 'trim'],
            ['password', 'required'],
            ['password', 'string', 'min' => 6, 'max' => 255],
        ];
    }

    public function attributeLabels() {
        return [
            'password' => 'Пароль'
        ];
    }

    public function resetPassword() {
        /* @var $user User */
        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removeSecretKey();
        return $user->save();
    }

    public function __construct($key, $config = []) {
        if(empty($key) || !is_string($key))
            throw new InvalidParamException('Ключ не может быть пустым.');
        $this->_user = User::findBySecretKey($key);
        if(!$this->_user)
            throw new InvalidParamException('Неверный ключ.');
        parent::__construct($config);
    }

}