<?php
namespace app\assets;

use yii\web\AssetBundle;

class ReplacementAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/quill.bubble.css',
        'css/replacement.css',
        'css/jquery.Jcrop.min.css'
    ];
    public $js = [
        'js/quill.min.js',
        'js/jquery.Jcrop.min.js',
        'js/replacement.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}
