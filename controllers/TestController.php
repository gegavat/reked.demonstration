<?php

namespace app\controllers;

use app\components\Parser;
use app\components\Rules;
use app\components\SxGeo;
use app\models\AuthAssignment;
use app\models\GoogleAccount;
use app\models\YandexAccount;
use app\models\Replacement;
use app\models\ReplacementIdentity;
use app\models\YandexAdGroup;
use app\models\YandexCampaign;
use app\models\Payment;
use app\models\user\User;
use app\models\EnabledDomain;
use app\models\YandexBidder;
use Yii;
use app\components\YandexApi;
use yii\web\UploadedFile;
use DateTime;
use DateInterval;

class TestController extends AppController {

    public function actionAccount() {
        return $this->render('account');
    }

    public function actionCampaigns() {
    }

    public function actionMarks() {
        return $this->render('marks');
    }

    public function actionAjax() {
        sleep(5);
        return 123;
    }

    public function actionConfirm(){
        return $this->render('confirm');
    }

    public function actionGetMarkedUrl() {
        $param = 'reked';
        $rpIdentId = 1234414;

        $url = urldecode ("http://site.com/page1.html?yuht#anchor");

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
                if ( $queryArr[$param] != $rpIdentId ) {
                    $queryArr[$param] = $rpIdentId;
                }
            } else {
                $queryArr += [$param => $rpIdentId];
            }
            $urlParts['query'] = http_build_query($queryArr);
        } else {
            $urlParts += ['query' => "$param=$rpIdentId"];
        }
        $query = '?' . $urlParts['query'];

        $newUrl = $scheme.$host.$port.$path.$query.$fragment;
        debug ($newUrl);
    }

    public static function actionParseUrl(){
        $url = urldecode ("http://www.yiiframework.com/doc/guide/1.1/ru/");
        $href = parse_url($url);
        $scheme = isset($href['scheme']) ? $href['scheme'] . '://' : '';
        $host   = isset($href['host']) ? $href['host'] : '';
        $port   = isset($href['port']) ? ':' . $href['port'] : '';
        $path   = isset($href['path']) ? $href['path'] : '';
        $newHref = preg_replace("#/$#", "", $scheme.$host.$port.$path);
        debug ($newHref);
    } 

    public function actionYandex(){
        $api = new YandexApi('AQAAAAAul57dAAS9V6p3jHT0_UqTrtJgxNDRkPA');
        $api->requestAds('3709352145');
    }

    public function actionHash(){
        $api = new YandexApi('AQAAAAAul57dAAS9V6p3jHT0_UqTrtJgxNDRkPA');
        $api->requestUrlImage('Ma7jGuanLFH3PPrZtB7jQA');
    }

    public function actionAlias() {
        return Yii::getAlias('@image_url/');
    }
    
    public function actionForm() {
		$model = new \app\models\TestForm;

        if (Yii::$app->request->isPost) {
            $model->name = $_POST['TestForm']['name'];
            $model->lastName = $_POST['TestForm']['lastName'];
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->upload()) {
                // file is uploaded successfully
//                return 123;
            }
        }
		
		return $this->render('form', compact('model'));
	}

    public function actionRole() {
//         роли пользователей
//        $role = Yii::$app->authManager->createRole('trial');
//        $role->description = 'trial';
//        Yii::$app->authManager->add($role);

//        получение ролей пользователя
//        debug ( Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId()) );

//         назначение роли пользователю
//        $userRole = Yii::$app->authManager->getRole('admin');
//        Yii::$app->authManager->assign($userRole, Yii::$app->user->getId());

//         добавление правил
//        $rule = new \app\components\rules\BidRule();
//        Yii::$app->authManager->add($rule);

//         добавление разрешения и связаемого с ним правила
//        $permission = Yii::$app->authManager->createPermission('addBid');
//        $permission->description = 'Добавление бид-менеджера';
//        $permission->ruleName = $rule->name;
//        Yii::$app->authManager->add($permission);

//        добавление правила к уже существующему разрешению
//        $permission = Yii::$app->authManager->getPermissions()['addBidder'];
//        $permission->ruleName = $rule->name;

//         наследуем разрешения для роли
//        $role = Yii::$app->authManager->getRole('premium');
//        $permission = Yii::$app->authManager->getPermission('addBidder');
//        Yii::$app->authManager->addChild($role, $permission);
//        return '++';

        if (Yii::$app->user->can('addBid', [1, 2, 3])){
            return 'true';
        } else {
            return 'false';
        }
    }

    public function actionCoder(){
        return urldecode("https://demo.reked.ru/%D0%BE-%D0%BA%D0%BE%D0%BC%D0%BF%D0%B0%D0%BD%D0%B8%D0%B8.html");
    }

    public function actionDate(){
        $users = User::find()->where(['status' => 10])->all();
        foreach ($users as $user){

            $tarifUser = AuthAssignment::find()->where(['user_id' => $user->id])->one();

            if ( $tarifUser->item_name == 'trial' ){
                $today = new \DateTime();
                $updTarifUser = new \DateTime();
                $updTarifUser->setTimestamp($tarifUser->updated_at);
                $interval = $today->diff($updTarifUser)->format('%a');
                debug($interval);
            }
        }
    }

    public function actionAddDomain() {
        return Rules::canAddDomain() ? 'true' : 'false';
    }

    public function actionDate2(){
        $tariffUser = AuthAssignment::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        $updTariffUser = new DateTime();//06.06.2019 18:11:02
        $updTariffUser->setTimestamp($tariffUser->updated_at);
        $newDate = $updTariffUser->add(new DateInterval('P'.Yii::$app->params['checkInterval']['prolongation'].'D'));
        $tariffUser->updated_at = $newDate->format('U');
        $tariffUser->detachBehaviors();
        $tariffUser->update();

        return $tariffUser->updated_at;
    }

    public function actionDate3(){
        $tariffUser = AuthAssignment::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        $today = new DateTime();
        $updTariffUser = new DateTime();
        $updTariffUser->setTimestamp($tariffUser->updated_at);
        $interval = $today->diff($updTariffUser)->format('%a');
        $tariffUser->updated_at = $today->format('U');
        $tariffUser->update();

        return $today->format('U');
    }

    public function actionBidder() {
        $bidders = YandexBidder::findAll(['status' => 'enabled']);
        Parser::orderBiddersByAccounts($bidders);
    }

    public function actionCompareBidder() {
        $bidder = new YandexBidder();
        $bidder->user_id = 5;
        $bidder->account_id = 43301778;
        $bidder->campaign_id = 123456;
        $bidder->campaign_type = 'search';
        $bidder->strategy = 'custom';
        $bidder->step = 500000;
        $bidder->price = null;
        $bidder->price_limit = null;
        $bidder->traffic_volume = 75;
        $bidder->bid = 45000000;
        $bidder->status = 'disabled';
        if ( !$bidder->save() ) {
            debug($bidder->errors);
        }
        debug ('saved');
    }

    public function actionDelete(){
//        $offDomain = 'test';
        $offBidder = 'del';
        if ( isset($offDomain) && isset($offBidder) ) return 'off_all';
        return 'disable';
    }

    public function actionMail(){
        Yii::$app->mailer->compose('responseEndSoon', ['username' => Yii::$app->user->identity->username])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo(Yii::$app->user->identity->email)
            ->setSubject('Заканчиваются переходы')
//            ->setTextBody('Текст сообщения')
//            ->setHtmlBody('<b>текст сообщения в формате HTML</b>')
            ->send();
        return 123;
    }

    public function actionGaccount (){
        $gAccount = GoogleAccount::find()
            ->where(['user_id' => 48])
            ->andWhere(['mcc' => 0])
            ->one();
        debug($gAccount->googleCampaigns);
    }

    public function actionGeoLite() {
        $reader = new Reader(Yii::getAlias('@app') . '/../GeoLite2-City.mmdb');

//        $record = $reader->city('128.101.101.101');  // изначальный ip
//        $record = $reader->city('92.39.217.80');     // mts домашний
//        $record = $reader->city('94.25.182.117');    // yota ip
        $record = $reader->city('95.64.255.255');    // МО Балашиха

        $result = [
            'country->isoCode' => $record->country->isoCode,
            'country->name' => $record->country->name,
            'country->names' => $record->country->names,
            'mostSpecificSubdivision->name' => $record->mostSpecificSubdivision->name,
            'mostSpecificSubdivision->isoCode' => $record->mostSpecificSubdivision->isoCode,
            'city->name' => $record->city->name,
            'postal->code' => $record->postal->code,
            'location->latitude' => $record->location->latitude,
            'location->longitude' => $record->location->longitude,
            'traits->network' => $record->traits->network,
        ];
        debug ($record->mostSpecificSubdivision->names['ru']);
    }

    public function actionSypexGeo() {
        $SxGeo = new SxGeo(Yii::getAlias('@webroot').'/../../SxGeoCity.dat');
        $ip = '94.25.182.117';

        // Определяем страну c БД содержащими страны (SxGeo Country)
        // $country = $SxGeo->getCountry($ip);  // возвращает двухзначный ISO-код страны
        // $SxGeo->getCountryId($ip); (возвращает номер страны)

        // Определяем город (SxGeo City, GeoLite City, IpGeoBase)
        // $SxGeo->getCity($ip); // возвращает с краткой информацией, без названия региона и временной зоны
        // $SxGeo->getCityFull($ip); // возвращает полную информацию о городе и регионе
        // $city = $SxGeo->get($ip); // выполняет getCountry либо getCity в зависимости от типа базы
        $city = $SxGeo->getCityFull($ip);
        debug ($city);
    }

    public function actionReplMove () {

        $yaCmpsOld = array_column(
            YandexCampaign::find()
                ->where(['account_id' => '70505955'])
                ->all()
            , 'campaign_id');

        $yaCmpsNew = array_column(
            YandexCampaign::find()
                ->where(['account_id' => '84138180'])
                ->all()
            , 'campaign_id');

        $repls = Replacement::find()
            ->where(['user_id' => 107])
            ->all();

        $printArr = [];

        foreach ($repls as $repl) {
            $replIdentity = ReplacementIdentity::find()
                ->where(['id' => $repl->repl_identity_id])
                ->one();
            $yaGroupOld = YandexAdGroup::find()
                ->where(['group_id' => $replIdentity->ya_group_id])
                ->andWhere(['in', 'campaign_id', $yaCmpsOld])
                ->one();
            if ( !empty($yaGroupOld) ) {
                $yaGroupNew = YandexAdGroup::find()
                    ->where(['group_name' => $yaGroupOld->group_name])
                    ->andWhere(['in', 'campaign_id', $yaCmpsNew])
                    ->one();

                $replIdentityNew = ReplacementIdentity::find()
                    ->where(['ya_group_id' => $yaGroupNew->group_id])
                    ->one();

                $replUpd = Replacement::find()
                    ->where(['repl_identity_id' => $repl->repl_identity_id])
                    ->one();
                $replUpd->repl_identity_id = $replIdentityNew->id;
                $replUpd->update();

                $printArr[] = $replIdentityNew->id;
            }
        }
        debug ($printArr);

    }

    public function actionUrlDecode() {
        $link = "http%3A%2F%2Fsite.com%2F%D1%81%D1%82%D1%80%D0%B0%D0%BD%D0%B8%D1%86%D0%B01%2F%D1%81%D1%82%D1%80%D0%B0%D0%BD%D0%B8%D1%86%D0%B043";
        $link = "https%3A%2F%2F%D0%BE%D0%BA%D0%BD%D0%B0.%D1%80%D1%84%2Fpage1";
        $link = "https%3A%2F%2Fstackoverflow.com%2Fquestions%2F3896591%2Fwhat-is-the-equivalent-of-javascripts-decodeuricomponent-in-php";
        $link = urldecode($link);
        debug ($link);
    }

}
