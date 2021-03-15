<?php

namespace app\controllers;

use app\models\GoogleAdGroup;
use app\models\ReplacementIdentity;
use app\models\UploadImageForm;
use app\models\YandexAdGroup;
use Yii;
use app\components\Parser;
use app\models\YandexCampaign;
use app\models\GoogleCampaign;
use app\models\Mark;
use app\models\Replacement;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use app\models\YandexAccount;
use app\models\GoogleAccount;
use yii\data\Pagination;


class ReplacementController extends AppController {

    protected $paginationPages;

    public function actionIndex() {
        $yaAccount = YandexAccount::findOne(['user_id' => Yii::$app->user->getId()]);
        $gAccount = GoogleAccount::find()
            ->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['mcc' => 0])
            ->one();
        if (!$yaAccount && !$gAccount) return $this->render('/check/no-accounts');

		// запрос всех кампаний
		$userId = Yii::$app->user->getId();
		$yaCampaigns = YandexCampaign::find()->where(['user_id' => $userId])->all();
		$gCampaigns = GoogleCampaign::find()->where(['user_id' => $userId])->all();
		
		// если кампаний нет (были удалены), выдаем сообщение
		if ( empty($yaCampaigns) && empty($gCampaigns) )
			return $this->render('/check/no-campaigns');
		
		// проверяем в GET наличие id кампании Яндекс или Google
		// если есть id, устанавливаем кампанию с этим id в качестве размечаемой
		$request = Yii::$app->request;
        $yaCmpId = $request->get('ya_campaign_id');
        $gCmpId = $request->get('g_campaign_id');

        if ($yaCmpId) {
            $replCmp = $this->getReplCmp($yaCmpId, 'yandex');
        } elseif ($gCmpId) {
            $replCmp = $this->getReplCmp($gCmpId, 'google');
		} else {
            // если в GET id кампании отсутствует, выбираем первую Яндекс кампанию в качестве размечаемой
            // если Яндекс кампаний нет, берем первую Google кампанию
            if ( !empty($yaCampaigns) ) {
                $replCmp = $this->getReplCmp($yaCampaigns[0]->campaign_id, 'yandex');
            } else {
                $replCmp = $this->getReplCmp($gCampaigns[0]->campaign_id, 'google');
            }
        }

        $adGroups = $this->getAdGroups($replCmp);

//        debug($replCmp);

        // принимаем из GET размечаемую страницу из кампании, если параметр отсутствует,
        // берем первую страницу из массива $pages
        if ( Yii::$app->request->get('repl_page') ) {
            $replPage = Yii::$app->request->get('repl_page');
        } else {
            // debug ($adGroups);
            // !!! временный костыль. Нужно исправить
            // также есть в view/replacement/index.php line-135
            // если в группе объявлений нет объявлений, идет поиск группы, в кот. объявления есть
            if ( isset($adGroups[0]->yandexAds) ) {
                foreach ( $adGroups as $adGroup ) {
                    if ( !empty($adGroup->yandexAds) ) {
                        $replPage = $adGroup->yandexAds[0]->ad_href;
                        break;
                    }
                }
                
            } else {
                foreach ( $adGroups as $adGroup ) {
                    if ( !empty($adGroup->googleAds) ) {
                        $replPage = $adGroup->googleAds[0]->ad_href;
                        break;
                    }
                }
            }
            
            // $replPage = isset($adGroups[0]->yandexAds) ? $adGroups[0]->yandexAds[0]->ad_href : $adGroups[0]->googleAds[0]->ad_href;
        }

//        debug($replPage);

        // загружаем все размеченные подмены для данной страницы
        $marks = Mark::find()->where(['user_id' => $userId])->andWhere(['url' => $replPage])->all();
        
        $curReplacements = Replacement::find()
			->where(['in', 'repl_identity_id', Parser::getReplacementIdentityIdsForCampaign($replCmp)])
			->all();

        \app\assets\ReplacementAsset::register($this->view);
        return $this->render('index', [
            'replCmp' => $replCmp,
            'replPage' => $replPage,
            'yaCampaigns' => $yaCampaigns,
            'gCampaigns' => $gCampaigns,
            'marks' => $marks,
            'curReplacements' => $curReplacements,
            'adGroups' => $adGroups,
            'paginationPages' => $this->paginationPages,
            'pageNumber' => $this->getPageSize()
        ]);
    }

    protected function getReplCmp($cmpId, $type) {
        if ($type == 'yandex') {
            return YandexCampaign::find()
                ->where(['campaign_id' => $cmpId])
                ->one();
        }
        if ($type == 'google') {
            return GoogleCampaign::find()
                ->where(['campaign_id' => $cmpId])
                ->one();
        }

    }

    protected function getAdGroups($replCmp) {
        if ( get_class($replCmp) === 'app\models\YandexCampaign' ) {
            $query = YandexAdGroup::find()
                ->where(['campaign_id' => $replCmp->campaign_id])
                ->with(['yandexAds', 'yandexKeywords', 'replacementIdentity']);
            $this->paginationPages = new Pagination(['totalCount' => $query->count(), 'pageSize' => $this->getPageSize()]);
            $adGroups = $query->offset($this->paginationPages->offset)
                ->limit($this->paginationPages->limit)
                ->all();
        } else {
            $query = GoogleAdGroup::find()
                ->where(['campaign_id' => $replCmp->campaign_id])
                ->with(['googleAds', 'googleTargetings', 'replacementIdentity']);
            $this->paginationPages = new Pagination(['totalCount' => $query->count(), 'pageSize' => $this->getPageSize()]);
            $adGroups = $query->offset($this->paginationPages->offset)
                ->limit($this->paginationPages->limit)
                ->all();
        }
        return $adGroups;
    }

//    protected function getReplCmp($cmpId, $type) {
//        if ($type == 'yandex') {
//            return YandexCampaign::find()
//                ->where(['campaign_id' => $cmpId])
//                ->with(['yandexAdGroups' =>
//                    function($query) {
//                        $this->paginationPages = new Pagination(['totalCount' => $query->count(), 'pageSize' => $this->getPageSize()]);
//                        $query->offset($this->paginationPages->offset)->limit($this->paginationPages->limit)->all();
//                    }
//                ])
//                ->one();
//        }
//        if ($type == 'google') {
//            return GoogleCampaign::find()
//                ->where(['campaign_id' => $cmpId])
//                ->with(['googleAdGroups' =>
//                    function($query) {
//                        $this->paginationPages = new Pagination(['totalCount' => $query->count(), 'pageSize' => $this->getPageSize()]);
//                        $query->offset($this->paginationPages->offset)->limit($this->paginationPages->limit)->all();
//                        $query->with(['googleAds', 'googleTargetings', 'replacementIdentity']);
//                    }
//                ])
//                ->one();
//        }
//
//    }

    protected function getPageSize() {
        return isset($_COOKIE['page_number']) ? $_COOKIE['page_number'] : 30;
    }
    
    public function actionSaveTxt() {
        if ( !Yii::$app->request->isAjax ) exit;
        $repl_identity_id = Yii::$app->request->post('repl_identity_id');
        $mark_id = Yii::$app->request->post('mark_id');
        $delta = Yii::$app->request->post('delta');

        $isNullDelta = '{"ops":[{"insert":"\n"}]}';

        $dbReplacement = Replacement::find()
            ->where(['repl_identity_id' => $repl_identity_id])
            ->andWhere(['mark_id' => $mark_id])
            ->one();

        if (!$dbReplacement) {
            if ($delta === $isNullDelta) {
                return 'проигнорировано';
            } else {
                $replacement = new Replacement();
                $replacement->user_id = Yii::$app->user->getId();
                $replacement->repl_identity_id = $repl_identity_id;
                $replacement->mark_id = $mark_id;
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
        if ( !Yii::$app->request->isAjax ) exit;
        $data = Yii::$app->request->post('data');
        if ( !$data ) exit;
        $data = json_decode($data);
        $replacement = Replacement::find()
            ->where(['mark_id' => $data->mark_id])
            ->andWhere(['repl_identity_id' => $data->repl_identity_id])
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
        if ( !Yii::$app->request->isAjax ) exit;
        $data = Yii::$app->request->post('data');
        if ( !$data ) exit;
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
        $replacement = Replacement::find()
            ->where(['mark_id' => $data->mark_id])
            ->andWhere(['repl_identity_id' => $data->repl_identity_id])
            ->andWhere(['user_id' => Yii::$app->user->getId()])
            ->one();
        if (!$replacement) {
            $replacement = new Replacement();
            $replacement->user_id = Yii::$app->user->getId();
            $replacement->repl_identity_id = $data->repl_identity_id;
            $replacement->mark_id = $data->mark_id;
            $replacement->image_name = basename($data->src);
            $replacement->save();
        } else {
            $rplNumberByImageName = Replacement::find()
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
        $rpls = Replacement::find()
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
        if ( !Yii::$app->request->isAjax ) exit;
        $data = Yii::$app->request->post('data');
        if ( !$data ) exit;
        $data = json_decode($data);
        $replacement = Replacement::find()
            ->where(['mark_id' => $data->mark_id])
            ->andWhere(['repl_identity_id' => $data->repl_identity_id])
            ->andWhere(['user_id' => Yii::$app->user->getId()])
            ->one();
        $imageName = $replacement->image_name;
        $replacement->delete();
        $this->delLocalImg($imageName);
        return 'удалено';
    }

    public function actionDownloadAnimation() {
        return "Загрузка...";
        // return $this->renderAjax('download-animation');
    }

    public function actionGetReplacementByDisplayIdentity($replIdentityId, $replPage) {
        $displayIdentity = getRandomString();
        $replIdentity = ReplacementIdentity::findOne($replIdentityId);
        $replIdentity->display_identity = $displayIdentity;
        $replIdentity->update();
        // $href = Parser::getFramePage($replPage) . '&reked_di=' . $displayIdentity;
        $href = $replPage . '?reked_di=' . $displayIdentity;
        return $href;
    }

    public function actionDelRpl($repl_identity_id){
        if ( !Yii::$app->request->isAjax ) exit;
        $replacements = Replacement::find()->where(['user_id' => Yii::$app->user->getId()])
            ->andWhere(['repl_identity_id' => $repl_identity_id])
            ->all();
        foreach ($replacements as $replacement){
            $replacement->delete();
            if ( $replacement->image_name ){
                $this->delLocalImg($replacement->image_name);
            }
        }
        return 'удалено';
    }

    protected function delLocalImg($imageName){
        $other_rpls = Replacement::find()
            ->where(['like', 'image_name', $imageName])
            ->andWhere(['user_id' => Yii::$app->user->getId()])
            ->all();
        if ( empty($other_rpls) )
            FileHelper::unlink(getUserImagePath() . $imageName);
    }

}
