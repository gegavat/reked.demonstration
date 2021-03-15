<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class UploadImageForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $imageFile;
    public $imageName;

    public function rules()
    {
        return [
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, gif'],
            [['imageName'], 'safe'],
        ];
    }

    public function upload()
    {
        if ($this->validate()) {
            $this->imageName = getRandomString() . '.' . $this->imageFile->extension;
//            $this->imageFile->saveAs(getUserImagePath() . $this->imageName);
            $this->imageFile->saveAs(Yii::getAlias('@image_path') . '/temp/' . $this->imageName);
            return true;
        } else {
            return false;
        }
    }
}