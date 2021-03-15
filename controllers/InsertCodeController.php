<?php

namespace app\controllers;

use app\components\Parser;
use app\models\GoogleAccount;
use app\models\GoogleAd;
use app\models\GoogleCampaign;
use app\models\user\User;
use app\models\YandexAccount;
use app\models\YandexAd;
use app\models\YandexCampaign;
use Yii;

class InsertCodeController extends AppController {

    public function actionIndex(){
        $yaAccount = YandexAccount::findOne(['user_id' => Yii::$app->user->getId()]);
        $gAccount = GoogleAccount::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['mcc' => 0])
            ->one();
        if ( !$yaAccount && !$gAccount ) return $this->render('/check/no-accounts');

        $yaCampaigns = YandexCampaign::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        $gCampaigns = GoogleCampaign::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        if ( empty($yaCampaigns) && empty($gCampaigns) ) return $this->render('/check/no-campaigns');

        $adYaUrls = YandexAd::find()->where(['user_id' => Yii::$app->user->getId()])->select(['ad_href'])->all();
        $adGUrls = GoogleAd::find()->where(['user_id' => Yii::$app->user->getId()])->select(['ad_href'])->all();
        $pages = Parser::getUniqUrls($adYaUrls, $adGUrls);

        $script = Parser::getInsertCode();

        \app\assets\InsertCodeAsset::register($this->view);
        return $this->render ('index', compact('pages', 'script'));
    }

    public function actionCheck($page){
        if ( !Yii::$app->request->isAjax ) exit;
        //запрос html кода страницы
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $page);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($curl);
        curl_close($curl);

        //поиск кода
        if (stristr($result, Parser::getInsertCodeSrc()))
            return 1;
        else
            return 2;
    }
}