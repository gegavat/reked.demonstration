<?php

namespace app\controllers;

use app\models\GoogleCampaign;
use app\models\YandexAd;
use app\models\YandexKeyword;
use Yii;
use app\components\YandexApi;
use app\models\YandexCampaign;
use app\models\YandexAdGroup;
use app\components\Parser;
use app\components\GoogleApi;
use app\models\GoogleAdGroup;
use app\models\ReplacementIdentity;
use app\models\GoogleTargeting;
use app\models\GoogleAd;


class CampaignUpdateController extends AppController {

    public function actionYandex($campaign_id) {
        $api = new YandexApi();

        // обновление имени кампании
        $campaignApi = $api->requestCampaigns($campaign_id);
        $campaignDb = YandexCampaign::find()->where(['campaign_id' => $campaign_id])->one();
        $campaignDb->campaign_name = $campaignApi[0]->Name;
        $campaignDb->updated_at = time();
        $campaignDb->update();

        //получение групп объявлений для кампании
        $adGroupsApi = $api->requestAdGroups($campaign_id);
        $adGroupsDb = YandexAdGroup::find()->where(['campaign_id' => $campaign_id])->all();
        $adGroupParts = Parser::splitElemsForUpdate($adGroupsApi, $adGroupsDb, 'Id', 'group_id');

        /**
         * сохранение новых групп объявлений и всех данных в этих группах
         */
        foreach ($adGroupParts->elemsForSave as $adGroup) {
            $adGroupDB = new YandexAdGroup();
            $adGroupDB->user_id = Yii::$app->user->getId();
            $adGroupDB->campaign_id = $campaign_id;
            $adGroupDB->group_id = $adGroup->Id;
            $adGroupDB->group_name = $adGroup->Name;
            $adGroupDB->save();

            //добавление группы объявлений в таблицу replacement_identity
            $replacementIdentityDB = new ReplacementIdentity();
            $replacementIdentityDB->user_id = Yii::$app->user->getId();
            $replacementIdentityDB->type = 'yandex';
            $replacementIdentityDB->ya_group_id = $adGroup->Id;
            $replacementIdentityDB->save();

            //загрузка ключевых фраз и запись в БД
            $keywords = $api->requestKeywords($adGroup->Id);
            // проверка на наличие таргетингов в группе объявлений
            foreach ($keywords as $keyword) {
                $keywordDB = new YandexKeyword();
                $keywordDB->user_id = Yii::$app->user->getId();
                $keywordDB->campaign_id = $campaign_id;
                $keywordDB->group_id = $adGroup->Id;
                $keywordDB->keyword_id = $keyword->Id;
                $keywordDB->keyword_text = $keyword->Keyword;
                $keywordDB->save();
            }

            //загрузка объявлений
            $ads = $api->requestAds($adGroup->Id);
            //обновление ссылок
            $api->updateUrls($ads, $adGroup->Id);
            //запись объявлений в БД
            foreach ($ads as $ad) {
                $adDB = new YandexAd();
                $adDB->user_id = Yii::$app->user->getId();
                $adDB->campaign_id = $campaign_id;
                $adDB->group_id = $adGroup->Id;
                $adDB->ad_id = $ad->Id;
                $adDB->ad_type = $ad->Type;
                $adDB->ad_href = Parser::parseUrl($ad->Href);
                $adDB->ad_title = $ad->Title;
                $adDB->ad_title2 = $ad->Title2;
                $adDB->ad_text = $ad->Text;
                $adDB->ad_creative_url = $ad->CreativeUrl;
                $adDB->save();
            }
        }

        /**
         * обновление уже имеющихся в БД групп объявлений и всех данных в этих группах
         */
        foreach ($adGroupParts->elemsForUpdate as $adGroup) {
            // обновление имени группы объявлений
            $adGroupDB = YandexAdGroup::find()->where(['group_id' => $adGroup->Id])->one();
            $adGroupDB->group_name = $adGroup->Name;
            $adGroupDB->update();

            /**
             * работа с ключевыми словами в обновляемых группах
             */
            $keywordsApi = $api->requestKeywords($adGroup->Id);
            $keywordsDb = YandexKeyword::find()->where(['group_id' => $adGroup->Id])->all();
            $keywordParts = Parser::splitElemsForUpdate($keywordsApi, $keywordsDb, 'Id', 'keyword_id');

            // сохранение новых ключевых слов
            foreach ($keywordParts->elemsForSave as $keyword) {
                $keywordsDb = new YandexKeyword();
                $keywordsDb->user_id = Yii::$app->user->getId();
                $keywordsDb->campaign_id = $campaign_id;
                $keywordsDb->group_id = $adGroup->Id;
                $keywordsDb->keyword_id = $keyword->Id;
                $keywordsDb->keyword_text = $keyword->Keyword;
                $keywordsDb->save();
            }
            // обновление ключевых слов
            foreach ($keywordParts->elemsForUpdate as $keyword) {
                // обновление типа и значения ключевого слова
                $keywordDb = YandexKeyword::find()->where(['keyword_id' => $keyword->Id])->one();
                $keywordDb->keyword_text = $keyword->Keyword;
                $keywordDb->update();
            }
            // удаление ключевых слов (удаленных в Yandex Direct) из БД
            foreach ($keywordParts->elemsForDelete as $keyword) {
                $keyword->delete();
            }

            /**
             * работа с объявлениями в обновляемых группах
             */
            $adsApi = $api->requestAds($adGroup->Id);
            $adsDb = YandexAd::find()->where(['group_id' => $adGroup->Id])->all();
            $adParts = Parser::splitElemsForUpdate($adsApi, $adsDb, 'Id', 'ad_id');

            //если есть объявления на сохранение, то обновляем ссылки
            if ( !empty($adParts->elemsForSave) ) {
                $api->updateUrls($adParts->elemsForSave, $adGroup->Id);
            }
            // сохранение новых объявлений
            foreach ($adParts->elemsForSave as $ad) {
                $adDB = new YandexAd();
                $adDB->user_id = Yii::$app->user->getId();
                $adDB->campaign_id = $campaign_id;
                $adDB->group_id = $adGroup->Id;
                $adDB->ad_id = $ad->Id;
                $adDB->ad_type = $ad->Type;
                $adDB->ad_href = Parser::parseUrl($ad->Href);
                $adDB->ad_title = $ad->Title;
                $adDB->ad_title2 = $ad->Title2;
                $adDB->ad_text = $ad->Text;
                $adDB->ad_creative_url = $ad->CreativeUrl;
                $adDB->save();
            }

            //если есть объявления на обновление, то обновляем ссылки
            if ( !empty($adParts->elemsForUpdate) ) {
                $api->updateUrls($adParts->elemsForUpdate, $adGroup->Id);
            }
            // обновление объявлений
            foreach ($adParts->elemsForUpdate as $ad) {
                // обновление типа, ссылки, заголовка1, заголовка2, описания, ссылки на изображение
                $adDB = YandexAd::find()->where(['ad_id' => $ad->Id])->one();
                $adDB->ad_type = $ad->Type;
                $adDB->ad_href = Parser::parseUrl($ad->Href);
                $adDB->ad_title = $ad->Title;
                $adDB->ad_title2 = $ad->Title2;
                $adDB->ad_text = $ad->Text;
                $adDB->ad_creative_url = $ad->CreativeUrl;
                $adDB->update();
            }

            // удаление объявлений (удаленных в Google Ads) из БД
            foreach ($adParts->elemsForDelete as $ad) {
                $ad->delete();
            }
        }

        /**
         * удаление групп объявлений (удаленных в Yandex Direct) из БД
         */
        foreach ($adGroupParts->elemsForDelete as $adGroup) {
            $adGroup->delete();
        }

        return 'Обновление завершено успешно';
    }

    public function actionGoogle($campaign_id) {
        $api = new GoogleApi();

        $cmpApi = $api->requestCampaigns($campaign_id);
        // обновление имени кампании
        $cmpDb = GoogleCampaign::find()->where(['campaign_id' => $campaign_id])->one();
        $cmpDb->campaign_name = $cmpApi->Name;
        $cmpDb->updated_at = time();
        $cmpDb->update();
        // обновление суффикса конечного url для кампании
        $api->setFinalUrlSuffix($campaign_id);

        // получение групп объявлений для кампании
        $adGroupsApi = $api->requestAdGroups($campaign_id);
        $adGroupsDb = GoogleAdGroup::find()->where(['campaign_id' => $campaign_id])->all();
        $adGroupParts = Parser::splitElemsForUpdate($adGroupsApi, $adGroupsDb, 'Id', 'group_id');

        /**
         * сохранение новых групп объявлений и всех данных в этих группах
         */
        foreach ($adGroupParts->elemsForSave as $adGroup) {
            $adGroupDB = new GoogleAdGroup();
            $adGroupDB->user_id = Yii::$app->user->getId();
            $adGroupDB->campaign_id = $campaign_id;
            $adGroupDB->group_id = $adGroup->Id;
            $adGroupDB->group_name = $adGroup->Name;
            $adGroupDB->save();

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
            // сохранение таргетингов в БД
            foreach ($targetings as $targeting) {
                $targetingDb = new GoogleTargeting();
                $targetingDb->user_id = Yii::$app->user->getId();
                $targetingDb->campaign_id = $campaign_id;
                $targetingDb->group_id = $adGroup->Id;
                $targetingDb->targeting_id = $targeting->Id;
                $targetingDb->targeting_type = $targeting->Type;
                $targetingDb->targeting_value = $targeting->Value;
                $targetingDb->save();
            }

            // загрузка объявлений
            $ads = $api->requestAds($adGroup->Id);
            // сохранение объявлений в БД
            foreach ($ads as $ad) {
                $adDb = new GoogleAd();
                $adDb->user_id = Yii::$app->user->getId();
                $adDb->campaign_id = $campaign_id;
                $adDb->group_id = $adGroup->Id;
                $adDb->ad_type = $ad->Type;
                $adDb->ad_id = $ad->Id;
                $adDb->ad_href = $ad->Href;
                $adDb->ad_header = $ad->Header;
                $adDb->ad_header2 = $ad->Header2;
                $adDb->ad_description = $ad->Description;
                $adDb->ad_preview_url = $ad->PreviewUrl;
                $adDb->save();
            }
        }

        /**
         * обновление уже имеющихся в БД групп объявлений и всех данных в этих группах
         */
        foreach ($adGroupParts->elemsForUpdate as $adGroup) {
            // обновление имени группы объявлений
            $adGroupDB = GoogleAdGroup::find()->where(['group_id' => $adGroup->Id])->one();
            $adGroupDB->group_name = $adGroup->Name;
            $adGroupDB->update();

            // проверка/обновление спец. параметра reked
            $api->updateCustomParameter($adGroup->Id, ReplacementIdentity::find()->where(['go_group_id' => $adGroup->Id])->one()->id);

            /**
             * работа с таргетингами в обновляемых группах
             */
            $targetingsApi = $api->requestTargetings($adGroup->Id);
            $targetingsDb = GoogleTargeting::find()->where(['group_id' => $adGroup->Id])->all();
            $targetingParts = Parser::splitElemsForUpdate($targetingsApi, $targetingsDb, 'Id', 'targeting_id');

            // сохранение новых таргетингов
            foreach ($targetingParts->elemsForSave as $targeting) {
                $targetingDb = new GoogleTargeting();
                $targetingDb->user_id = Yii::$app->user->getId();
                $targetingDb->campaign_id = $campaign_id;
                $targetingDb->group_id = $adGroup->Id;
                $targetingDb->targeting_id = $targeting->Id;
                $targetingDb->targeting_type = $targeting->Type;
                $targetingDb->targeting_value = $targeting->Value;
                $targetingDb->save();
            }
            // обновление таргетингов
            foreach ($targetingParts->elemsForUpdate as $targeting) {
                // обновление типа и значения таргетинга
                $targetingDb = GoogleTargeting::find()->where(['targeting_id' => $targeting->Id])->one();
                $targetingDb->targeting_type = $targeting->Type;
                $targetingDb->targeting_value = $targeting->Value;
                $targetingDb->update();
            }
            // удаление таргетингов (удаленных в Google Ads) из БД
            foreach ($targetingParts->elemsForDelete as $targeting) {
                $targeting->delete();
            }

            /**
             * работа с объявлениями в обновляемых группах
             */
            $adsApi = $api->requestAds($adGroup->Id);
            $adsDb = GoogleAd::find()->where(['group_id' => $adGroup->Id])->all();
            $adParts = Parser::splitElemsForUpdate($adsApi, $adsDb, 'Id', 'ad_id');

            // сохранение новых объявлений
            foreach ($adParts->elemsForSave as $ad) {
                $adDb = new GoogleAd();
                $adDb->user_id = Yii::$app->user->getId();
                $adDb->campaign_id = $campaign_id;
                $adDb->group_id = $adGroup->Id;
                $adDb->ad_type = $ad->Type;
                $adDb->ad_id = $ad->Id;
                $adDb->ad_href = $ad->Href;
                $adDb->ad_header = $ad->Header;
                $adDb->ad_header2 = $ad->Header2;
                $adDb->ad_description = $ad->Description;
                $adDb->ad_preview_url = $ad->PreviewUrl;
                $adDb->save();
            }
            // обновление объявлений
            foreach ($adParts->elemsForUpdate as $ad) {
                // обновление типа, ссылки, заголовка1, заголовка2, описания, ссылки на изображение
                $adDb = GoogleAd::find()->where(['ad_id' => $ad->Id])->one();
                $adDb->ad_type = $ad->Type;
                $adDb->ad_href = $ad->Href;
                $adDb->ad_header = $ad->Header;
                $adDb->ad_header2 = $ad->Header2;
                $adDb->ad_description = $ad->Description;
                $adDb->ad_preview_url = $ad->PreviewUrl;
                $adDb->update();
            }
            // удаление объявлений (удаленных в Google Ads) из БД
            foreach ($adParts->elemsForDelete as $ad) {
                $ad->delete();
            }
        }

        /**
         * удаление групп объявлений (удаленных в Google Ads) из БД
         */
        foreach ($adGroupParts->elemsForDelete as $adGroup) {
            $adGroup->delete();
        }

        return 'Обновление завершено успешно';
    }

}