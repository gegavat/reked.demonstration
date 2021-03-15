<?php

namespace app\controllers;

use app\components\addition\LoadStatus;
use app\components\GoogleApi;
use app\components\Parser;
use app\models\GoogleAd;
use app\models\GoogleCampaign;
use app\models\Mark;
use app\models\ReplacementIdentity;
use app\models\YandexAd;
use app\models\YandexAdGroup;
use app\models\YandexCampaign;
use app\models\YandexKeyword;
use Yii;
use app\components\YandexApi;
use app\models\YandexAccount;
use app\models\GoogleAccount;
use app\models\GoogleAdGroup;
use app\models\GoogleTargeting;

class CampaignController extends AppController {

    public function actionIndex() {
        $userId = Yii::$app->user->getId();
        $yaAccounts = YandexAccount::find()
			->where(['user_id' => $userId])
			->with(['yandexCampaigns' =>
				function ($query) {
					$query->with(['yandexAdGroups' =>
                        function ($q) {
                            $q->asArray();
                        }
                    ]);
				}
			])
			->all();
        $gAccounts = GoogleAccount::find()
			->where(['user_id' => $userId])
			->andWhere(['mcc' => false])
			->with(['googleCampaigns' =>
				function ($query) {
					$query->with(['googleAdGroups' =>
                        function($q) {
					        $q->asArray();
                        }
                    ]);
				}
			])
			->all();

        if ( empty($yaAccounts) && empty($gAccounts) ) return $this->render('/check/no-accounts');

        \app\assets\CampaignAsset::register($this->view);
        return $this->render('index', compact('yaAccounts', 'gAccounts'));
    }

    public function actionGetYaCmp($ya_account) {
        if ( !Yii::$app->request->isAjax ) exit;
        $api = new YandexApi();
        $cmpsApi = $api->requestCampaigns();
        $cmpsDb = YandexCampaign::findAll(['account_id' => $ya_account]);

        return $this->renderAjax('get-ya-cmp', compact('cmpsApi', 'cmpsDb'));
    }

    public function actionGetGCmp($g_account) {
        if ( !Yii::$app->request->isAjax ) exit;
        $api = new GoogleApi();
        $cmpsApi = $api->requestCampaigns();
        $cmpsDb = GoogleCampaign::findAll(['account_id' => $g_account]);

        return $this->renderAjax('get-g-cmp', compact('cmpsApi', 'cmpsDb'));
    }

    //загрузка кампаний, групп объявлений, объявлений и ключевых фраз из Яндекса
    public function actionYandexLoading() {
        $campaigns = json_decode(Yii::$app->request->post('campaigns'));

        $api = new YandexApi();
        //результат (объект) загрузки каждой кампании добавляется в массив
        $loadStatuses = [];
        /**
         * Запись в БД кампаний
         */
        foreach ($campaigns as $campaign) {
            // создание статус-объекта
            $loadStatus = new LoadStatus();
            //проверка на наличие групп объявлений в кампании
            $adGroups = $api->requestAdGroups($campaign->cmp_id);
            if ( empty($adGroups) ) {
                $loadStatus->errors->noAdGroups->campaignName = $campaign->cmp_name;
            } else {
                //сохранение кампании в БД
                $campaignDb = new YandexCampaign();
                $campaignDb->user_id = Yii::$app->user->getId();
                $campaignDb->account_id = Yii::$app->request->post('ya_account');
                $campaignDb->campaign_id = $campaign->cmp_id;
                $campaignDb->campaign_name = $campaign->cmp_name;
                $campaignDb->save();

                // добавляем имя кампании в статус-объект
                $loadStatus->campaignName = $campaign->cmp_name;

                /**
                 * загрузка групп объявлений и запись в БД
                 */
                foreach ($adGroups as $adGroup) {
                    $adGroupDB = new YandexAdGroup();
                    $adGroupDB->user_id = Yii::$app->user->getId();
                    $adGroupDB->campaign_id = $campaign->cmp_id;
                    $adGroupDB->group_id = $adGroup->Id;
                    $adGroupDB->group_name = $adGroup->Name;
                    $adGroupDB->save();

                    // добавляем id группы объявлений в статус-объект
                    $loadStatus->adGroupIds[] = $adGroup->Id;

                    /**
                     * добавление группы объявлений в таблицу replacement_identity
                     */
                    $replacementIdentityDB = new ReplacementIdentity();
                    $replacementIdentityDB->user_id = Yii::$app->user->getId();
                    $replacementIdentityDB->type = 'yandex';
                    $replacementIdentityDB->ya_group_id = $adGroup->Id;
                    $replacementIdentityDB->save();

                    /**
                     * загрузка ключевых фраз и запись в БД
                     */
                    $keywords = $api->requestKeywords($adGroup->Id);
                    // проверка на наличие таргетингов в группе объявлений
                    if (empty($keywords)) {
                        $loadStatus->errors->noTargetings->adGroupIds[] = $adGroup->Id;
                    } else {
                        foreach ($keywords as $keyword) {
                            $keywordDB = new YandexKeyword();
                            $keywordDB->user_id = Yii::$app->user->getId();
                            $keywordDB->campaign_id = $campaign->cmp_id;
                            $keywordDB->group_id = $adGroup->Id;
                            $keywordDB->keyword_id = $keyword->Id;
                            $keywordDB->keyword_text = $keyword->Keyword;
                            $keywordDB->save();
                            // добавляем id таргетинга в статус-объект
                            $loadStatus->targetingIds[] = $keyword->Id;
                        }
                    }
                    /**
                     * загрузка объявлений
                     */
                    $ads = $api->requestAds($adGroup->Id);
                    // проверка на наличие объявлений в группе объявлений
                    if (empty($ads)) {
                        $loadStatus->errors->noAds->adGroupIds[] = $adGroup->Id;
                    } else {
                        /**
                         * обновление ссылок
                         */
                        $api->updateUrls($ads, $adGroup->Id);
                        /**
                         * запись объявлений в БД
                         */
                        foreach ($ads as $ad) {
                            $adDB = new YandexAd();
                            $adDB->user_id = Yii::$app->user->getId();
                            $adDB->campaign_id = $campaign->cmp_id;
                            $adDB->group_id = $adGroup->Id;
                            $adDB->ad_id = $ad->Id;
                            $adDB->ad_type = $ad->Type;
                            $adDB->ad_href = Parser::parseUrl($ad->Href);
                            $adDB->ad_title = $ad->Title;
                            $adDB->ad_title2 = $ad->Title2;
                            $adDB->ad_text = $ad->Text;
                            $adDB->ad_creative_url = $ad->CreativeUrl;
                            $adDB->save();
                            // добавляем id объявления в статус-объект
                            $loadStatus->adIds[] = $ad->Id;
                        }
                    }
                }
            }
        $loadStatuses[] = $loadStatus;
        }
        return $this->renderAjax('load-ya-status', compact('loadStatuses'));
    }

    public function actionGoogleLoading() {
        $campaigns = json_decode(Yii::$app->request->post('campaigns'));
        $api = new GoogleApi();
        // результат (объект) загрузки каждой кампании добавляется в массив
        $loadStatuses = [];

        foreach ($campaigns as $campaign) {
            // создание статус-объекта
            $loadStatus = new LoadStatus();

            // проверка на наличие групп объявлений в кампании
            $adGroups = $api->requestAdGroups($campaign->cmp_id);
            if ( empty($adGroups) ) {
                $loadStatus->errors->noAdGroups->campaignName = $campaign->cmp_name;
            } else {
                // добавление суффикса конечного URL к кампаниям
                $api->setFinalUrlSuffix($campaign->cmp_id);

                // сохранение полученных через ajax кампаний в БД
                $campaignDb = new GoogleCampaign();
                $campaignDb->user_id = Yii::$app->user->getId();
                $campaignDb->account_id = Yii::$app->request->post('g_account');
                $campaignDb->campaign_id = $campaign->cmp_id;
                $campaignDb->campaign_name = $campaign->cmp_name;
                $campaignDb->save();

                // добавляем имя кампании в статус-объект
                $loadStatus->campaignName = $campaign->cmp_name;

                // сохранение групп объявлений
                foreach ($adGroups as $adGroup) {
                    $adGroupDB = new GoogleAdGroup();
                    $adGroupDB->user_id = Yii::$app->user->getId();
                    $adGroupDB->campaign_id = $campaign->cmp_id;
                    $adGroupDB->group_id = $adGroup->Id;
                    $adGroupDB->group_name = $adGroup->Name;
                    $adGroupDB->save();

                    // добавляем id группы объявлений в статус-объект
                    $loadStatus->adGroupIds[] = $adGroup->Id;

                    // добавление группы объявлений в таблицу replacement_identity
                    $replacementIdentityDb = new ReplacementIdentity();
                    $replacementIdentityDb->user_id = Yii::$app->user->getId();
                    $replacementIdentityDb->type = 'google';
                    $replacementIdentityDb->go_group_id = $adGroup->Id;
                    $replacementIdentityDb->save();

                    // добавление специального параметра для группы объявлений
                    $api->setCustomParameter($adGroup->Id, ReplacementIdentity::find()->where(['go_group_id' => $adGroup->Id])->one()->id);

                    // загрузка таргетингов
                    $targetings = $api->requestTargetings($adGroup->Id);
                    // проверка на наличие таргетингов в группе объявлений
                    if (empty($targetings)) {
                        $loadStatus->errors->noTargetings->adGroupIds[] = $adGroup->Id;
                    } else {
                        // сохранение таргетингов в БД
                        foreach ($targetings as $targeting) {
                            $targetingDb = new GoogleTargeting();
                            $targetingDb->user_id = Yii::$app->user->getId();
                            $targetingDb->campaign_id = $campaign->cmp_id;
                            $targetingDb->group_id = $adGroup->Id;
                            $targetingDb->targeting_id = $targeting->Id;
                            $targetingDb->targeting_type = $targeting->Type;
                            $targetingDb->targeting_value = $targeting->Value;
                            $targetingDb->save();
                            // добавляем id таргетинга в статус-объект
                            $loadStatus->targetingIds[] = $targeting->Id;
                        }
                    }
                    // загрузка объявлений
                    $ads = $api->requestAds($adGroup->Id);
                    // проверка на наличие объявлений в группе объявлений
                    if (empty($ads)) {
                        $loadStatus->errors->noAds->adGroupIds[] = $adGroup->Id;
                        continue;
                    } else {
                        // сохранение объявлений в БД
                        foreach ($ads as $ad) {
                            $adDb = new GoogleAd();
                            $adDb->user_id = Yii::$app->user->getId();
                            $adDb->campaign_id = $campaign->cmp_id;
                            $adDb->group_id = $adGroup->Id;
                            $adDb->ad_type = $ad->Type;
                            $adDb->ad_id = $ad->Id;
                            $adDb->ad_href = $ad->Href;
                            $adDb->ad_header = $ad->Header;
                            $adDb->ad_header2 = $ad->Header2;
                            $adDb->ad_description = $ad->Description;
                            $adDb->ad_preview_url = $ad->PreviewUrl;
                            $adDb->save();
                            // добавляем id объявления в статус-объект
                            $loadStatus->adIds[] = $ad->Id;
                        }
                    }
                }
            }
        $loadStatuses[] = $loadStatus;
        }
        return $this->renderAjax('load-g-status', compact('loadStatuses'));
    }

    public function actionYandexDelete($campaign_id) {
        if ( !Yii::$app->request->isAjax ) exit;
        $yaAds = YandexAd::find()->where(['user_id' => Yii::$app->user->getId()])->all();
        $gAds = GoogleAd::find()->where(['user_id' => Yii::$app->user->getId()])->all();
        $this->cascadeMarkDelete($yaAds, $gAds, $campaign_id, 'yandex');
        YandexCampaign::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['campaign_id' => $campaign_id])
            ->one()
            ->delete();
        return 'Кампания удалена';
    }

    public function actionGoogleDelete($campaign_id) {
        if ( !Yii::$app->request->isAjax ) exit;
        $yaAds = YandexAd::find()->where(['user_id' => Yii::$app->user->getId()])->all();
        $gAds = GoogleAd::find()->where(['user_id' => Yii::$app->user->getId()])->all();
        $this->cascadeMarkDelete($yaAds, $gAds, $campaign_id, 'google');
        GoogleCampaign::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['campaign_id' => $campaign_id])
            ->one()
            ->delete();
        return 'Кампания удалена';
    }

    protected function cascadeMarkDelete($yaAds, $gAds, $cmpId, $mode) {
        $deleteAds = [];
        $saveAds = [];
        foreach ( $yaAds as $ad ) {
            if ( $ad->campaign_id == $cmpId && $mode=='yandex' )
                $deleteAds[] = $ad->ad_href;
            else
                $saveAds[] = $ad->ad_href;
        }
        foreach ( $gAds as $ad ) {
            if ( $ad->campaign_id == $cmpId && $mode=='google' )
                $deleteAds[] = $ad->ad_href;
            else
                $saveAds[] = $ad->ad_href;
        }
        $deleteAds = array_filter($deleteAds, function($delAd) use ($saveAds) {
            return !in_array($delAd, $saveAds);
        });
        if ( !empty($deleteAds) )
            Mark::deleteAll(['in', 'url', $deleteAds]);
    }

}

