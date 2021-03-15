<?php

namespace app\components;

use yii\base\BaseObject;
use Yii;

class BitrixApi extends BaseObject {

    const CRM_HOST = 'reked.bitrix24.ru';             // Домен CRM системы
    const CRM_PORT = '443';                           // Порт сервера CRM. Установлен по умолчанию
    const CRM_PATH = '/crm/configs/import/lead.php';  // Путь к компоненту lead.rest
    const CRM_LOGIN = 'gegavat2007@rambler.ru';       // Логин пользователя CRM
    const CRM_PASSWORD = 'g1245u456';                 // Пароль пользователя CRM

    public static function addLead() {
        $postData = [
            'LOGIN' => self::CRM_LOGIN,
            'PASSWORD' => self::CRM_PASSWORD,
            'TITLE' => 'Сделка #' . Yii::$app->user->getId(),
            'NAME' => Yii::$app->user->identity->username,
            'EMAIL_WORK' => Yii::$app->user->identity->email,
            'PHONE_WORK' => Yii::$app->user->identity->phone_number
        ];
        $fp = fsockopen("ssl://".self::CRM_HOST, self::CRM_PORT, $errno, $errstr, 30);
        if ($fp) {
            $strPostData = '';
            foreach ($postData as $key => $value)
                $strPostData .= ($strPostData == '' ? '' : '&').$key.'='.urlencode($value);
            $str = "POST ".self::CRM_PATH." HTTP/1.0\r\n";
            $str .= "Host: ".self::CRM_HOST."\r\n";
            $str .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $str .= "Content-Length: ".strlen($strPostData)."\r\n";
            $str .= "Connection: close\r\n\r\n";
            $str .= $strPostData;
            fwrite($fp, $str);
            $result = '';
            while (!feof($fp)) {
                $result .= fgets($fp, 128);
            }
            fclose($fp);
            $response = explode("\r\n\r\n", $result);
//            return '<pre>'.print_r($response[1], 1).'</pre>';
        } else {
//            return 'Connection Failed! '.$errstr.' ('.$errno.')';
        }
    }

}