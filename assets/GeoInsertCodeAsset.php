<?php

namespace app\assets;


use yii\web\AssetBundle;

class GeoInsertCodeAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/clipboard.min.js',
        'js/geo-insert-code.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}