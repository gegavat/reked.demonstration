<?php
/**
 * Description: скрипт, управляющий ставками клиентов на аукционе Яндекс Директ
 */

namespace app\commands;

use app\components\YandexApiException;
use Yii;
use app\components\KeywordBidHandler;
use app\components\Parser;
use app\components\YandexApi;
use app\models\user\User;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\YandexBidder;

class BidderController extends Controller {

    public function actionIndex() {
        $activeUsers = User::findAll(['tariff_activity' => 1]);
        $activeUserIds = [];
        foreach ($activeUsers as $activeUser){
            $activeUserIds[] = $activeUser->id;
        }
        $bidders = YandexBidder::find()
            ->where(['status' => 'enabled'])
            ->andWhere(['in', 'user_id', $activeUserIds])
            ->all();
        if ( empty($bidders) ) exit;

        // объект, кот. будет логгироваться в файл
        $logObject = (object) [
            'startTime' => date('d.m.Y H:i:s'),
            'endTime' => null,
            'bidders' => []
        ];
		// перебор АККАУНТОВ
        foreach ( Parser::orderBiddersByAccounts($bidders) as $account ) {
            try {
                $api = new YandexApi($account->accessToken);
                // информация о том как отработал бид-менеджер для текущего аккаунта
                $currentBidder = (object)[
                    'accountId' => $account->accountId,
                    'search' => null,
                    'network' => null
                ];
                // обработка бид-менеджеров на поиске
                if (!empty($account->searchBidders)) {
                    // проверка/установка стратегии "ручное управление ставками"
                    // если этой кампании уже нет в api или показы на поиске были выключены,
                    // эта кампания будет удалена из базы
                    $api->checkBiddingStrategy($account->searchBidders);
                    $apiKeywordBids = $api->getKeywordBids($account->searchBidders);
                    $handledKeywordBids = KeywordBidHandler::getSearchBids($apiKeywordBids, $account->searchBidders);
                    $currentBidder->search = $api->setKeywordBids($handledKeywordBids);
                }
                // обработка бид-менеджеров в сетях
                if (!empty($account->networkBidders)) {
                    // проверка/установка стратегии "ручное управление ставками"
                    // если этой кампании уже нет в api или показы в сетях были выключены,
                    // эта кампания будет удалена из базы
                    $api->checkBiddingStrategy($account->networkBidders);
                    $apiKeywordBids = $api->getKeywordBids($account->networkBidders);
                    $handledKeywordBids = KeywordBidHandler::getNetworkBids($apiKeywordBids, $account->networkBidders);
                    $currentBidder->network = $api->setKeywordBids($handledKeywordBids);
                }
                $logObject->bidders[] = $currentBidder;
            } catch (YandexApiException $e) {
                $logObject->bidders[] = (object)[
                    'accountId' => $account->accountId,
                    'apiError' => $e->getMessage()
                ];
            }
        }
        $logObject->endTime = date('d.m.Y H:i:s');
        logFile($logObject, Yii::$app->params['bidderLogPath']);
        return ExitCode::OK;
    }
}
