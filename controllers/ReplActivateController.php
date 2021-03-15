<?php

namespace app\controllers;

use app\components\Parser;
use app\components\Rules;
use app\models\EnabledDomain;
use app\models\GoogleCampaign;
use app\models\Mark;
use app\models\YandexCampaign;
use Yii;
use app\models\YandexAccount;
use app\models\GoogleAccount;

class ReplActivateController extends AppController {

    public function actionIndex(){
        $yaAccount = YandexAccount::findOne(['user_id' => Yii::$app->user->getId()]);
        $gAccount = GoogleAccount::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['mcc' => 0])
            ->one();
        if (!$yaAccount && !$gAccount) return $this->render('/check/no-accounts');

        $yaCampaigns = YandexCampaign::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        $gCampaigns = GoogleCampaign::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        if ( empty($yaCampaigns) && empty($gCampaigns) ) return $this->render('/check/no-campaigns');

        //запрос доменов
        $userId = Yii::$app->user->getId();
        $marks = Mark::find()->where(['user_id' => $userId])->all();

        // если размеченных страниц нет (были удалены), выдаем сообщение
        if ( empty($marks) )
            return $this->render('/check/empty-marks');

        $domains = Parser::getUniqDomains($marks);

        $enabledDomains = EnabledDomain::find()->where(['user_id' => $userId])->all();

        \app\assets\ReplActivateAsset::register($this->view);
        return $this->render('index', compact('domains', 'enabledDomains'));
    }

    public function actionCheckStatus($domain, $status){
        if ($status == 'enable'){
            if ( Rules::canAddDomain() ){
                $enabledDomainDB = new EnabledDomain();
                $enabledDomainDB->user_id = Yii::$app->user->getId();
                $enabledDomainDB->domain = $domain;
                $enabledDomainDB->save();
                return 'saved';
            } else {
                return 'deny';
            }
        } else {
            $enabledDomainDB = EnabledDomain::find()->where(['domain' => $domain])->one();
            if ( $enabledDomainDB ) {
                $enabledDomainDB->delete();
                return 'deleted';
            } else {
                return 'ignore';
            }
        }
    }

}