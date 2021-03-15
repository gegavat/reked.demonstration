<?php

namespace app\components\addition;

use Yii;
use yii\base\BaseObject;

class SubBid extends BaseObject {

    public $objForSend;

    public function __construct($kwrd_id, $config = []) {
        $this->objForSend = (object) [
            'KeywordId' => $kwrd_id
        ];
        parent::__construct($config);
    }

    public function isSearch($kwrd_bid) {
        if ( !$kwrd_bid->Search->AuctionBids ) {
            Yii::info("Обработка кампании 'Поиск'. Не получены ставки для ключевой фразы ID:$kwrd_bid->KeywordId", 'bidder_category');
            return false;
        } else
            return true;
    }

    public function isNetwork($kwrd_bid) {
        if ( !$kwrd_bid->Network->Coverage ) {
            Yii::info("Обработка кампании 'РСЯ'. Не получены ставки для ключевой фразы ID:$kwrd_bid->KeywordId", 'bidder_category');
            return false;
        } else
            return true;
    }

    public function getBidItemByTrafVal($AuctionBidItems, $traffic_value) {
        $bidItemByTraffic = null;
        foreach ( $AuctionBidItems as $item ) {
            if ( $item->TrafficVolume == $traffic_value )
                $bidItemByTraffic = $item;
        }
        if ( !$bidItemByTraffic ) {
            $trafVals = [];
            foreach ( $AuctionBidItems as $item ) {
                if ($item->TrafficVolume < $traffic_value)
                    array_push($trafVals, $item->TrafficVolume);
            }
            arsort($trafVals);
            $bidItemByTraffic = array_filter($AuctionBidItems, function($item) use ($trafVals) {
                return $trafVals[0] == $item->TrafficVolume;
            });
            foreach ( $bidItemByTraffic as $elem ) $bidItemByTraffic = $elem;
        }
//        debug ($bidItemByTraffic);
        return $bidItemByTraffic;
    }

}