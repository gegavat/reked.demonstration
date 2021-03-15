<?php

namespace app\controllers;

use app\models\GeoPage;
use Yii;

class GeoPageController extends AppController {

	public function actionIndex() {
	    $pages = GeoPage::find()->where(['user_id' => Yii::$app->user->getId()])->all();

		\app\assets\GeoPageAsset::register($this->view);
        return $this->render('index', compact('pages'));
	}

	public function actionNewPage() {
        if ( !Yii::$app->request->isAjax ) exit;
        $data = Yii::$app->request->post('data');
        if ( !$data ) exit;
        $url = json_decode($data)->url;
        // проверка правильности url
        if ( strripos($url, '?') || strripos($url, '#') ) {
            return 'error-url';
        }
        /*if(!filter_var($url, FILTER_VALIDATE_URL)){
            return 'error-url';
        }*/
        // проверка доступности url
        $curlInit = curl_init($url);
        curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curlInit,CURLOPT_HEADER,true);
        curl_setopt($curlInit,CURLOPT_NOBODY,true);
        curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);
        $response = curl_exec($curlInit);
        curl_close($curlInit);
        if ( !$response ) {
            return 'error-url';
        }
        $userPages = array_column(GeoPage::find()->where(['user_id' => Yii::$app->user->getId()])->all(), 'page');
        if ( in_array($url, $userPages) ) {
            return 'error-alreadyexist';
        }
        $newPage = new GeoPage();
        $newPage->user_id = Yii::$app->user->getId();
        $newPage->page = $url;
        $newPage->save();
        return 'success';
    }

    public function actionDelPage() {
        if ( !Yii::$app->request->isAjax ) exit;
        $pageId = Yii::$app->request->get('page_id');
        if ( !$pageId ) exit;
        $page = GeoPage::findOne($pageId);
        return $page->delete() ? 'success' : 'error';
    }

}