<?php

namespace app\components\addition;

//use yii\base\BaseObject;

class LoadStatus {

    // объект статуса загрузки кампаний
    public $campaignName = null;
    public $adGroupIds = [];
    public $adIds = [];
    public $targetingIds = [];
    public $errors;

    public function __construct() {
        $this->errors = (object)[
            'noAdGroups' => (object)[
                'campaignName' => null
            ],
            'noAds' => (object)[
                'adGroupIds' => []
            ],
            'noTargetings' => (object)[
                'adGroupIds' => []
            ],
        ];
    }

}