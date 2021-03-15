<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\helpers\Url;

class AppController extends Controller {

    protected function setMeta($title = null, $keywords = null, $description = null) {
        $this->view->title = $title;
        $this->view->registerMetaTag(['name' => 'keywords', 'content' => "$keywords"]);
        $this->view->registerMetaTag(['name' => 'description', 'content' => "$description"]);
    }

    public function behaviors() {

        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'controllers' => Yii::$app->params['allowedControllers'],
                        'actions' => Yii::$app->params['allowedActions'],
					    'roles' => ['?'],
                        // 'denyCallback' => function () {
                        //     return Yii::$app->response->redirect(['/site/login']);
                        // },
                    ],
                    [
                        'allow' => false,
                        'controllers' => Yii::$app->params['allowedControllers'],
                        'actions' => Yii::$app->params['allowedActions'],
                        'roles' => ['@'],
                        'denyCallback' => function () {
                            return Yii::$app->response->redirect(['/site/index']);
                        },
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ]
                ],
                'denyCallback' => function () {
                    return Yii::$app->response->redirect(['/site/login']);
                },
            ],
        ];
    }

}
