<?php

namespace app\controllers;

use app\models\GeoMark;
use app\models\GeoPage;
use Yii;

class GeoMarkController extends AppController {

	public function actionIndex() {
	    $pages = GeoPage::find()->where(['user_id' => Yii::$app->user->getId()])->all();
        if ( empty($pages) ) return $this->render('/check/no-geo-pages');

		$pages = array_column($pages, 'page', 'id' );
		if ( isset($_COOKIE['geo_mark_page']) ) $geo_mark_page = $_COOKIE['geo_mark_page'];
			else $geo_mark_page = reset($pages);
		$marks = GeoMark::find()->where(['page_id' => array_search($geo_mark_page, $pages)])->andWhere(['user_id' => Yii::$app->user->getId()])->all();
		\app\assets\GeoMarkAsset::register($this->view);
		return $this->render('index', compact('pages', 'marks', 'geo_mark_page'));
	}

	public function actionSaveMark() {
		if ( !Yii::$app->request->isAjax ) exit;
		$selector = Yii::$app->request->post('selector');
		if ( !$selector ) exit;
		$selector = json_decode($selector);
		if ($selector->type == 'undefined') return 'error-type';
		$pageId = GeoPage::find()->where(['page' => $selector->url])->andWhere(['user_id' => Yii::$app->user->getId()])->one()->id;
		$marks = GeoMark::find()->where(['page_id' => $pageId])->andWhere(['user_id' => Yii::$app->user->getId()])->all();
		foreach ($marks as $elem)
			if ($elem->selector_path == $selector->path) return 'error-alreadyexist';
		$name_number = count($marks);
		$name = 'Подмена №' . ($name_number + 1);
		$geoMark = new GeoMark();
		$geoMark->user_id = Yii::$app->user->getId();
		$geoMark->page_id = $pageId;
		$geoMark->name = $name;
		$geoMark->type = $selector->type;
		if ( $geoMark->type === 'img' ) {
			$geoMark->img_width = $selector->width;
			$geoMark->img_height = $selector->height;
		}
		$geoMark->selector_path = $selector->path;
		$geoMark->save();
		return $selector->type;
	}

	public function actionMoveMark() {
		if ( !Yii::$app->request->isAjax ) exit;
		$selector = Yii::$app->request->post('selector');
		if ( !$selector ) exit;
		$selector = json_decode($selector);
		// проверка на неопределенность типа
		if ($selector->type == 'undefined') return 'error-type-undefined';
		$own_mark = GeoMark::find()->where(['id' => $selector->mark_id])->limit(1)->one();
		// проверка на несоответствие типа
		if ( $own_mark->type != $selector->type ) return 'error-type-mismatch';
		// проверка на тот же самый элемент
		if ( $own_mark->selector_path == $selector->path ) return 'error_same';
		// проверка на запись в уже существующие подмены
        $pageId = GeoPage::find()->where(['page' => $selector->url])->andWhere(['user_id' => Yii::$app->user->getId()])->one()->id;
		$other_marks = GeoMark::find()
			->where(['!=', 'id', $selector->mark_id])
			->andWhere(['user_id' => Yii::$app->user->getId()])
			->andWhere(['page_id' => $pageId])
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
		$geoMark = GeoMark::find()->where(['id' => $id])->limit(1)->one();
		$geoMark->name = $name;
		$geoMark->update();
		return $name;
	}

	public function actionDelMark($id) {
		if ( !Yii::$app->request->isAjax ) exit;
		$geoMark = GeoMark::find()->where(['id' => $id])->limit(1)->one();
		$geoMark->delete();
		return $id;
	}

}