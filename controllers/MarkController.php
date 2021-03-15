<?php

namespace app\controllers;

use app\components\Parser;
use app\models\GoogleCampaign;
use app\models\Mark;
use app\models\YandexCampaign;
use Yii;
use app\models\GoogleAd;
use app\models\YandexAd;
use app\models\YandexAccount;
use app\models\GoogleAccount;

class MarkController extends AppController {

    public function actionIndex() {
        $yaAccount = YandexAccount::findOne(['user_id' => Yii::$app->user->getId()]);
        $gAccount = GoogleAccount::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['mcc' => 0])
            ->one();
        if ( !$yaAccount && !$gAccount ) return $this->render('/check/no-accounts');

        $yaCampaigns = YandexCampaign::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        $gCampaigns = GoogleCampaign::find()->where(['user_id' => Yii::$app->user->getId()])->one();
        if ( empty($yaCampaigns) && empty($gCampaigns) ) return $this->render('/check/no-campaigns');

        $adYaUrls = YandexAd::find()->where(['user_id' => Yii::$app->user->getId()])->select(['ad_href'])->all();
        $adGUrls = GoogleAd::find()->where(['user_id' => Yii::$app->user->getId()])->select(['ad_href'])->all();
        $pages = Parser::getUniqUrls($adYaUrls, $adGUrls);
        if ( isset($_COOKIE['mark_page']) ) $mark_page = $_COOKIE['mark_page'];
            else $mark_page = $pages[0];
        $marks = Mark::find()->where(['url' => $mark_page])->andWhere(['user_id' => Yii::$app->user->getId()])->all();

        \app\assets\MarkAsset::register($this->view);
        return $this->render('index', compact('pages', 'marks', 'mark_page'));
    }

    public function actionSaveMark() {
        if ( !Yii::$app->request->isAjax ) exit;
        $selector = Yii::$app->request->post('selector');
        if ( !$selector ) exit;
        $selector = json_decode($selector);
        if ($selector->type == 'undefined') return 'error-type';
        $marks = Mark::find()->where(['url' => $selector->url])->andWhere(['user_id' => Yii::$app->user->getId()])->all();
        foreach ($marks as $elem)
            if ($elem->selector_path == $selector->path) return 'error-alreadyexist';
        $name_number = count($marks);
        $name = 'Подмена №' . ($name_number + 1);
        $mark = new Mark();
        $mark->user_id = Yii::$app->user->getId();
        $mark->name = $name;
        $mark->url = $selector->url;
        $mark->type = $selector->type;
        if ( $mark->type === 'img' ) {
            $mark->img_width = $selector->width;
            $mark->img_height = $selector->height;
        }
        $mark->selector_path = $selector->path;
        $mark->save();
        return $selector->type;
    }

    public function actionMoveMark() {
        if ( !Yii::$app->request->isAjax ) exit;
        $selector = Yii::$app->request->post('selector');
        if ( !$selector ) exit;
        $selector = json_decode($selector);
        // проверка на неопределенность типа
        if ($selector->type == 'undefined') return 'error-type-undefined';
        $own_mark = Mark::find()->where(['id' => $selector->mark_id])->limit(1)->one();
        // проверка на несоответствие типа
        if ( $own_mark->type != $selector->type ) return 'error-type-mismatch';
        // проверка на тот же самый элемент
        if ( $own_mark->selector_path == $selector->path ) return 'error_same';
        // проверка на запись в уже существующие подмены
        $other_marks = Mark::find()
            ->where(['!=', 'id', $selector->mark_id])
            ->andWhere(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['url' => $selector->url])
            ->all();
        foreach ($other_marks as $elem)
            if ($elem->selector_path == $selector->path) return 'error-alreadyexist';
        $own_mark->selector_path = $selector->path;
        if ( $own_mark->type === 'img' ) {
            $own_mark->img_width = $selector->width;
            $own_mark->img_height = $selector->height;
        }
        $own_mark->update();
        return $selector->type;
    }

    public function actionRename($id, $name) {
        if ( !Yii::$app->request->isAjax ) exit;
        $mark = Mark::find()->where(['id' => $id])->limit(1)->one();
        $mark->name = $name;
        $mark->update();
        return $name;
    }

    public function actionDelMark($id) {
        if ( !Yii::$app->request->isAjax ) exit;
        $mark = Mark::find()->where(['id' => $id])->limit(1)->one();
        $mark->delete();
        return $id;
    }

}