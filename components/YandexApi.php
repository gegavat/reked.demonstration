<?php

namespace app\components;

use app\models\YandexBidder;
use Yii;
use yii\base\BaseObject;
use app\models\YandexAccount;
use yii\db\Exception;

class YandexApiException extends \Exception {}

class YandexApi extends BaseObject {

    const CUSTOM_PARAMETER_NAME = 'reked';

    protected $headers;

    protected $services = [
        'clients' => 'https://api.direct.yandex.com/json/v5/clients',
        'campaigns' => 'https://api.direct.yandex.com/json/v5/campaigns',
        'adgroups' => 'https://api.direct.yandex.com/json/v5/adgroups',
        'ads' => 'https://api.direct.yandex.com/json/v5/ads',
        'keywords' => 'https://api.direct.yandex.com/json/v5/keywords',
        'keywordbids' => 'https://api.direct.yandex.com/json/v5/keywordbids',
        'adimages' => 'https://api.direct.yandex.com/json/v5/adimages',
    ];

    protected function errorResult($url) {
        Yii::warning("Ошибка выполнения запроса к api yandex: $url");
        exit;
    }

    protected function errorApi($result) {
        $apiErr = $result->error;
        Yii::warning("Ошибка API {$apiErr->error_code}: {$apiErr->error_string} - {$apiErr->error_detail} (RequestId: {$apiErr->request_id})");
        throw new YandexApiException('Сервис Яндекса вернул ошибку: ' . $apiErr->error_string . ' - ' . $apiErr->error_detail);
    }

    protected function requestToApi($params, $url, $headers=true)
    {
        $body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $result = curl_exec($curl);
        if ( $result === false ) $this->errorResult($url);
        // Разделение HTTP-заголовков и тела ответа
        $responseHeadersSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseHeaders = explode("\r\n", substr($result, 0, $responseHeadersSize));
        $result = substr($result, $responseHeadersSize);
        curl_close($curl);

        $result = json_decode($result);
        if (isset($result->error)) $this->errorApi($result);
        if ($headers) return [$responseHeaders, $result];
        return $result;
    }

    public function __construct($token = null, $config = []) {
        if ( !$token ) {
            $request = Yii::$app->request;
            $ya_account = $request->get('ya_account', $request->post('ya_account'));
            $token = YandexAccount::find()->where(['account_id' => $ya_account])->one()->access_token;
        }

        $this->headers = [
            "Authorization: Bearer " . $token,
            "Accept-Language: ru",
            "Content-Type: application/json; charset=utf-8"
        ];

        // обновление токена (с помощью refresh-токена), если необходимо
        $accByToken = YandexAccount::findOne(['access_token' => $token]);
        if ( isset($accByToken->expires_in) && $accByToken->expires_in < Yii::$app->params['yandexRefreshTokenTime'] ) {
            $query = http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $accByToken->refresh_token,
                'client_id' => Yii::$app->params['yandexOAuthParam']['id'],
                'client_secret' => Yii::$app->params['yandexOAuthParam']['secret'],
            ]);
            $header = "Content-type: application/x-www-form-urlencoded";
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => $header,
                    'content' => $query
                ]
            ];
            $result = json_decode(file_get_contents('https://oauth.yandex.ru/token', false, stream_context_create($opts)));
            $accByToken->access_token = $result->access_token;
            $accByToken->expires_in = $result->expires_in;
            $accByToken->refresh_token = $result->refresh_token;
            $accByToken->update();
        }
        parent::__construct($config);
    }

    /**
     * Replacement Options
     */

    public function requestAccount() {
        $params = [
            'method' => 'get',
            'params' => [
                'FieldNames' => ['ClientId', 'Login']
            ]
        ];
        $result = $this->requestToApi($params, $this->services['clients'], false);
        return $result->result->Clients[0];
    }

    public function requestCampaigns($cmpId = null) {
        $params = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [
                    'Types' => ['TEXT_CAMPAIGN'],
                    'States' => ['ON', 'OFF', 'SUSPENDED', 'ENDED']
                ],
                'FieldNames' => ['Id', 'Name']
            ]
        ];
        if ($cmpId) $params['params']['SelectionCriteria'] += ['Ids' => [$cmpId]];

        $result = $this->requestToApi($params, $this->services['campaigns'], !$cmpId);
        return $cmpId ? $result->result->Campaigns : $result;
    }

    public function requestAdGroups($cmp_id) {
        $params = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [
                    'CampaignIds' => [$cmp_id],
                    'Types' => ['TEXT_AD_GROUP'],
                    'Statuses' => ['DRAFT', 'MODERATION', 'PREACCEPTED', 'ACCEPTED']
                ],
                'FieldNames' => ['Id', 'Name']
            ]
        ];
        $result = $this->requestToApi($params, $this->services['adgroups'], false);
        if ( !isset($result->result->AdGroups) ) return [];
        return $result->result->AdGroups;
    }

    public function requestAds($group_id) {
        $params = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [
                    'AdGroupIds' => [$group_id],
                    'Types' => ['TEXT_AD', 'IMAGE_AD'],
                    'States' => ['ON', 'OFF', 'SUSPENDED', 'OFF_BY_MONITORING'],
                    'Mobile' => 'NO'
                ],
                'FieldNames' => ['Id', 'Type', 'Subtype'/*, 'AdGroupId'*/],
                'TextAdFieldNames' => ['Title', 'Title2', 'Text', 'Href'],
                'TextImageAdFieldNames' => ['Href', 'AdImageHash'],
                'TextAdBuilderAdFieldNames' => ['Creative', 'Href'],
            ]
        ];
        $result = $this->requestToApi($params, $this->services['ads'], false);
        if ( !isset($result->result->Ads) ) return [];
        $ads = $result->result->Ads;
        $resultAd = [];
        foreach ($ads as $ad) {
            $pushObj = (object)[
                'Id' => $ad->Id,
                'Type' => null,
                'Href' => null,
                'Title' => null,
                'Title2' => null,
                'Text' => null,
                'CreativeUrl' => null,
            ];
            if ( ($ad->Type == 'TEXT_AD') && ($ad->Subtype == 'NONE') ) {
//                $pushObj->Type = $ad->Type;
                $pushObj->Type = 'TextAd';
                $pushObj->Href = $ad->TextAd->Href;
                $pushObj->Title = $ad->TextAd->Title;
                $pushObj->Title2 = $ad->TextAd->Title2;
                $pushObj->Text = $ad->TextAd->Text;
            }
            if ($ad->Subtype == 'TEXT_IMAGE_AD') {
//                $pushObj->Type = $ad->Subtype;
                $pushObj->Type = 'TextImageAd';
                $pushObj->Href = $ad->TextImageAd->Href;
                $pushObj->CreativeUrl = $this->requestUrlImage($ad->TextImageAd->AdImageHash);
//                $ad->TextImageAd->AdImageHash; //хэш изображения
            }
            if ($ad->Subtype == 'TEXT_AD_BUILDER_AD') {
//                $pushObj->Type = $ad->Subtype;
                $pushObj->Type = 'TextAdBuilderAd';
                $pushObj->Href = $ad->TextAdBuilderAd->Href;
                $pushObj->CreativeUrl = $ad->TextAdBuilderAd->Creative->ThumbnailUrl;
            }
            $resultAd[] = $pushObj;
        }

        return $resultAd;
    }

    public function requestUrlImage($imageHash) {
        $params = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [
                    'AdImageHashes' => [$imageHash],
                ],
                'FieldNames' => ['OriginalUrl'],
            ]
        ];
        $result = $this->requestToApi($params, $this->services['adimages'], false);
        if ( !isset($result->result->AdImages) ) return [];
        return $result->result->AdImages[0]->OriginalUrl;
    }

    public function requestKeywords($group_id) {
        $params = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [
                    'AdGroupIds' => [$group_id],
                    'States' => ['ON', 'OFF', 'SUSPENDED']
                ],
                'FieldNames' => ['Id', 'Keyword']
            ]
        ];
        $result = $this->requestToApi($params, $this->services['keywords'], false);
        if ( !isset($result->result->Keywords) ) return [];
        return $result->result->Keywords;
    }

    /**
     * добавление в ссылки объявлений параметра CUSTOM_PARAMETER_NAME и отправка в api
     * @param $ads
     * @param $group_id
     * @return bool
     */
    public function updateUrls($ads, $group_id) {
        $repl = \app\models\ReplacementIdentity::find()->where(['ya_group_id' => $group_id])->one();
        $ad_array = [];
        foreach ($ads as $ad) {
            //обновление ссылок и добавление спец параметра CUSTOM_PARAMETER_NAME
            $newUrl = Parser::markUrl($ad->Href, $repl->id, self::CUSTOM_PARAMETER_NAME);
            //если новая и старая ссылки одинаковые, то в апи ссылку не обновляем
            if ($ad->Href == $newUrl) continue;

            /*$type = $ad->Type;
            //менямем тип объявлений для отправки в api
            switch ($type) {
                case 'TEXT_AD': $type = 'TextAd';
                break;
                case 'TEXT_IMAGE_AD': $type = 'TextImageAd';
                break;
                case 'TEXT_AD_BUILDER_AD': $type = 'TextAdBuilderAd';
                break;
            }*/
            //собираем в массив id объявлений и их Href
            $adWithHref = [
                'Id' => $ad->Id,
                $ad->Type => [
                    'Href' => $newUrl
                ]
            ];
            $ad_array[] = $adWithHref;
        }
        //если массив пуст -> обновлять нечего
        if ( empty($ad_array) ) return false;
        //собираем массив для отправки в api
        $params = [
            'method' => 'update',
            'params' => [
                'Ads' => $ad_array
            ]
        ];
        //отправляем в api
        $this->requestToApi($params, $this->services['ads'], false);

        //проверка ответа
//        if ( !isset($result->result->UpdateResults) ) return false;
//        return true;
    }

    protected function searchAd($arrayOfObjects, $searchValue) {
        $neededObject = array_filter(
            $arrayOfObjects,
            function ($e) use ($searchValue) {
                return $e->Id == $searchValue;
            }
        );
        foreach ($neededObject as $obj) $neededObject = $obj;
        return $neededObject;
    }

    /**
     * Bidder Options
     */

    public function getBidCmps() {
        $params = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [
                    'Types' => ['TEXT_CAMPAIGN'],
                    'States' => ['ON', 'OFF', 'SUSPENDED', 'ENDED']
                ],
                'FieldNames' => ['Id', 'Name', 'StatusClarification'],
                'TextCampaignFieldNames' => ['BiddingStrategy']
            ]
        ];
        $result = $this->requestToApi($params, $this->services['campaigns'], false);
        if ( !isset($result->result->Campaigns) ) return [];
        return $result->result->Campaigns;
    }

    public function getKeywordBids($bidders) {
        $campaignIds = [];
        foreach ($bidders as $bidder) {
            if (!in_array($bidder->campaign_id, $campaignIds));
                $campaignIds[] = $bidder->campaign_id;
        }
        $params = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [
                    'CampaignIds' => $campaignIds,
                ],
                'FieldNames' => ['KeywordId', 'CampaignId'],
            ]
        ];

        if ( $bidders[0]->campaign_type === 'search' )
            $params['params']['SearchFieldNames'] = ['Bid', 'AuctionBids'];
        if ( $bidders[0]->campaign_type === 'network' )
            $params['params']['NetworkFieldNames'] = ['Bid', 'Coverage'];

        $KeywordBids = [];
        // новая итерация проходит, если кол-во ключ. фраз > 10000
        while (true) {
            $buf_result = $this->requestToApi($params, $this->services['keywordbids'], false);
            if ( !isset($buf_result->result->KeywordBids) ) break;
            foreach ( $buf_result->result->KeywordBids as $kwrd_bid ) {
                $KeywordBids[] = $kwrd_bid;
            }
            if ( isset($buf_result->result->LimitedBy) ) {
                $params['params']['Page']['Offset'] = $buf_result->result->LimitedBy;
            } else {
                break;
            }
        }
//        debugToFile($KeywordBids);
        return $KeywordBids;
    }

    public function checkBiddingStrategy($bidders) {
        $cmpIds = [];
        foreach ( $bidders as $bidder ) {
            if ( !in_array($bidder->campaign_id, $cmpIds) )
                $cmpIds[] = $bidder->campaign_id;
        }
        $params = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [
                    'Ids' => $cmpIds,
                    'Types' => ['TEXT_CAMPAIGN'],
                    'States' => ['ON', 'OFF', 'SUSPENDED', 'ENDED']
                ],
                'FieldNames' => ['Id'],
                'TextCampaignFieldNames' => ['BiddingStrategy']
            ]
        ];
        $result = $this->requestToApi($params, $this->services['campaigns'], false);
        $apiCampaigns = isset($result->result->Campaigns) ? $result->result->Campaigns : [];
        // параметры для последующей смены стратегии (если будет необходимо)
        $searchCmpParamsForUpdate = [];
        $networkCmpParamsForUpdate = [];
        // id кампаний, полученных через api (если какого-то id не было получено, эта кампания будет удалена из yandex_bidder)
        $apiCmpIds = [];
        // устанавливаем с какими типами бид-менеджеров идет работа (поиск/сети)
        if ( $bidders[0]->campaign_type === 'search' ) {
            foreach ( $apiCampaigns as $cmp ) {
                // если стратегия кампании не "ручное управление ставками", выставляем именно ее
                if ( $cmp->TextCampaign->BiddingStrategy->Search->BiddingStrategyType != 'HIGHEST_POSITION' ) {
                    // если показы в поиске для этой кампании были выключены, такой бид-менеджер будет удален из базы
                    if ( $cmp->TextCampaign->BiddingStrategy->Search->BiddingStrategyType === 'SERVING_OFF' ) {
                        // можно добавить извещение на email
                        continue;
                    }
                    $searchCmpParamsForUpdate[] = [
                        'Id' => $cmp->Id,
                        'TextCampaign' => [
                            'BiddingStrategy' => [
                                'Search' => [
                                    'BiddingStrategyType' => 'HIGHEST_POSITION'
                                ]
                            ]
                        ]
                    ];
                }
                $apiCmpIds[] = $cmp->Id;
            }
        } else {
            foreach ( $apiCampaigns as $cmp ) {
                // если стратегия кампании не "ручное управление ставками", выставляем именно ее
                if ($cmp->TextCampaign->BiddingStrategy->Network->BiddingStrategyType != 'MAXIMUM_COVERAGE') {
                    // если тип кампании был изменен с РСЯ на Поиск, такой бид-менеджер будет удален из базы
                    if ($cmp->TextCampaign->BiddingStrategy->Search->BiddingStrategyType !== 'SERVING_OFF') {
                        // можно добавить извещение на email
                        continue;
                    }
                    $networkCmpParamsForUpdate[] = [
                        'Id' => $cmp->Id,
                        'TextCampaign' => [
                            'BiddingStrategy' => [
                                'Network' => [
                                    'BiddingStrategyType' => 'MAXIMUM_COVERAGE'
                                ]
                            ]
                        ]
                    ];
                }
                $apiCmpIds[] = $cmp->Id;
            }
        }
        if ( !empty($searchCmpParamsForUpdate) ) {
            self::updateBiddingStrategy($searchCmpParamsForUpdate);
        }
        if ( !empty($networkCmpParamsForUpdate) ) {
            self::updateBiddingStrategy($networkCmpParamsForUpdate);
        }
        // сбор id кампаний, кот. сейчас нет в Яндексе, они будут удалены из таблицы yandex_bidder
        // также сюда попадут кампании, в кот. измененен тип: Поиск на Сети и наоборот
        $cmpIdsForDelete = array_filter($cmpIds, function($cmpId) use($apiCmpIds) {
            return !in_array($cmpId, $apiCmpIds);
        });
        foreach ( $cmpIdsForDelete as $cmpId )
            YandexBidder::findOne(['campaign_id' => $cmpId])->delete();
    }

    protected function updateBiddingStrategy($cmpParams) {
        $params = [
            'method' => 'update',
            'params' => [
                'Campaigns' => $cmpParams
            ]
        ];
        $this->requestToApi($params, $this->services['campaigns'], false);
    }

    public function setKeywordBids($KeywordBids) {
//        $KeywordBids[] = (object) [
//            'KeywordId' => 1234567,
//            'SearchBid' => 100000000
//        ];
        $kwrd_bids_parts = array_chunk($KeywordBids, 10000);
        $keywordIds = [];
        $setResult = (object)[
            'keywordIdsNumber' => null,
            'errors' => [],
            'warnings' => []
        ];
        for ($i = 0; $i<count($kwrd_bids_parts); $i++) {
            $params = [
                'method' => 'set',
                'params' => [
                    'KeywordBids' => $kwrd_bids_parts[$i]
                ]
            ];
            $buf_result = $this->requestToApi($params, $this->services['keywordbids'], false);
            foreach ($buf_result->result->SetResults as $elem) {
                if ( isset($elem->KeywordId) ) $keywordIds[] = $elem->KeywordId;
                if ( isset($elem->Errors) ) $setResult->errors[] = $elem->Errors;
                if ( isset($elem->Warnings) ) $setResult->warnings[] = $elem->Warnings;
            }
        }
        $setResult->keywordIdsNumber = count($keywordIds);
        return $setResult;
    }

}
