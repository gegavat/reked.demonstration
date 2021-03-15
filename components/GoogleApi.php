<?php

namespace app\components;

use Google\AdsApi\AdWords\v201809\cm\AdGroup;
use Google\AdsApi\AdWords\v201809\cm\AdGroupAdService;
use Google\AdsApi\AdWords\v201809\cm\AdGroupAdStatus;
use Google\AdsApi\AdWords\v201809\cm\AdGroupOperation;
use Google\AdsApi\AdWords\v201809\cm\AdGroupService;
use Google\AdsApi\AdWords\v201809\cm\AdGroupCriterionService;
use Google\AdsApi\AdWords\v201809\cm\ApiException;
use Google\AdsApi\AdWords\v201809\cm\Campaign;
use Google\AdsApi\AdWords\v201809\cm\CampaignOperation;
use Google\AdsApi\AdWords\v201809\cm\CustomParameter;
use Google\AdsApi\AdWords\v201809\cm\CustomParameters;
use Google\AdsApi\AdWords\v201809\cm\Operator;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;
use Google\AdsApi\AdWords\v201809\mcm\ManagedCustomerService;
use Google\Auth\OAuth2;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\CampaignService;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Yii;
use yii\base\BaseObject;
use app\models\GoogleAccount;
use Google\AdsApi\AdWords\v201809\mcm\CustomerService;

class GoogleApi extends BaseObject {

    const PAGE_LIMIT = 500;
    const FINAL_URL_SUFFIX = 'reked={_reked}';
    const CUSTOM_PARAMETER_NAME = 'reked';
    /**
     * типы таргетингов
     */
    const KEYWORD = 'KEYWORD';
    const AGE_RANGE = 'AGE_RANGE';
    const GENDER = 'GENDER';
    const USER_INTEREST = 'USER_INTEREST';
    /**
     * типы объявлений
     */
    const TEXT_AD = 'TEXT_AD';
    const EXPANDED_TEXT_AD = 'EXPANDED_TEXT_AD';
    const RESPONSIVE_SEARCH_AD = 'RESPONSIVE_SEARCH_AD';
    const IMAGE_AD = 'IMAGE_AD';
    const GMAIL_AD = 'GMAIL_AD';
    const RESPONSIVE_DISPLAY_AD = 'RESPONSIVE_DISPLAY_AD';
    const MULTI_ASSET_RESPONSIVE_DISPLAY_AD = 'MULTI_ASSET_RESPONSIVE_DISPLAY_AD';

    protected $oauth2;
    protected $session;
    protected $authToken;
    protected $authorizationUri = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected $tokenCredentialUri = 'https://www.googleapis.com/oauth2/v4/token';

    public function getRefreshToken() {
        return $this->authToken['refresh_token'];
    }

    public function requestAccountInfo() {
        // получение email пользователя
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $this->authToken['access_token'];
        $userInfo = json_decode(file_get_contents($userInfoUrl));

        // через CustomerService получаем информацию о текущем аккаунте
        $customerService = (new AdWordsServices())->get($this->session, CustomerService::class);
        $selector = new Selector();
        $selector->setFields(['CustomerId', 'CanManageClients']);
        $selector->setOrdering([new OrderBy('CanManageClients', SortOrder::DESCENDING)]);
        $resultCustomers = $customerService->getCustomers($selector);

        // если аккаунт управляющий (mcc), запрашиваем через сервис ManagedCustomerService инф-ю об управляемых аккаунтах
        if ( $resultCustomers[0]->getCanManageClients() ) {
            $managedCustomerService = (new AdWordsServices())->get($this->session, ManagedCustomerService::class);
            $resultManagedCustomers = $managedCustomerService->get($selector);
        }

        /**
         * Создание объекта возврата с информацией об аккаунтах
         * В зависимости от того MCC аккаунт или нет заполняются разные свойства объекта
         */
        $retObj = (object)[
            'mccAcc' => (object)[
                'managerId' => false,
                'accIds' => [],
            ],
            'accId' => false,
            'email' => $userInfo->email,
        ];
        if ( $resultCustomers[0]->getCanManageClients() ) {
            foreach ( $resultManagedCustomers->getEntries() as $acc ) {
                if ( $acc->getCanManageClients() )
                    $retObj->mccAcc->managerId = $acc->getCustomerId();
                else
                    $retObj->mccAcc->accIds[] = $acc->getCustomerId();
            }
        } else {
            $retObj->accId = $resultCustomers[0]->getCustomerId();
        }
        return $retObj;
    }

    public function __construct($config = []) {
        $authorizationUri = 'https://accounts.google.com/o/oauth2/v2/auth';
        $tokenCredentialUri = 'https://www.googleapis.com/oauth2/v4/token';
        /**
         * Если в get/post-параметрах есть переданный g_account, то авторизоваться с помощью refresh-token,
         * если его нет - с помощью code-запроса (с перенаправлением на Google OAuth2)
         */
        $request = Yii::$app->request;
        if ( $g_account = $request->get('g_account', $request->post('g_account')) ) {
            $refreshToken = GoogleAccount::find()->where(['account_id' => $g_account])->one()->refresh_token;
            $this->oauth2 = new OAuth2([
                'authorizationUri' => $authorizationUri,
                'tokenCredentialUri' => $tokenCredentialUri,
                'clientId' => Yii::$app->params['googleOAuthParam']['id'],
                'clientSecret' => Yii::$app->params['googleOAuthParam']['secret'],
                'refresh_token' => $refreshToken
            ]);
            $this->session = (new AdWordsSessionBuilder())
                ->withDeveloperToken(Yii::$app->params['googleOAuthParam']['developerToken'])
                ->withClientCustomerId($g_account) // указывает на аккаунт внутри MCC-аккаунта
                ->withOAuth2Credential($this->oauth2)
                ->build();
        } else {
            $this->oauth2 = new OAuth2([
                'authorizationUri' => $authorizationUri,
                'tokenCredentialUri' => $tokenCredentialUri,
                'redirectUri' => 'https://my.reked.ru/get-google-token.html',
                'clientId' => Yii::$app->params['googleOAuthParam']['id'],
                'clientSecret' => Yii::$app->params['googleOAuthParam']['secret'],
                'scope' => ['https://www.googleapis.com/auth/adwords', 'https://www.googleapis.com/auth/userinfo.email']
            ]);
            // перенаправление пользователя на страницу соглашения
            if ( !Yii::$app->request->get('code') ) {
                $config = [
                    // тип доступа 'offline', для выдачи refresh_token
                    'access_type' => 'offline',
                    // каждый раз при обращении к серверу oauth2 будет запрос пользовательского согласия
                    'prompt' => 'consent'
                ];
                header('Location: ' . $this->oauth2->buildFullAuthorizationUri($config));
                exit;
            } else {
                $this->oauth2->setCode(Yii::$app->request->get('code'));
                $this->authToken = $this->oauth2->fetchAuthToken();
                $this->session = (new AdWordsSessionBuilder())
                    ->withDeveloperToken(Yii::$app->params['googleOAuthParam']['developerToken'])
                    ->withOAuth2Credential($this->oauth2)
                    ->build();
            }
        }
        parent::__construct($config);
    }

    public function requestCampaigns($cmpId = null) {
        $campaignService = (new AdWordsServices())->get($this->session, CampaignService::class);
        $selector = new Selector();
        $selector->setFields(['Id', 'Name']);
        if ($cmpId) {
            $selector->setPredicates(
                [new Predicate('Id', PredicateOperator::IN, [$cmpId])]
            );
        }
        $result = [];
        $page = $campaignService->get($selector);
        if ($page->getEntries() !== null) {
            foreach ($page->getEntries() as $campaign) {
                array_push($result, (object)[
                    'Id' => $campaign->getId(),
                    'Name' => $campaign->getName()
                ]);
            }
        }
        return $cmpId ? $result[0] : $result;
    }

    public function requestAdGroups($cmpId) {
        $adGroupService = (new AdWordsServices())->get($this->session, AdGroupService::class);
        $selector = new Selector();
        $selector->setFields(['Id', 'Name']);
        $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));
        $selector->setPredicates(
            [new Predicate('CampaignId', PredicateOperator::IN, [$cmpId])]
        );
        $totalNumEntries = 0;
        $result = [];
        do {
            $page = $adGroupService->get($selector);
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $adGroup) {
                    array_push($result, (object)[
                        'Id' => $adGroup->getId(),
                        'Name' => $adGroup->getName()
                    ]);
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);
        return $result;
    }
    
    public function requestTargetings($grpId) {
        $adGroupCriterionService = (new AdWordsServices())->get($this->session, AdGroupCriterionService::class);
        $selector = new Selector();
        $selector->setFields(['Id', 'CriteriaType', 'KeywordText']);
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));
        $selector->setPredicates([
            new Predicate('AdGroupId', PredicateOperator::IN, [$grpId]),
            new Predicate('CriteriaType', PredicateOperator::IN, [
                self::KEYWORD,
                self::AGE_RANGE,
                self::GENDER,
                self::USER_INTEREST
            ])
        ]);
        $totalNumEntries = 0;
        $result = [];
        do {
            $page = $adGroupCriterionService->get($selector);
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $adGroupCriterion) {
                    $criterionType = $adGroupCriterion->getCriterion()->getType();
                    $pushObj = (object)[
                        'Id' => $adGroupCriterion->getCriterion()->getId(),
                        'Type' => $criterionType,
                        'Value' => null
                    ];
                    switch ($criterionType) {
                        case self::KEYWORD:
                            $pushObj->Value = $adGroupCriterion->getCriterion()->getText();
                            break;
                        case self::AGE_RANGE:
                            $pushObj->Value = $adGroupCriterion->getCriterion()->getAgeRangeType();
                            break;
                        case self::GENDER:
                            $pushObj->Value = $adGroupCriterion->getCriterion()->getGenderType();
                            break;
                        case self::USER_INTEREST:
                            $pushObj->Value = $adGroupCriterion->getCriterion()->getUserInterestName();
                            break;
                    }
                    array_push($result, $pushObj);
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);
        return $result;
    }

    public function requestAds($grpId) {
        $adGroupAdService = (new AdWordsServices())->get($this->session, AdGroupAdService::class);
        $selector = new Selector();
        $selector->setFields([
            'Id', 'Url', 'CreativeFinalUrls',                                   // common fields
            'HeadlinePart1', 'HeadlinePart2', 'Description',                    // ETA
            'Headline', 'Description1',                                         // TA
            'ResponsiveSearchAdHeadlines', 'ResponsiveSearchAdDescriptions',    // RSA
            'Urls',                                                             // IA
            'MarketingImage', 'ShortHeadline', 'LongHeadline',                  // RDA
            'MultiAssetResponsiveDisplayAdMarketingImages',                     // MARDA
            'MultiAssetResponsiveDisplayAdHeadlines',
            'MultiAssetResponsiveDisplayAdLongHeadline',
            'MultiAssetResponsiveDisplayAdDescriptions',
            'GmailMarketingImage', 'MarketingImageHeadline',                    // GA
            'MarketingImageDescription',
        ]);
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));
        $selector->setPredicates([
            new Predicate('AdGroupId', PredicateOperator::IN, [$grpId]),
            new Predicate(
                'AdType',
                PredicateOperator::IN, [
                    self::EXPANDED_TEXT_AD,
                    self::TEXT_AD,
                    self::RESPONSIVE_SEARCH_AD,
                    self::IMAGE_AD,
                    self::RESPONSIVE_DISPLAY_AD,
                    self::MULTI_ASSET_RESPONSIVE_DISPLAY_AD,
                    self::GMAIL_AD,
                ]
            ),
            new Predicate(
                'Status',
                PredicateOperator::IN,
                [AdGroupAdStatus::ENABLED, AdGroupAdStatus::PAUSED]
            )
        ]);
        $totalNumEntries = 0;
        $result = [];
        do {
            $page = $adGroupAdService->get($selector);
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $adGroupAd) {
                    $ad = $adGroupAd->getAd();
                    $adGroupAdType = $ad->getType();
                    $adGroupAdHref = $ad->getFinalUrls() ? $ad->getFinalUrls()[0] : $ad->getUrl();
                    $pushObj = (object)[
                        'Id' => $ad->getId(),
                        'Type' => $adGroupAdType,
                        'Href' => Parser::parseUrl($adGroupAdHref),
                        'Header' => null,
                        'Header2' => null,
                        'Description' => null,
                        'PreviewUrl' => null,
                    ];
                    switch ($adGroupAdType) {
                        case self::EXPANDED_TEXT_AD:
                            $pushObj->Header = $ad->getHeadlinePart1();
                            $pushObj->Header2 = $ad->getHeadlinePart2();
                            $pushObj->Description = $ad->getDescription();
                            break;
                        case self::TEXT_AD:
                            $pushObj->Header = $ad->getHeadline();
                            $pushObj->Description = $ad->getDescription1();
                            break;
                        case self::RESPONSIVE_SEARCH_AD:
                            $pushObj->Header = $ad->getHeadlines()[0]->getAsset()->getAssetText();
                            $pushObj->Description = $ad->getDescriptions()[0]->getAsset()->getAssetText();
                            break;
                        case self::IMAGE_AD:
                            $pushObj->PreviewUrl = Parser::getGoFullImageUrl($ad->getImage()->getUrls());
                            break;
                        case self::RESPONSIVE_DISPLAY_AD:
                            $pushObj->Header = $ad->getShortHeadline();
                            $pushObj->Header2 = $ad->getLongHeadline();
                            $pushObj->Description = $ad->getDescription();
                            $pushObj->PreviewUrl = Parser::getGoFullImageUrl($ad->getMarketingImage()->getUrls());
                            break;
                        case self::MULTI_ASSET_RESPONSIVE_DISPLAY_AD:
                            $pushObj->Header = $ad->getHeadlines()[0]->getAsset()->getAssetText();
                            $pushObj->Header2 = $ad->getLongHeadline()->getAsset()->getAssetText();
                            $pushObj->Description = $ad->getDescriptions()[0]->getAsset()->getAssetText();
                            $pushObj->PreviewUrl = $ad->getMarketingImages()[0]->getAsset()->getFullSizeInfo()->getImageUrl();
                            break;
                        case self::GMAIL_AD:
                            $pushObj->Header = $ad->getMarketingImageHeadline();
                            $pushObj->Description = $ad->getMarketingImageDescription();
                            $pushObj->PreviewUrl = Parser::getGoFullImageUrl($ad->getMarketingImage()->getUrls());
                            break;
                    }
                    array_push($result, $pushObj);
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);
        return $result;
    }

    public function setFinalUrlSuffix($cmpId) {
        $campaignService = (new AdWordsServices())->get($this->session, CampaignService::class);
        $operations = [];
        $campaign = new Campaign();
        $campaign->setId($cmpId);
        $campaign->setFinalUrlSuffix(self::FINAL_URL_SUFFIX);
        $operation = new CampaignOperation();
        $operation->setOperand($campaign);
        $operation->setOperator(Operator::SET);
        $operations[] = $operation;
        $result = $campaignService->mutate($operations);
        return $result->getValue()[0];
    }

    public function setCustomParameter($grpId, $rpIdentId) {
        $adGroupService  = (new AdWordsServices())->get($this->session, AdGroupService::class);
        $operations = [];
        $adGroup = new AdGroup();
        $adGroup->setId($grpId);
        $adGroup->setUrlCustomParameters(
            new CustomParameters([
                new CustomParameter(self::CUSTOM_PARAMETER_NAME, $rpIdentId)
            ])
        );
        $operation = new AdGroupOperation();
        $operation->setOperand($adGroup);
        $operation->setOperator(Operator::SET);
        $operations[] = $operation;
        $result = $adGroupService->mutate($operations);
        return $result->getValue()[0];
    }

    public function updateCustomParameter($grpId, $rpIdentId) {
        $adGroupService  = (new AdWordsServices())->get($this->session, AdGroupService::class);
        $selector = new Selector();
        $selector->setFields(['UrlCustomParameters']);
        $selector->setPredicates(
            [new Predicate('Id', PredicateOperator::IN, [$grpId])]
        );
        $page = $adGroupService->get($selector);
        $mustUpdate = false;
        $urlCustomParameters = $page->getEntries()[0]->getUrlCustomParameters();
        // если спец. параметров нет, будет вызван метод setCustomParameter()
        if ( $urlCustomParameters ) {
            $parameters = $urlCustomParameters->getParameters();
            $customParamObj = array_filter($parameters, function($param) {
                return $param->getKey() == self::CUSTOM_PARAMETER_NAME;
            });
            // если в спец. параметрах нет параметра 'reked', будет вызван метод setCustomParameter()
            if ( !empty($customParamObj) ) {
                foreach ($customParamObj as $elem) $customParamObj = $elem;
                // если спец. параметр 'reked' не равен $rpIdentId, будет вызван метод setCustomParameter()
                if ( $customParamObj->getValue() != $rpIdentId )
                    $mustUpdate = true;
            } else {
                $mustUpdate = true;
            }
        } else {
            $mustUpdate = true;
        }
        if ( $mustUpdate ) self::setCustomParameter($grpId, $rpIdentId);
    }

}
