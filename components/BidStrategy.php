<?php

namespace app\components;

use yii\base\BaseObject;
use app\components\addition\SubBid;

class BidStrategy extends BaseObject {

    public function getSearchBidMax($keywordBid, $bidder) {

        // вспомогательный объект
        // хранит свойства для наполнения и методы проверок
        $sub_bid = new SubBid($keywordBid->KeywordId);

        // проверка действительно ли фраза является поисковой, без статуса "мало показов" и не является автотаргетингом
//        if ( !$sub_bid->isSearch($kwrd_bid) )
//            continue;

        // сбор всех AuctionBidItems, в кот. ставка в бид-менеджере больше, чем списываемая цена
        $bidItemsByPrice = array_filter($keywordBid->Search->AuctionBids->AuctionBidItems, function($bidItem) use ($bidder) {
            return $bidItem->Price <= $bidder->price;
        });
        //debug($bidItemsByPrice);

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
                $sub_bid->objForSend->SearchBid = $bidItemByPrice->Bid + $bidder->step;
                // если ставка превышает ограничение
            } else {
                $sub_bid->objForSend->SearchBid = $bidder->price_limit;
            }
            // 2) если ставки не достаточно даже для минимального трафика
        } else {
            $sub_bid->objForSend->SearchBid = $bidder->price;
        }
        return $sub_bid->objForSend;
    }
}
