<?php
namespace app\assets;

use yii\web\AssetBundle;

class GeoReplacementAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/quill.bubble.css',
        'css/jquery.Jcrop.min.css',
        'css/replacement.css',
        'css/geo-replacement.css',
    ];
    public $js = [
        'js/quill.min.js',
        'js/jquery.Jcrop.min.js',
        'js/geo-replacement.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}
