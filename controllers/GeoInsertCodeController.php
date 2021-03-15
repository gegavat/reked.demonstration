<?php

namespace app\controllers;

use app\components\Parser;
use app\models\GeoPage;
use Yii;

class GeoInsertCodeController extends AppController {

    public function actionIndex(){
        $pages = GeoPage::find()->where(['user_id' => Yii::$app->user->getId()])->all();
        if ( empty($pages) ) return $this->render('/check/no-geo-pages');

        $script = Parser::getInsertCode();
        \app\assets\GeoInsertCodeAsset::register($this->view);
        return $this->render ('index', compact('pages', 'script'));
    }

    public function actionCheck($page){
        if ( !Yii::$app->request->isAjax ) exit;
        //запрос html кода страницы
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $page);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($curl);
        curl_close($curl);

        //поиск кода
        if (stristr($result, Parser::getInsertCodeSrc()))
            return 1;
        else
            return 2;
    }
}