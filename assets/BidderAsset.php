<?php
namespace app\assets;

use yii\web\AssetBundle;

class BidderAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/icheck/icheck.css',
    ];
    public $js = [
        'js/icheck.min.js',
        'js/input-number.js',
        'js/bidder.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}
