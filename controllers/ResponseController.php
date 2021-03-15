<?php

namespace app\controllers;

use app\components\Parser;
use app\models\GeoLocation;
use app\models\GeoPage;
use app\models\TrafficNumber;
use Yii;
use yii\web\Controller;
use app\models\ReplacementIdentity;
use app\models\AuthAssignment;
use app\models\user\User;
use yii\web\Response;
use app\models\GeoReplacement;
use app\components\SxGeo;

class ResponseController extends Controller {

    /*public function beforeAction($action) {
        if ($action->id == 'repls') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }*/

    public function actionRepls() {
        // my.reked.loc/repls.js?reked=1210&uh=sdafgsgsdfgrt565

        // sleep(2);

        $jsCode = null;

        // РЕЖИМ ПРОСМОТРА при настройке подмен
        // Мультилендинговый Функционал
        if ( $rekedDi = Yii::$app->request->get('reked_di') ) {
            $replIdentity = ReplacementIdentity::find()
                ->where(['display_identity' => $rekedDi])
                ->with(['replacements' =>
                    function ($query) {
                        $query->with(['mark']);
                    }
                ])
                ->one();
            $jsCode = $this->getJsCode($replIdentity);
            // удаляем временный идентификатор, если он есть в базе
            if ($replIdentity) {
                $replIdentity->display_identity = null;
                $replIdentity->update();
            }
            goto endScript;
        }
        // Геолендинговый Функционал
        if ( $rekedGeoDi = Yii::$app->request->get('reked_geo_di') ) {
            $location = GeoLocation::find()
                ->where(['display_identity' => $rekedGeoDi])
                ->with(['replacements' =>
                    function ($query) {
                        $query->with(['mark']);
                    }
                ])
                ->one();
            $jsCode = $this->getJsCode($location);
            // удаляем временный идентификатор, если он есть в базе
            if ($location) {
                $location->display_identity = null;
                $location->update();
            }
            goto endScript;
        }

        // ОСНОВНОЙ РЕЖИМ
        // проверка наличия хеша пользователя
        if ( !$userHash = Yii::$app->request->get('uh') ) exit;
        if ( !$user = User::findOne(['user_hash' => $userHash]) ) exit;
        // проверка активности тарифа
        if ( !$user->tariff_activity ) {
            // если тариф не оплачен
            $jsCode = self::getJsCode();
            goto endScript;
        }
        // проверка запаса обрабатываемых переходов
        $traffic = TrafficNumber::findOne(['user_id' => $user->id]);
        $tariff = AuthAssignment::findOne(['user_id' => $user->id])->item_name;
        $permitedTrafficNumber = Yii::$app->params['permitedTrafficNumber'][$tariff];
        // стоит > чтобы прошла логика ==
        if ( $traffic->traffic_number > $permitedTrafficNumber ) {
            // если закончилось количество обрабатываемых переходов
            $jsCode = self::getJsCode();
            goto endScript;
        }

        // Мультилендинговый Функционал
        if ( $reked = Yii::$app->request->get('reked') ) {
            $replIdentity = ReplacementIdentity::find()
                ->where(['user_id' => $user->id])
                ->where(['id' => $reked])
                ->with(['replacements' =>
                    function ($query) {
                        $query->with(['mark']);
                    }
                ])
                ->one();
            // если МФ не нашел подмены для данного идентификатора, управление перейдет в ГФ
            if ( !empty($replIdentity->replacements) ) {
                $jsCode = $this->getJsCode($replIdentity);
                // если подмены настроены под эту группу, добавляем обр. переход
                self::trafficAdd($traffic, $permitedTrafficNumber, $user);
                goto endScript;
            }
        }

        // Геолендинговый Функционал
        if ( $page = Yii::$app->request->get('page') ) {
            $page = urldecode($page);
            // добавлена и активирована ли страница, с кот. идет запрос
            $enabledPageDb = GeoPage::find()
                ->where(['user_id' => $user->id])
                ->andWhere(['page' => $page])
                ->andWhere(['enabled' => 1])
                ->one();
            if ( !$enabledPageDb ) {
                $jsCode = self::getJsCode();
                goto endScript;
            }
            // добавлены ли локации под данную страницу
            $locations = GeoLocation::find()
                ->where(['user_id' => $user->id])
                ->andWhere(['page_id' => $enabledPageDb->id])
                ->all();
            if ( empty($locations) ) {
                $jsCode = self::getJsCode();
                goto endScript;
            }
            $geoTypes = array_column($locations, 'geo_type');
            $countryInGeoTypes = in_array('country', $geoTypes);
            $regionInGeoTypes = in_array('region', $geoTypes);
            $cityInGeoTypes = in_array('city', $geoTypes);

            $SxGeo = new SxGeo(Yii::getAlias('@webroot').'/../../SxGeoCity.dat');
            $clientIp = $_SERVER['HTTP_X_REAL_IP'];
            $geoInfo = $SxGeo->getCityFull($clientIp);

            // только страны
            if ( $countryInGeoTypes && !$regionInGeoTypes && !$cityInGeoTypes ) {
                $location = self::getLocation($user->id, $enabledPageDb->id, 'country', $geoInfo['country']['id']);
            }
            // только регионы
            if ( !$countryInGeoTypes && $regionInGeoTypes && !$cityInGeoTypes ) {
                $location = self::getLocation($user->id, $enabledPageDb->id, 'region', $geoInfo['region']['id']);
            }
            // только города
            if ( !$countryInGeoTypes && !$regionInGeoTypes && $cityInGeoTypes ) {
                $location = self::getLocation($user->id, $enabledPageDb->id, 'city', $geoInfo['city']['id']);
            }
            // страны + регионы
            if ( $countryInGeoTypes && $regionInGeoTypes && !$cityInGeoTypes ) {
                $location = self::getLocation($user->id, $enabledPageDb->id, 'region', $geoInfo['region']['id']);
                if ( !$location ) {
                    $location = self::getLocation($user->id, $enabledPageDb->id, 'country', $geoInfo['country']['id']);
                }
            }
            // страны + города
            if ( $countryInGeoTypes && !$regionInGeoTypes && $cityInGeoTypes ) {
                $location = self::getLocation($user->id, $enabledPageDb->id, 'city', $geoInfo['city']['id']);
                if ( !$location ) {
                    $location = self::getLocation($user->id, $enabledPageDb->id, 'country', $geoInfo['country']['id']);
                }
            }
            // регионы + города
            if ( !$countryInGeoTypes && $regionInGeoTypes && $cityInGeoTypes ) {
                $location = self::getLocation($user->id, $enabledPageDb->id, 'city', $geoInfo['city']['id']);
                if ( !$location ) {
                    $location = self::getLocation($user->id, $enabledPageDb->id, 'region', $geoInfo['region']['id']);
                }
            }
            // страны + регионы + города
            if ( $countryInGeoTypes && $regionInGeoTypes && $cityInGeoTypes ) {
                $location = self::getLocation($user->id, $enabledPageDb->id, 'city', $geoInfo['city']['id']);
                if ( !$location ) {
                    $location = self::getLocation($user->id, $enabledPageDb->id, 'region', $geoInfo['region']['id']);
                    if ( !$location ) {
                        $location = self::getLocation($user->id, $enabledPageDb->id, 'country', $geoInfo['country']['id']);
                    }
                }
            }

            $jsCode = $this->getJsCode($location);
            if ( $location ) {
                // если подмены настроены под эту локацию, добавляем обр. переход
                if ( !empty($location->replacements) ) {
                    self::trafficAdd($traffic, $permitedTrafficNumber, $user);
                }
            }
        }

        endScript:
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', ['application/javascript; charset=utf-8']);
        return $jsCode;
    }

    protected function getJsCode($replIdentityOrLocation=null) {
        if ( !$replIdentityOrLocation || empty($replIdentityOrLocation->replacements) ) {
            $jsCode = 'var rekedJson = null;';
        } else {
            $response = [];
            foreach ( $replIdentityOrLocation->replacements as $replacement ) {
                $add_obj = (object) [];
                $add_obj->type = $replacement->mark->type;
                $add_obj->selector_path = $replacement->mark->selector_path;
                if ( $replacement->mark->type === 'txt' ) {
                    $add_obj->html =  Parser::getHtmlFromDelta($replacement->delta);
                }
                if ( $replacement->mark->type === 'img' ) {
                    $add_obj->width = $replacement->mark->img_width;
                    $add_obj->height = $replacement->mark->img_height;
                    $add_obj->src = 'https://my.reked.ru' . Yii::getAlias('@image_url/') . $replacement->user_id . '/' . $replacement->image_name;
                }
                $response[] = $add_obj;
            }
            $jsCode = 'var rekedJson = "'  .  addslashes( json_encode($response) ) . '";';
        }
        return $jsCode;
    }

    protected function getLocation($userId, $pageId, $geoType, $geoId) {
        return GeoLocation::find()
            ->where(['user_id' => $userId])
            ->andWhere(['page_id' => $pageId])
            ->andWhere(['geo_type' => $geoType])
            ->andWhere(['geo_id' => $geoId])
            ->with(['replacements' =>
                function ($query) {
                    $query->with(['mark']);
                }
            ])
            ->one();
    }

    protected function trafficAdd($traffic, $permitedTrafficNumber, $user) {
        $traffic->traffic_number ++;
        $traffic->update();
        if ( $traffic->traffic_number == round($permitedTrafficNumber * 0.8) ) {
            // если количество переходов скоро закончится
            if ( $user->send_message ) {
                Yii::$app->mailer->compose('responseEndSoon', [
                    'username' => $user->username,
                    'permitedTrafficNumber' => $permitedTrafficNumber
                ])
                    ->setFrom(Yii::$app->params['supportEmail'])
                    ->setTo($user->email)
                    ->setSubject('Обработка подменяемых элементов скоро будет прекращена')
                    ->send();
            }
        }
        if ( $traffic->traffic_number == $permitedTrafficNumber ) {
            // если количество переходов закончилось
            if ( $user->send_message ) {
                Yii::$app->mailer->compose('responseEnd', ['username' => $user->username])
                    ->setFrom(Yii::$app->params['supportEmail'])
                    ->setTo($user->email)
                    ->setSubject('Обработка подменяемых элементов временно выключена')
                    ->send();
            }
        }
    }

}