<?php

namespace app\components;

use yii\base\BaseObject;
use app\components\addition\SubBid;

class KeywordBidHandler extends BaseObject {

    // protected static $responseKeywordBids = [];
	
	public static function getSearchBids($keywordBids, $bidders) {
        $responseKeywordBids = [];
        //что делать если нет ключевых фраз

		// перебор СТАВОК из API
		foreach ($keywordBids as $keywordBid){
            // проверка действительно ли фраза является поисковой, без статуса "мало показов" и не является автотаргетингом
            if ( !self::isSearch($keywordBid) ) continue;
			// перебор ПОИСКОВЫХ БИДДЕРОВ
			foreach ($bidders as $bidder){
				// выявление какой биддер подключен к конкретной ключ. фразе
				if ($keywordBid->CampaignId == $bidder->campaign_id){
					if ($bidder->strategy == 'max') {
						$responseKeywordBids[] = self::getSearchBidMax($keywordBid, $bidder);
					}
					if ($bidder->strategy == 'custom') {
                        $responseKeywordBids[] = self::getSearchBidCustom($keywordBid, $bidder);
					}
				}
			}
		}
		return $responseKeywordBids;
	}

    public static function getNetworkBids($keywordBids, $bidders) {
        $responseKeywordBids = [];
        //что делать если нет ключевых фраз

        // перебор СТАВОК из API
        foreach ($keywordBids as $keywordBid){
            // проверка действительно ли фраза является РСЯ, без статуса "мало показов" и не является автотаргетингом
            if ( !self::isNetwork($keywordBid) ) continue;
            // перебор РСЯ БИДДЕРОВ
            foreach ($bidders as $bidder){
                $responseKeywordBids[] = self::getNetworkBid($keywordBid, $bidder);
            }
        }
        return $responseKeywordBids;
    }

    protected static function getNetworkBid($keywordBid, $bidder) {
        $sendKeywordBid = self::initSendKeywordBid($keywordBid, $bidder);
        // берем объект coverageItem, где [Probability] => 100
        $coverageItemBy100Prob = array_filter($keywordBid->Network->Coverage->CoverageItems, function($coverageItem) {
            return $coverageItem->Probability == 100;
        });
        //если есть данные по ставкам
        if ( !empty($coverageItemBy100Prob) ){
            foreach ($coverageItemBy100Prob as $elem) $coverageItemBy100Prob = $elem;
            if ( $bidder->bid >= $coverageItemBy100Prob->Bid ) {
                $sendKeywordBid->NetworkBid = $coverageItemBy100Prob->Bid + $bidder->step;
            } else {
                $sendKeywordBid->NetworkBid = $bidder->bid;
            }
        //если яндексу не удалось спрогнозировать стоимость клика для данной ключевой фразы
        } else {
            $sendKeywordBid->NetworkBid = $bidder->bid;
        }
        return $sendKeywordBid;
    }

    protected static function getSearchBidMax($keywordBid, $bidder) {
        $sendKeywordBid = self::initSendKeywordBid($keywordBid, $bidder);
        // сбор всех AuctionBidItems, в кот. ставка в бид-менеджере больше, чем списываемая цена
        $bidItemsByPrice = array_filter($keywordBid->Search->AuctionBids->AuctionBidItems, function($bidItem) use ($bidder) {
            return $bidItem->Price <= $bidder->price;
        });
        // 1) если ставки достаточно для какого-либо объема трафика
        if ( !empty($bidItemsByPrice) ) {
            // параметры в буфере
            $bufPrice = 0;
            $bidItemByPrice = null;
            // получение объекта AuctionBidItems
            // с наибольшим объемом трафика, для кот. списываемая цена не превышает ставку бид-менеджера
            foreach ( $bidItemsByPrice as $item ) {
                if ( $item->Price > $bufPrice  ) {
                    // запись в буфер списываемой цены для текущего объема трафика (для сравнения на след. итерации)
                    $bufPrice = $item->Price;
                    // сохранение самого объекта AuctionBidItems
                    $bidItemByPrice = $item;
                }
            }
            // если ставка не превышает ограничение в бид-менеджере price_limit
            if ( $bidItemByPrice->Bid <= $bidder->price_limit ) {
                $sendKeywordBid->SearchBid = $bidItemByPrice->Bid + $bidder->step;
                // если ставка превышает ограничение
            } else {
                $sendKeywordBid->SearchBid = $bidder->price_limit;
            }
            // 2) если ставки не достаточно даже для минимального трафика
        } else {
            $sendKeywordBid->SearchBid = $bidder->price;
        }
        return $sendKeywordBid;
    }

    protected static function getSearchBidCustom ($keywordBid, $bidder) {
        $sendKeywordBid = self::initSendKeywordBid($keywordBid, $bidder);
        // обработка объема трафика 100+
        if ( $bidder->traffic_volume === '100+' ) {
            // находим объект AuctionBidItem с наибольшим объемом трафика
            $maxTrVol = 0;
            $auctionBidItem = null;
            foreach ( $keywordBid->Search->AuctionBids->AuctionBidItems as $item ) {
                if ( $item->TrafficVolume > $maxTrVol ) {
                    $maxTrVol = $item->TrafficVolume;
                    $auctionBidItem = $item;
                }
            }
        // обработка объема трафика меньше 100, указанных в БД
        } else {
            // находим объект AuctionBidItem с объемом трафика, кот. указан в $bidder->traffic_volume
            // или, если такого нет, то ближайший меньший
            $auctionBidItem = self::getBidItemByTrafVal($keywordBid->Search->AuctionBids->AuctionBidItems, $bidder->traffic_volume);
        }
        if ( $bidder->bid >= $auctionBidItem->Bid  ) {
            $sendKeywordBid->SearchBid = $auctionBidItem->Bid + $bidder->step;
        } else {
            $sendKeywordBid->SearchBid = $bidder->bid;
        }
        return $sendKeywordBid;
    }

    protected static function getBidItemByTrafVal($AuctionBidItems, $traffic_value) {
        $bidItemByTraffic = null;
        foreach ( $AuctionBidItems as $item ) {
            if ( $item->TrafficVolume == $traffic_value )
                $bidItemByTraffic = $item;
        }
        if ( !$bidItemByTraffic ) {
            $trafVals = [];
            foreach ( $AuctionBidItems as $item ) {
                if ($item->TrafficVolume < $traffic_value)
                    $trafVals[] = $item->TrafficVolume;
            }
            arsort($trafVals);
            $bidItemByTraffic = array_filter($AuctionBidItems, function($item) use ($trafVals) {
                return $trafVals[0] == $item->TrafficVolume;
            });
            foreach ( $bidItemByTraffic as $elem ) $bidItemByTraffic = $elem;
        }
        return $bidItemByTraffic;
    }

    protected static function initSendKeywordBid($keywordBid, $bidder) {
        return (object) [
            'KeywordId' => $keywordBid->KeywordId,
            // закомментировать след. строки на продакшене
            /*'campaignId' => $bidder->campaign_id,
            'accountId' => $bidder->account_id,
            'bidderInfo' => (object) [
                'strategy' => $bidder->strategy,
                'step' => $bidder->step,
                'price' => $bidder->price,
                'price_limit' => $bidder->price_limit,
                'traffic_volume' => $bidder->traffic_volume,
                'bid' => $bidder->bid
            ],
            'searchApiInfo' => isset($keywordBid->Search) ? (object)[
                'keywordBid' => $keywordBid->Search->Bid,
                'keywordAuctionBidItems' => $keywordBid->Search->AuctionBids->AuctionBidItems
            ] : null,
            'networkApiInfo' => isset($keywordBid->Network) ? (object)[
                'keywordBid' => $keywordBid->Network->Bid,
                'keywordCoverageItems' => $keywordBid->Network->Coverage->CoverageItems
            ] : null,*/
        ];
    }

    protected static function isSearch($kwrdBid) {
        if ( !isset($kwrdBid->Search->AuctionBids) ) {
            // добавить логгирование ошибки
//            Yii::info("Обработка кампании 'Поиск'. Не получены ставки для ключевой фразы ID:$kwrd_bid->KeywordId", 'bidder_category');
            return false;
        } else
            return true;
    }

    protected static function isNetwork($kwrdBid) {
        if ( !isset($kwrdBid->Network->Coverage) ) {
            // добавить логгирование ошибки
//            Yii::info("Обработка кампании 'РСЯ'. Не получены ставки для ключевой фразы ID:$kwrd_bid->KeywordId", 'bidder_category');
            return false;
        } else
            return true;
    }
}
