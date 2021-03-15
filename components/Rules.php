<?php

namespace app\components;

use app\models\AuthAssignment;
use app\models\GeoPage;
use Yii;
use yii\base\BaseObject;
use app\models\EnabledDomain;
use app\models\YandexBidder;

class Rules extends BaseObject {

    public static function canAddDomain() {
        $dbDomainNumber = EnabledDomain::find()->where(['user_id' => Yii::$app->user->getId()])->count();
        $userDomainNumber = Yii::$app->params['permitedDomainNumber'][self::getUserTariff()];
        return $dbDomainNumber < $userDomainNumber;
    }

    public static function canEnableGeoPage() {
        $dbEnabledGeoPageNumber = GeoPage::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['enabled' => 1])
            ->count();
        $userPageNumber = Yii::$app->params['permitedPageNumber'][self::getUserTariff()];
        return $dbEnabledGeoPageNumber < $userPageNumber;
    }

    public static function canActivateBidders($cmps) {
        $dbBidderNumber = YandexBidder::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['status' => 'enabled'])
            ->count();
        $userBidderNumber = Yii::$app->params['permitedBidderNumber'][self::getUserTariff()];
        return $dbBidderNumber + count($cmps) <= $userBidderNumber;
    }

    protected static function getUserTariff() {
        return AuthAssignment::findOne(['user_id' => Yii::$app->user->getId()])->item_name;
    }

}