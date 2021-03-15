<?php
namespace app\assets;

use yii\web\AssetBundle;

class PaymentAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/icheck/icheck.css',
    ];
    public $js = [
        'js/icheck.min.js',
        'js/payment-change.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}