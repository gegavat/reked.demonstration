<?php

namespace app\models\user;

use app\models\AuthAssignment;
use yii\base\InvalidParamException;
use yii\base\Model;
use Yii;
use app\models\Balance;
use app\models\TrafficNumber;
/* @property string $username */

class AccountActivation extends Model {
    /* @var $user \app\models\user\User */
    private $_user;

    public function __construct($key, $config = []) {
        if(empty($key) || !is_string($key))
            throw new InvalidParamException('Ключ не может быть пустым!');
        $this->_user = User::findBySecretKey($key);
        if(!$this->_user)
            throw new InvalidParamException('Не верный ключ!');
        parent::__construct($config);
    }

    public function activateAccount() {
        $user = $this->_user;
        $user->status = User::STATUS_ACTIVE;
        $user->removeSecretKey();
        // дополнительные действия при регистрации пользователя, подтвердившего свой e-mail
        $user->tariff_activity = 1;

        $balance = new Balance();
        $balance->user_id = $user->id;
        // $balance->balance = 0; // добавляется по умолчанию через правила валидации модели Balance
        $balance->save();

        $traffic = new TrafficNumber();
        $traffic->user_id = $user->id;
        // $traffic->traffic_number = 0; // добавляется по умолчанию через правила валидации модели TrafficNumber
        $traffic->save();

        $tariff = new AuthAssignment();
        $tariff->item_name = Yii::$app->params['tariff'][0];
        $tariff->user_id = $user->id;
        $tariff->save();

        return $user->save();
    }

    public function getUsername() {
        $user = $this->_user;
        return $user->username;
    }

    public function getUserId() {
        $user = $this->_user;
        return $user->id;
    }
}