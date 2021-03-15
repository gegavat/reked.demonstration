<?php

namespace app\components;

use app\models\user\User;
use app\models\YandexAccount;
use app\models\YandexBidder;
use TrueBV\Punycode;
use yii\base\BaseObject;
use DBlackborough\Quill\Render as QuillRender;
use Yii;

class Parser extends BaseObject {

    public static function getGoFullImageUrl($urls) {
        $data = array_filter($urls, function($url) {
            return $url->getKey() === 'FULL';
        });
        foreach ($data as $elem) $data = $elem;
        return $data->getValue();
    }

    public static function getYandexUnits($headers) {
        foreach ($headers as $header) {
            if (preg_match('/Units:/', $header))
                $units = stristr(substr(stristr($header, '/'), 1), '/', true);
        }
        return $units;
    }

    public static function orderLoadedCmps($cmpsApi, $cmpsDb) {
        $orderedCmps = [];
        foreach ( $cmpsApi as $cmpApi ) {
            $loadedCmpApi = false;
            foreach ( $cmpsDb as $cmpDb ) {
                if ( $cmpDb->campaign_id == $cmpApi->Id ) {
                    $loadedCmpApi = true;
                }
            }
            if ( $loadedCmpApi ) {
                $cmpApi->isDownloaded = true;
                $orderedCmps[] = $cmpApi;
            } else {
                array_unshift($orderedCmps, $cmpApi);
            }
        }
        return $orderedCmps;
    }

    /**
     * Разбирает URL, удаляет GET параметры, возвращает URL типа https://www.example.ru:port/page
     * @param $href - ссылка
     * @return string
     */
    public static function parseUrl($href){
        $href = parse_url($href);
        $scheme = isset($href['scheme']) ? $href['scheme'] . '://' : '';
        $host   = isset($href['host']) ? $href['host'] : '';
        $port   = isset($href['port']) ? ':' . $href['port'] : '';
        $path   = isset($href['path']) ? $href['path'] : '';
        $newHref = preg_replace("#/$#", "", $scheme.$host.$port.$path);
        return urldecode($newHref);
    }

    public static function removeEndUrlSlash($url) {
        return preg_replace("#/$#", "", $url);
    }

    // для обновления кампаний. Разбивает группы об-ний, об-ния и таргетинги (ключевики) на части
    // для сохранения, обновления и удаления
	public static function splitElemsForUpdate($elemsApi, $elemsDb, $selectorApi, $selectorDb) {
		$elemsForSave = [];
        $elemsForUpdate = [];
        $elemsForDelete = [];
        foreach ($elemsApi as $elemApi) {
			$elemApiForSave = true;
            foreach ($elemsDb as $elemDb) {
                if ($elemApi->$selectorApi == $elemDb->$selectorDb) {
                    $elemsForUpdate[] = $elemApi;
                    $elemApiForSave = false;
                    break;
				}
            }
			if ($elemApiForSave)
				$elemsForSave[] = $elemApi;
        }
        foreach ($elemsDb as $elemDb) {
			$elemApiForDelete = true;
			foreach ($elemsApi as $elemApi) {
				if ($elemDb->$selectorDb == $elemApi->$selectorApi) {
					$elemApiForDelete = false;
					break;
				}
					
			}
			if ($elemApiForDelete)
				$elemsForDelete[] = $elemDb;
        };
        return (object)[
			'elemsForSave' => $elemsForSave,
			'elemsForUpdate' => $elemsForUpdate,
			'elemsForDelete' => $elemsForDelete
        ];
	}

	// добавляет к полученной ссылке get-параметр reked = $replId
	public static function markUrl($url, $replId, $param) {
        //разбиваем url на части
        $urlParts = parse_url($url);
        $scheme   = isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '';
        $host     = isset($urlParts['host']) ? $urlParts['host'] : '';
        $port     = isset($urlParts['port']) ? ':' . $urlParts['port'] : '';
        $path     = isset($urlParts['path']) ? $urlParts['path'] : '';
        $fragment = isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '';

        // проверяем есть ли GET-параметры
        if ( isset($urlParts['query']) ) {
            // разбиваем GET-параметры в массив $queryArr
            parse_str($urlParts['query'], $queryArr);
            // проверяем есть ли в GET-параметрах размечаемый параметр (reked)
            if (array_key_exists($param, $queryArr)) {
                // проверяем равен ли размечаемый параметр значению из таблицы repl_identity
                if ( $queryArr[$param] != $replId ) {
                    $queryArr[$param] = $replId;
                }
            } else {
                $queryArr += [$param => $replId];
            }
            $urlParts['query'] = self::mixQuery($queryArr);
        } else {
            $urlParts += ['query' => "$param={$replId}"];
        }
        $query = '?' . $urlParts['query'];

        //собираем новый урл с get параметром $param
        return $scheme.$host.$port.$path.$query.$fragment;
    }

    //собирает массив GET параметров в строку
    protected static function mixQuery($queryArr){
        $queryString = "";
        $i = 0;
        foreach ($queryArr as $key => $value) {
            $queryString .= ($i === 0) ? $key . '=' . $value : '&' . $key . '=' . $value;
            $i++;
        }
        return $queryString;
    }

    // формирует из полученных массивов с объявлениями массив с уникальными (неповторяющимися) ссылками
    public static function getUniqUrls($adYaUrls = [], $adGUrls = []){
        $pages = [];
        foreach ($adYaUrls as $url) {
            if ( !in_array($url->ad_href, $pages) )
                $pages[] = $url->ad_href;
        }
        foreach ($adGUrls as $url) {
            if ( !in_array($url->ad_href, $pages) )
                $pages[] = $url->ad_href;
        }
        return $pages;
    }

    // формирует из полученного массива с группами об-ний массив с уникальными (неповторяющимися) ссылками
    public static function getUniqUrlsFromAdGroups($adGroups, $adsName) {
        $pages = [];
        foreach ($adGroups as $adGroup) {
            foreach ($adGroup->$adsName as $ad) {
                if ( !in_array($ad->ad_href, $pages) )
                    $pages[] = $ad->ad_href;
            }
        }
        return $pages;
    }
    
    // превращает массив с одним элементом в этот элемент; если массив пуст, вернет null
    public static function arrayToElem(array $arr) {
		foreach ($arr as $elem)
			$arr = $elem;
		return $arr ? $arr : null;
	}

	// находит в массиве кампаний кампанию с указанным id
	public static function getCmpById($cmpArr, $id) {
        return self::arrayToElem(array_filter($cmpArr, function($cmp) use($id) {
            return $cmp->campaign_id == $id;
        }));
    }

    // возвращает объявления, ссылки которых ведут на страницу $page
    public static function filterAdsByPage($ads, $page) {
        return array_filter($ads, function($ad) use($page) {
            return $ad->ad_href == $page;
        });
    }

    // получает json-кодированный массив объявлений (с данными по объявлениям) (Яндекс)
    public static function getAdsJson($ads, $type) {
		$adsArray = [];
		foreach ( $ads as $ad ) {
			$adsArray[] = (object) [
				//'group_id' => $ad->group_id,
				//'ad_type' => $ad->ad_type,
				'header' => $type=='yandex' ? $ad->ad_title : $ad->ad_header,
				'header2' => $type=='yandex' ? $ad->ad_title2 : $ad->ad_header2,
				'description' => $type=='yandex' ? $ad->ad_text : $ad->ad_description,
				'bg_url' => $type=='yandex' ? $ad->ad_creative_url : self::getFramePage($ad->ad_preview_url),
				'href' => $ad->ad_href
			];
		}
		return self::insertJsonIntoHtml(json_encode($adsArray));
    }
    
    // преобразует кавычки json для вставки в html-атрибуты
    public static function insertJsonIntoHtml($json) {
		return htmlentities($json, ENT_QUOTES, 'UTF-8');
	}
    
    // получает массив id-шников идентифкаторов подмен для указанной кампании
    public static function getReplacementIdentityIdsForCampaign($cmp) {
		$adGroups = isset($cmp->yandexAdGroups) ? $cmp->yandexAdGroups : $cmp->googleAdGroups;
		$replacementIdentityIds = [];
		foreach ( $adGroups as $adGroup ) {
			$replacementIdentityIds[] = $adGroup->replacementIdentity->id;
		}
		return $replacementIdentityIds;
	}

	// получает ссылку для проксирования через proxy.reked.ru
    public static function getFramePage($url) {
        if (!$url) return null;
        $parsedUrl = parse_url($url);
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $hostUrl = preg_match("/[а-я]/i", $parsedUrl['host']) ?
            (new Punycode())->encode($parsedUrl['host']) :
            $parsedUrl['host'];
        $framePage = "https://proxy.reked.ru" . $path . "?reked_site=" . $parsedUrl['scheme'] . "://" . $hostUrl;
        return $framePage;
    }
	
	// получает текущую подмену в пересечении разметки mark и группы об-ний adGroup
	public static function getCurReplacementFromReplacementsByMarkAndAdGroup($replacements, $mark, $adGroup) {
		return self::arrayToElem( array_filter($replacements, function ($replacement) use ($mark, $adGroup) {
			if ($replacement->mark_id === $mark->id && $replacement->repl_identity_id === $adGroup->replacementIdentity->id)
				return true;
		}));
	}

    public static function getUniqDomains($marks){
        $domains = [];
        foreach ($marks as $mark) {
            $domain = parse_url($mark->url, PHP_URL_SCHEME).'://'.parse_url($mark->url, PHP_URL_HOST);
            if ( !in_array($domain, $domains) ) {
                $domains[] = $domain;
            }
        }
        return $domains;
    }

    public static function isEnabledDomain($domain, $enabledDomains){
        foreach ($enabledDomains as $enabledDomain){
            if ( $domain == $enabledDomain->domain ){
                return true;
            }
        }
        return false;
    }

	public static function getHtmlFromDelta($deltaJson) {
        $quill = new QuillRender($deltaJson);
        // удаляет все \n
        $html = preg_replace('/\\n/', '', $quill->render());
        // удаляет последний <br />
        $html = preg_replace('/<br \/>\<\/p>$/', '</p>', $html);
        // заменяет p на div
        $html = preg_replace('/p>/', 'div>', $html);
        // заменяет атрибут size="" атрибутом style="font-size:"
        $html = preg_replace('/size="([0-9]{2}px)"/', 'style="font-size:$1"', $html);
        return $html;
    }

    public static function getInsertCode(){
        return '<script ' . self::getInsertCodeSrc() . '></script>';
    }

    public static function getInsertCodeSrc() {
        $userHash = User::find()->where(['id' => Yii::$app->user->getId()])->one();
        return "src=\"//code.reked.ru/mcms_rek.js?uh=$userHash->user_hash\"";
    }

    // bidder options

    // сортировка кампаний на Поиск и РСЯ
    public static function orderByStrategyType($accounts) {
        $search = [];
        $network = [];
        foreach ( $accounts as $account ) {
            // оставляем только кампании РСЯ
            $apiNetworkCmps = array_filter($account->apiCmps, function($cmp) {
                return $cmp->TextCampaign->BiddingStrategy->Search->BiddingStrategyType === 'SERVING_OFF';
            });
            // оставляем только кампании Поиск, Поиск+РСЯ
            $apiSearchCmps = array_filter($account->apiCmps, function($cmp) {
                return $cmp->TextCampaign->BiddingStrategy->Search->BiddingStrategyType !== 'SERVING_OFF';
            });
            if ( !empty($apiNetworkCmps) ) {
                $networkAcc = clone $account;
                $networkAcc->apiCmps = $apiNetworkCmps;
                $network[] = $networkAcc;
            }
            if ( !empty($apiSearchCmps) ) {
                $searchAcc = clone $account;
                $searchAcc->apiCmps = $apiSearchCmps;
                $search[] = $searchAcc;
            }
        }
        return (object) [
            'search' => $search,
            'network' => $network
        ];
    }

    // добавляет в массив кампаний, полученных через API, состояния бид-менеджеров
    public static function mixApiWithBidder($apiCmps, $dbBidders) {
		foreach ( $apiCmps as $cmp ) {
			$cmp->BidderStatus = null;
			foreach ( $dbBidders as $bidder ) {
				if ( $cmp->Id == $bidder->campaign_id ) {
					$cmp->BidderStatus = $bidder->status;
					continue;
				}
			}
		}
		return $apiCmps;
	}
	
	// получает конечное значение свойства $propertyName после обработки всех $bidders
	public static function getMixedBidderProperty($bidders, $propertyName) {
		$propertyValues = [];
        foreach ( $bidders as $bidder ) {
            if ( !in_array($bidder->$propertyName, $propertyValues) && $bidder->$propertyName ) {
                $propertyValues[] = $bidder->$propertyName;
            }
        }
        return count($propertyValues) === 1
			? $propertyValues[0]
			: null;
	}

	// разбивает массив биддеров по аккаунтам
	public static function orderBiddersByAccounts($bidders) {
        $accObjects = [];
        $accountIds = [];
        foreach ($bidders as $bidder){
            if (!in_array($bidder->account_id, $accountIds)) {
                $accountIds[] = $bidder->account_id;
            }
        }
        foreach ($accountIds as $accountId) {
            $accObjects[] = (object)[
                'accountId' => $accountId,
                'accessToken' => null,
                'searchBidders' => [],
                'networkBidders' => []
            ];
        }
        foreach ($bidders as $bidder) {
            foreach ($accObjects as $accObj) {
                if ($bidder->account_id == $accObj->accountId) {
                    if ($bidder->campaign_type == 'search'){
                        $accObj->searchBidders[] = $bidder;
                    } else {
                        $accObj->networkBidders[] = $bidder;
                    }
                    continue;
                }
            }
        }
        $yaAccounts = YandexAccount::find()->where(['in', 'account_id', $accountIds])->all();
        foreach ($yaAccounts as $yaAccount) {
            foreach ($accObjects as $accObj) {
                if ($yaAccount->account_id == $accObj->accountId) {
                    $accObj->accessToken = $yaAccount->access_token; continue;
                }
            }
        }
        return $accObjects;
    }

    // получает последовательность локаций из Sypex Geo DB
    public static function getLocation($type, $id) {
        if ( $type === 'country' ) {
            $country = \app\models\sypexgeo\Country::find()
                ->where(['country_id' => $id])
                ->one();
            $loc = $country->name_ru;
        }
        if ( $type === 'region' ) {
            $region = \app\models\sypexgeo\Region::find()
                ->where(['region_id' => $id])
                ->one();
            $loc = $region->country->name_ru . ', ' . $region->name_ru;
        }
        if ( $type === 'city' ) {
            $city = \app\models\sypexgeo\City::find()
                ->where(['city_id' => $id])
                ->one();
            $loc = $city->region->country->name_ru . ', ' . $city->region->name_ru . ', ' . $city->name_ru;
        }
        return $loc;
    }

    // получает текущую подмену в пересечении строки location и столбца mark
    public static function getCurReplacementFromReplacementsByLocationAndMark($replacements, $location, $mark) {
        return self::arrayToElem( array_filter($replacements, function ($replacement) use ($mark, $location) {
            if ($replacement->mark_id === $mark->id && $replacement->location_id === $location->id)
                return true;
        }));
    }

}
