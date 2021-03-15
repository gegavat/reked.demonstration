<?php
namespace app\assets;

use yii\web\AssetBundle;

class MarkAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/mark.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}
