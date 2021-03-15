<?php

namespace app\controllers;

use app\components\YandexApiException;
use app\models\YandexBidder;
use Yii;
use app\components\YandexApi;
use app\components\Parser;
use app\models\YandexAccount;
use app\components\Rules;

class BidderController extends AppController {

    public function actionIndex() {
        $dbYaAccounts = YandexAccount::findAll(['user_id' => Yii::$app->user->getId()]);
        // if ( empty($dbYaAccounts) ) return $this->render('/check/no-accounts');

        $dbBidders = YandexBidder::findAll(['user_id' => Yii::$app->user->getId()]);
        $bidderYaAccounts = [];
        try {
            foreach($dbYaAccounts as $dbYaAccount){
                $api = new YandexApi($dbYaAccount->access_token);
                $bidCmps = $api->getBidCmps();
                if ( empty($bidCmps) ) continue;
                $bidderYaAccounts[] = (object)[
                    'accountId' => $dbYaAccount->account_id,
                    'accountLogin' => $dbYaAccount->login,
                    'apiCmps' => Parser::mixApiWithBidder($bidCmps, $dbBidders)
                ];
            }
        } catch (YandexApiException $e) {
            return $e->getMessage();
        }

        // if ( !empty($dbYaAccounts) && empty($bidderYaAccounts) ) return $this->render('/check/no-bidder-campaigns');

        \app\assets\BidderAsset::register($this->view);
        return $this->render('index', [
            'dbYaAccounts' => $dbYaAccounts,
            'bidderYaAccounts' => Parser::orderByStrategyType($bidderYaAccounts)
        ]);
    }

    public function actionYaShowSearch() {
        if (!Yii::$app->request->isAjax) exit;
        $cmpIds = Yii::$app->request->post('ya_cmp_ids');
        
        $bidders = YandexBidder::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['in', 'campaign_id', $cmpIds])
            ->all();

        return $this->renderAjax('ya-show-search', [
            'cmpIds' => $cmpIds,
            'strategy' => Parser::getMixedBidderProperty($bidders, 'strategy'),
            'traffic_volume' => Parser::getMixedBidderProperty($bidders, 'traffic_volume'),
            'step' => Parser::getMixedBidderProperty($bidders, 'step'),
            'price' => Parser::getMixedBidderProperty($bidders, 'price'),
            'bid' => Parser::getMixedBidderProperty($bidders, 'bid'),
            'price_limit' => Parser::getMixedBidderProperty($bidders, 'price_limit'),
            'status' => Parser::getMixedBidderProperty($bidders, 'status')
        ]);
    }

    public function actionYaSearchChangeStatus() {
        $data = Yii::$app->request->post('data');
        $data = json_decode($data);

		if ( $data->mode === 'activate' ) {
            $canActivateBidders = Rules::canActivateBidders($data->yaCmps);
			foreach ( $data->yaCmps as $cmp ) {
				$bidder = new YandexBidder();
				$bidder->user_id = Yii::$app->user->getId();
				$bidder->account_id = $cmp->accId;
				$bidder->campaign_id = $cmp->cmpId;
				$bidder->campaign_type = 'search';
				$bidder->strategy = $data->strategy;
				$bidder->step = $data->step * Yii::$app->params['payMultiplier'];
				$bidder->price = $data->price ? $data->price * Yii::$app->params['payMultiplier'] : null;
				$bidder->price_limit = $data->price_limit ? $data->price_limit * Yii::$app->params['payMultiplier'] : null;
				$bidder->traffic_volume = $data->traffic_volume ? $data->traffic_volume : null;
				$bidder->bid = $data->bid ? $data->bid * Yii::$app->params['payMultiplier'] : null;
				$bidder->status = $canActivateBidders ? 'enabled' : 'disabled';
                $bidder->save();
			}
			return $canActivateBidders ? 'activated' : 'saved';
		}
		if ( $data->mode === 'update' ) {
			foreach ( $data->yaCmps as $cmp ) {
				$bidder = YandexBidder::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['campaign_id' => $cmp->cmpId])->one();
				$bidder->campaign_type = 'search';
				$bidder->strategy = $data->strategy;
				$bidder->step = $data->step * Yii::$app->params['payMultiplier'];
				$bidder->price = $data->price ? $data->price * Yii::$app->params['payMultiplier'] : null;
				$bidder->price_limit = $data->price_limit ? $data->price_limit * Yii::$app->params['payMultiplier'] : null;
				$bidder->traffic_volume = $data->traffic_volume ? $data->traffic_volume : null;
				$bidder->bid = $data->bid ? $data->bid * Yii::$app->params['payMultiplier'] : null;
				$bidder->update();
			}
			return 'updated';
		}
        if ( $data->mode === 'enable' ) {
            $canActivateBidders = Rules::canActivateBidders($data->yaCmps);
            foreach ( $data->yaCmps as $cmp ) {
                $bidder = YandexBidder::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['campaign_id' => $cmp->cmpId])->one();
                $bidder->status = $canActivateBidders ? 'enabled' : 'disabled';
                $bidder->update();
            }
            return $canActivateBidders ? 'enabled' : 'saved';
        }
        if ( $data->mode === 'disable' ) {
		    foreach ( $data->yaCmps as $cmp ) {
                $bidder = YandexBidder::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['campaign_id' => $cmp->cmpId])->one();
                $bidder->status = 'disabled';
                $bidder->update();
            }
            return 'disabled';
        }
    }

    public function actionYaShowNetwork() {
        if (!Yii::$app->request->isAjax) exit;
        $cmpIds = Yii::$app->request->post('ya_cmp_ids');
        $bidders = YandexBidder::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['in', 'campaign_id', $cmpIds])
            ->all();
        return $this->renderAjax('ya-show-network', [
            'cmpIds' => $cmpIds,
            'strategy' => Parser::getMixedBidderProperty($bidders, 'strategy'),
            'step' => Parser::getMixedBidderProperty($bidders, 'step'),
            'bid' => Parser::getMixedBidderProperty($bidders, 'bid'),
            'status' => Parser::getMixedBidderProperty($bidders, 'status')
        ]);
    }

    public function actionYaNetworkChangeStatus() {
        $data = Yii::$app->request->post('data');
        $data = json_decode($data);

        if ( $data->mode === 'activate' ) {
            $canActivateBidders = Rules::canActivateBidders($data->yaCmps);
            foreach ( $data->yaCmps as $cmp ) {
                $bidder = new YandexBidder();
                $bidder->user_id = Yii::$app->user->getId();
                $bidder->account_id = $cmp->accId;
                $bidder->campaign_id = $cmp->cmpId;
                $bidder->campaign_type = 'network';
                $bidder->strategy = $data->strategy;
                $bidder->step = $data->step * Yii::$app->params['payMultiplier'];
                $bidder->bid = $data->bid * Yii::$app->params['payMultiplier'];
                $bidder->status = $canActivateBidders ? 'enabled' : 'disabled';
                $bidder->save();
            }
            return $canActivateBidders ? 'activated' : 'saved';
        }
        if ( $data->mode === 'update' ) {
            foreach ( $data->yaCmps as $cmp ) {
                $bidder = YandexBidder::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['campaign_id' => $cmp->cmpId])->one();
                $bidder->campaign_type = 'network';
                $bidder->strategy = $data->strategy;
                $bidder->step = $data->step * Yii::$app->params['payMultiplier'];
                $bidder->bid = $data->bid * Yii::$app->params['payMultiplier'];
                $bidder->update();
            }
            return 'updated';
        }
        if ( $data->mode === 'enable' ) {
            $canActivateBidders = Rules::canActivateBidders($data->yaCmps);
            foreach ( $data->yaCmps as $cmp ) {
                $bidder = YandexBidder::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['campaign_id' => $cmp->cmpId])->one();
                $bidder->status = $canActivateBidders ? 'enabled' : 'disabled';
                $bidder->update();
            }
            return $canActivateBidders ? 'enabled' : 'saved';
        }
        if ( $data->mode === 'disable' ) {
            foreach ( $data->yaCmps as $cmp ) {
                $bidder = YandexBidder::find()->where(['user_id' => Yii::$app->user->getId()])->andWhere(['campaign_id' => $cmp->cmpId])->one();
                $bidder->status = 'disabled';
                $bidder->update();
            }
            return 'disabled';
        }
    }

}
