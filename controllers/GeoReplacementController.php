<?php

namespace app\controllers;

use app\models\GeoLocation;
use app\models\GeoPage;
use app\models\GeoReplacement;
use Yii;
use app\models\sypexgeo\City;
use app\models\sypexgeo\Country;
use app\models\sypexgeo\Region;
use app\models\GeoMark;
use app\models\UploadImageForm;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

class GeoReplacementController extends AppController {

    public function actionIndex() {
        $userId = Yii::$app->user->getId();
        $pages = GeoPage::find()->where(['user_id' => $userId])->all();
        if ( empty($pages) ) return $this->render('/check/no-geo-pages');

        if ( Yii::$app->request->get('repl_page_id') ) {
            $replPageId = Yii::$app->request->get('repl_page_id');
        } else {
            $replPageId = $pages[0]->id;
        }

        $locations = GeoLocation::find()
            ->where(['user_id' => $userId])
            ->andWhere(['page_id' => $replPageId])
            ->all();

        $marks = GeoMark::find()
            ->where(['user_id' => $userId])
            ->andWhere(['page_id' => $replPageId])
            ->all();

        $pageReplacements = GeoReplacement::find()
            ->where(['user_id' => $userId])
            ->andWhere(['page_id' => $replPageId])
            ->all();

        \app\assets\GeoReplacementAsset::register($this->view);
        return $this->render('index', [
            'pages' => $pages,
            'replPageId' => $replPageId,
            'locations' => $locations,
            'marks' => $marks,
            'pageReplacements' => $pageReplacements,
            'sgCountries' => Country::find()->orderBy(['id' => SORT_DESC])->all(),
        ]);
    }

    public function actionGetSgRegions() {
        if ( !$countryIso = Yii::$app->request->get('country_iso') ) exit;
        $sgRegions = Region::findAll(['country_iso' => $countryIso]);
        return $this->renderAjax('sg-regions', compact('sgRegions'));
    }

    public function actionGetSgCities() {
        if ( !$regionId = Yii::$app->request->get('region_id') ) exit;
        $sgCities = City::findAll(['region_id' => $regionId]);
        return $this->renderAjax('sg-cities', compact('sgCities'));
    }

    public function actionSaveLocation() {
        if ( !$pageId = Yii::$app->request->get('page_id') ) exit;
        if ( !$geoType = Yii::$app->request->get('geo_type') ) exit;
        if ( !$geoId = Yii::$app->request->get('geo_id') ) exit;
        // проверка на уже добавленную локацию
        $checkLocation = GeoLocation::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['page_id' => $pageId])
            ->andWhere(['geo_type' => $geoType])
            ->andWhere(['geo_id' => $geoId])
            ->exists();
        if ( $checkLocation ) return 'error-alreadyexist';
        $saveLoc = new GeoLocation();
        $saveLoc->user_id = Yii::$app->user->getId();
        $saveLoc->page_id = $pageId;
        $saveLoc->geo_type = $geoType;
        $saveLoc->geo_id = $geoId;
        return $saveLoc->save() ? 'success' : 'error';
    }

    public function actionSaveTxt() {
        if ( !$pageId = Yii::$app->request->post('page_id') ) exit;
        if ( !$markId = Yii::$app->request->post('mark_id') ) exit;
        if ( !$locationId = Yii::$app->request->post('location_id') ) exit;
        if ( !$delta = Yii::$app->request->post('delta') ) exit;
        $isNullDelta = '{"ops":[{"insert":"\n"}]}';
        $dbReplacement = GeoReplacement::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['page_id' => $pageId])
            ->andWhere(['mark_id' => $markId])
            ->andWhere(['location_id' => $locationId])
            ->one();
        if (!$dbReplacement) {
            if ($delta === $isNullDelta) {
                return 'проигнорировано';
            } else {
                $replacement = new GeoReplacement();
                $replacement->user_id = Yii::$app->user->getId();
                $replacement->page_id = $pageId;
                $replacement->mark_id = $markId;
                $replacement->location_id = $locationId;
                $replacement->delta = $delta;
                $replacement->save();
                return 'сохранено';
            }
        } else {
            if ($delta === $isNullDelta) {
                $dbReplacement->delete();
                return 'удалено';
            } else {
                $dbReplacement->delta = $delta;
                $dbReplacement->update();
                return 'обновлено';
            }
        }
    }

    public function actionDelLoc($location_id){
        $location = GeoLocation::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['id' => $location_id])
            ->one();
        $imageRepls = GeoReplacement::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['location_id' => $location_id])
            ->andWhere(['not',['image_name'=>null]])
            ->all();
        $location->delete();
        foreach ( $imageRepls as $repl ) {
            $this->delLocalImg($repl->image_name);
        }
        return 'удалено';
    }

    public function actionUploadImage() {
        $image = new UploadImageForm();
        if (Yii::$app->request->isPost /*&& Yii::$app->request->isAjax*/) {
            //debug ($image);
            $image->imageFile = UploadedFile::getInstance($image, 'imageFile');
            //debug ($image);
            if ($image->upload()) {
                return $this->renderAjax('crop-view', compact('image'));
            }
        }
        return $this->renderAjax('upload-image', compact('image'));
    }

    public function actionUpdateImage() {
        if ( !$data = Yii::$app->request->post('data') ) exit;
        $data = json_decode($data);
        $replacement = GeoReplacement::find()
            ->where(['mark_id' => $data->mark_id])
            ->andWhere(['location_id' => $data->location_id])
            ->andWhere(['page_id' => $data->page_id])
            ->andWhere(['user_id' => Yii::$app->user->getId()])
            ->one();

        return $this->renderAjax('update-image', compact('replacement'));
    }

    public function actionCropSaveImage() {
        $request = Yii::$app->request;
        if ( !$request->isAjax ) exit;
        $data = $request->post('data');
        if ( !$data ) exit;
        $data = json_decode($data);
        $src =  $data->src;
        $width =  $data->coords->w;
        $height =  $data->coords->h;

        $type = strtolower(substr(strrchr($src,"."),1));
        if($type == 'jpeg') $type = 'jpg';
        switch($type){
            case 'gif': $img = imagecreatefromgif($src); break;
            case 'jpg': $img = imagecreatefromjpeg($src); break;
            case 'png': $img = imagecreatefrompng($src); break;
            default : return "Неподдерживаемый тип изображения!";
        }

        $new = imagecreatetruecolor($width, $height);

        if($type == "gif" or $type == "png"){
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }

        imagecopyresampled($new, $img, 0, 0,
            $data->coords->x, $data->coords->y,
            $width, $height,
            $data->coords->w, $data->coords->h
        );

        $imageName = basename($src);
        switch($type){
            case 'gif': imagegif($new, getUserImagePath() . $imageName); break;
            case 'jpg': imagejpeg($new, getUserImagePath() . $imageName, 90); break;
            case 'png': imagepng($new, getUserImagePath() . $imageName, 9); break;
        }

        $this->saveImageDb($data);
        FileHelper::unlink(Yii::getAlias('@image_path') . '/temp/' . $imageName);

        return getUserImageUrl() . $imageName;
    }

    public function actionNocropSaveImage() {
        if ( !$data = Yii::$app->request->post('data') ) exit;
        $data = json_decode($data);

        copy (
            Yii::getAlias('@image_path') . '/temp/' . basename($data->src),
            getUserImagePath() . basename($data->src)
        );
        FileHelper::unlink(Yii::getAlias('@image_path') . '/temp/' . basename($data->src) );

        $this->saveImageDb($data);
        return getUserImageUrl() . basename($data->src);
    }

    protected function saveImageDb($data) {
        $replacement = GeoReplacement::find()
            ->where(['mark_id' => $data->mark_id])
            ->andWhere(['location_id' => $data->location_id])
            ->andWhere(['page_id' => $data->page_id])
            ->andWhere(['user_id' => Yii::$app->user->getId()])
            ->one();
        if (!$replacement) {
            $replacement = new GeoReplacement();
            $replacement->user_id = Yii::$app->user->getId();
            $replacement->page_id = $data->page_id;
            $replacement->mark_id = $data->mark_id;
            $replacement->location_id = $data->location_id;
            $replacement->image_name = basename($data->src);
            $replacement->save();
        } else {
            $rplNumberByImageName = GeoReplacement::find()
                ->where(['image_name' => $replacement->image_name])
                ->andWhere(['user_id' => Yii::$app->user->getId()])
                ->count();
            if ($rplNumberByImageName == 1)
                FileHelper::unlink(getUserImagePath() . $replacement->image_name );
            $replacement->image_name = basename($data->src);
            $replacement->update();
        }
    }

    public function actionLoadedImages() {
        // post-запрос на сохранение изображений
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('data');
            if ( !$data ) exit;
            $data = json_decode($data);
            if ( !$data->src ) exit;
            $this->saveImageDb($data);
            return getUserImageUrl() . basename($data->src);
        }
        // get-запрос на отображение загруженных изображений
        $rpls = GeoReplacement::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->select(['image_name'])
            ->all();
        $imageNames = [];
        foreach ($rpls as $rpl) {
            // фильтруем текстовые подмены
            if ( $rpl->image_name == null ) continue;
            // фильтруем изображения с таким же именем
            if ( $src = Yii::$app->request->get('src') ) {
                if ( $rpl->image_name == basename($src) ) continue;
            }
            if ( !in_array($rpl->image_name, $imageNames) ) $imageNames[] = $rpl->image_name;
        }
        return $this->renderAjax('loaded-images', compact('imageNames'));
    }

    public function actionCropCnc($src) {
        if ( !Yii::$app->request->isAjax ) exit;
        // удаление временного файла
        FileHelper::unlink(Yii::getAlias('@image_path') . '/temp/' . basename($src));
        return 'удалено';
    }

    public function actionDelImg() {
        if ( !$data = Yii::$app->request->post('data') ) exit;
        $data = json_decode($data);
        $replacement = GeoReplacement::find()
            ->where(['page_id' => $data->page_id])
            ->andWhere(['mark_id' => $data->mark_id])
            ->andWhere(['location_id' => $data->location_id])
            ->andWhere(['user_id' => Yii::$app->user->getId()])
            ->one();
        $imageName = $replacement->image_name;
        $replacement->delete();
        $this->delLocalImg($imageName);
        return 'удалено';
    }

    public function actionDownloadAnimation() {
        return "Загрузка...";
    }

    public function actionGetReplacementByDisplayIdentity($locationId, $replPage) {
        $displayIdentity = getRandomString();
        $location = GeoLocation::findOne($locationId);
        $location->display_identity = $displayIdentity;
        $location->update();
        $href = $replPage . '?reked_geo_di=' . $displayIdentity;
        return $href;
    }

    protected function delLocalImg($imageName){
        $other_rpls = GeoReplacement::find()
            ->where(['like', 'image_name', $imageName])
            ->andWhere(['user_id' => Yii::$app->user->getId()])
            ->all();
        if ( empty($other_rpls) )
            FileHelper::unlink(getUserImagePath() . $imageName);
    }

}
