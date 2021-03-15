<?php
namespace app\assets;

use yii\web\AssetBundle;

class GeoMarkAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
    	'js/geo-mark.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}
