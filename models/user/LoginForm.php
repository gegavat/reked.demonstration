<?php

namespace app\models\user;

use yii\base\Model;
use Yii;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $email;
    public $rememberMe = false;
    public $status;

    private $_user = false;

    public function rules() {
        return [
            [['username', 'password'], 'required', 'on' => 'default'],
            [['email', 'password'], 'required', 'on' => 'loginWithEmail'],
            ['email', 'email'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword']
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Ник',
            'email' => 'e-mail',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить меня'
        ];
    }

    public function getUser() {
        if ($this->_user === false):
            if($this->scenario === 'loginWithEmail'):
                $this->_user = User::findByEmail($this->email);
            else:
                $this->_user = User::findByUsername($this->username);
            endif;
        endif;
        return $this->_user;
    }

    public function validatePassword($attribute) {
        if (!$this->hasErrors()):
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)):
                $field = ($this->scenario === 'loginWithEmail') ? 'e-mail' : 'ник';
                $this->addError($attribute, 'Неправильный '.$field.' или пароль.');
            endif;
        endif;
    }

    public function login() {
        if ( $this->validate() ) {
            $this->status = ($user = $this->getUser()) ? $user->status : User::STATUS_NOT_ACTIVE;
            if ( $this->status === User::STATUS_ACTIVE )
                return Yii::$app->user->login($user, $this->rememberMe ? 3600*24*30 : 0);
            else
                return false;
        }
        else
            return false;
    }

    public function loginAdmin() {
        if ( $this->validate() ) {
            // администратор должен быть первым в базе данных в таблице user
            if ( $this->getUser()->getAttribute('id') === 1 ) {
                return Yii::$app->user->login($this->getUser());
            }
            else {
                Yii::$app->session->setFlash('error', 'У пользователя нет прав администратора');
                return false;
            }
        }
        else
            return false;
    }

}
