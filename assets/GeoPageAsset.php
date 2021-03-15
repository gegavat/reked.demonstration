<?php
namespace app\assets;

use yii\web\AssetBundle;

class GeoPageAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/geo-page.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}
