<?php

namespace app\controllers;

use app\models\GeoMark;
use app\models\GeoPage;
use Yii;
use app\components\Rules;

class GeoReplActivateController extends AppController {

    public function actionIndex(){
        $userId = Yii::$app->user->getId();
        $pages = GeoPage::findAll(['user_id' => $userId]);
        if ( empty($pages) ) return $this->render('/check/no-geo-pages');

        $marks = GeoMark::find()->where(['user_id' => $userId])->all();
        if ( empty($marks) )
            return $this->render('/check/empty-geo-marks');

        \app\assets\GeoReplActivateAsset::register($this->view);
        return $this->render('index', compact('pages'));
    }

    public function actionCheckStatus($page_id, $status){
        $page = GeoPage::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['id' => $page_id])
            ->one();
        if ($status === 'enable'){
            if ( Rules::canEnableGeoPage() ){
                $page->enabled = 1;
                $page->update();
                return 'enabled';
            } else {
                return 'deny';
            }
        } else {
            if ( $page->enabled === 1 ) {
                $page->enabled = 0;
                $page->update();
                return 'disabled';
            } else {
                return 'ignore';
            }
        }
    }

}