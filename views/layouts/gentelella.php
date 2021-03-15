<?php
// доступ для одного пользователя в режиме отладки
if ( constant('YII_ENV') === 'dev' ) {
    if ( Yii::$app->user->getId() != 42 ) {
        $content = '<div style="font-family: \'Open Sans\', sans-serif; margin: 20px">
    Улучшаем наш сервис...<br> Закончим в 5-00 (МСК)<br> Просим прощения за временные неудобства
    </div>';
        echo $content;
        exit;
    }
}

/**
 * @var string $content
 * @var \yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;
use app\widgets\Alert;

//$plHoldImg = "https://place-hold.it/128x128/15997f/ffffff.jpg&text=I&bold&italic&fontsize=50";
//$bundle = yiister\gentelella\assets\Asset::register($this);
//Yii::$app->view->registerLinkTag(['rel' => 'icon', 'type' => 'image/png', 'href' => '/web/favicon.ico']);
\app\assets\AppAsset::register($this);
//баланс
$dbBalance = \app\models\Balance::find()->where(['user_id' => Yii::$app->user->getId()])->one();
$balance = number_format($dbBalance->balance / Yii::$app->params['payMultiplier'], 2, ',', ' ');
//действующий тариф
$tariff = \app\models\AuthAssignment::find()->where(['user_id' => Yii::$app->user->getId()])->one();
$tariffOver = !Yii::$app->user->identity->tariff_activity; // время действия тарифа закончилось
//срок действия тарифа
$updTariffUser = new DateTime();
$updTariffUser->setTimestamp($tariff->updated_at);
$validity = $updTariffUser->add(new DateInterval('P30D'))->format('U');
?>
<?php $this->beginPage(); ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta charset="<?= Yii::$app->charset ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css?family=Luckiest+Guy" rel="stylesheet">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <?php if ( constant('YII_ENV') === 'prod' ) : ?>
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','GTM-WHDLFM3');</script>
        <!-- End Google Tag Manager -->
    <?php endif; ?>
</head>
<body class="nav-md" >
<?php $this->beginBody(); ?>
<?php if ( constant('YII_ENV') === 'prod' ) : ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WHDLFM3"
                      height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
<?php endif; ?>
<div class="container body">

    <div class="main_container">

        <div class="col-md-3 left_col">
            <div class="left_col scroll-view">

                <div class="navbar nav_title" style="border: 0;">
                    <!---<a href="/" class="site_title"><i class="fa fa-paw"></i> <span>Gentellela Alela!</span></a>-->
                    <a href="/" class="site_title"><img src="/web/images/reked-logo-min.png" style="height:80%; margin-left: 5px;" alt="">
                        <span style="font-family: 'Luckiest Guy', cursive; font-style: italic; font-weight: 500; font-size: 1.2em; vertical-align: middle; letter-spacing: 2px; color: #e6e6e6; margin-left: 3px;">REKED</span>
                    </a>
                </div>
                <div class="clearfix"></div>

                <div class="profile profile-balance finance">
                    <div class="balance-info">
                        <div class="balance-info-left">
                            <i class="fa fa-rub"></i>
                            <span><?= $balance ?></span>
                        </div>
                        <div class="balance-info-right">
                            <button class="btn btn-success btn-xs but-payment" data-balance="<?= $balance ?>">Пополнить</button>
                        </div>
                    </div>
                    <div class="btn-order-set">
                        <button
                                class="btn btn-success btn-xs"
                                data-cost="<?= Yii::$app->params['orderSetCost'] ?>"
                                title="Настроим сервис специально под Ваш сайт. Стоимость услуги <?= Yii::$app->params['orderSetCost'] ?> рублей">
                            Заказать настройку
                            <i class="fa fa-question-circle"></i>
                        </button>
                    </div>
                </div>

                <!-- sidebar menu -->
                <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">

                    <div class="menu_section">
                        <div class="margin-sidebar""></div>
                        <?=
                        \yiister\gentelella\widgets\Menu::widget(
                            [

//                                'activateItems' => true,
//                                'activateParents' => true,
//                                'activeCssClass' => 'active',

                                "items" => [
                                    ["label" => "Главная", "url" => ["site/index"], "icon" => "star"],
                                    [
                                        "label" => "Мультилендинг",
                                        "icon" => "puzzle-piece",
                                        "url" => "#",
                                        "items" => [
                                            ["label" => "1. Добавление аккаунтов", "url" => ["account/index"]],
                                            ["label" => "2. Загрузка кампаний", "url" => ["campaign/index"]],
                                            ["label" => "3. Вставка кода", "url" => ["insert-code/index"]],
                                            ["label" => "4. Разметка страниц", "url" => ["mark/index"]],
                                            ["label" => "5. Настройка подмен", "url" => ["replacement/index"]],
                                            ["label" => "6. Активация", "url" => ["repl-activate/index"]],
                                        ],
                                        'options'=> ['class'=>'repl_menu_item'],
                                    ],

                                    [
                                        "label" => "Геолендинг",
                                        "icon" => "flag",
                                        "url" => "#",
                                        "items" => [
                                            ["label" => "1. Добавление страниц", "url" => ["geo-page/index"]],
                                            ["label" => "2. Вставка кода", "url" => ["geo-insert-code/index"]],
                                            ["label" => "3. Разметка страниц", "url" => ["geo-mark/index"]],
                                            ["label" => "4. Настройка подмен", "url" => ["geo-replacement/index"]],
                                            ["label" => "5. Активация", "url" => ["geo-repl-activate/index"]],
                                        ],
                                        'options'=> ['class'=>'repl_menu_item'],
                                    ],

                                    ["label" => "Бид-менеджер", "url" => ["bidder/index"], "icon" => "bar-chart"],
                                    ["label" => "Финансы", "url" => ["payment/index"], "icon" => "money", 'options'=> ['class'=>'payment-item']],
                                    ["label" => "Документация", "url" => "https://help.reked.ru", "icon" => "mortar-board", 'template'=> '<a href="{url}" target="_blank">{icon}{label}</a>'],
                                ],
                            ]
                        )
                        ?>
                    </div>

                </div>
                <!-- /sidebar menu -->

                <?php /* <!-- /menu footer buttons -->

                <div class="sidebar-footer hidden-small">
                    <a data-toggle="tooltip" data-placement="top" title="Settings">
                        <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
                    </a>
                    <a data-toggle="tooltip" data-placement="top" title="FullScreen">
                        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
                    </a>
                    <a data-toggle="tooltip" data-placement="top" title="Lock">
                        <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
                    </a>
                    <a data-toggle="tooltip" data-placement="top" title="Logout">
                        <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
                    </a>
                </div>
                <!-- /menu footer buttons --> */ ?>
            </div>
        </div>

        <!-- top navigation -->
        <div class="top_nav">

            <div class="nav_menu">
                <nav class="" role="navigation">
                    <div class="nav toggle">
                        <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                    </div>

                    <ul class="nav navbar-nav navbar-right">
                        <li class="">
                            <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <span><?= Yii::$app->user->identity->email ?></span>
                                <span class=" fa fa-angle-down"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-usermenu pull-right">
                                <li>
                                    <a href="<?= Url::to(['/payment/personal-area']) ?>">Профиль</a>
                                </li>
                                <li>
                                    <a href="//help.reked.ru" target="_blank">Документация</a>
                                </li>
                                <li>
                                    <a href="<?= Url::to(['/site/logout']) ?>"><i class="fa fa-sign-out pull-right"></i>Выйти</a>
                                </li>
                            </ul>
                        </li>

                        <?php /*
                        <li role="presentation" class="dropdown">
                            <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-envelope-o"></i>
                                <span class="badge bg-green">6</span>
                            </a>
                            <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
                                <li>
                                    <a>
                      <span class="image">
                                        <img src="https://placehold.it/128x128" alt="Profile Image" />
                                    </span>
                      <span>
                                        <span>John Smith</span>
                      <span class="time">3 mins ago</span>
                      </span>
                      <span class="message">
                                        Film festivals used to be do-or-die moments for movie makers. They were where...
                                    </span>
                                    </a>
                                </li>
                                <li>
                                    <a>
                      <span class="image">
                                        <img src="https://placehold.it/128x128" alt="Profile Image" />
                                    </span>
                      <span>
                                        <span>John Smith</span>
                      <span class="time">3 mins ago</span>
                      </span>
                      <span class="message">
                                        Film festivals used to be do-or-die moments for movie makers. They were where...
                                    </span>
                                    </a>
                                </li>
                                <li>
                                    <a>
                      <span class="image">
                                        <img src="https://placehold.it/128x128" alt="Profile Image" />
                                    </span>
                      <span>
                                        <span>John Smith</span>
                      <span class="time">3 mins ago</span>
                      </span>
                      <span class="message">
                                        Film festivals used to be do-or-die moments for movie makers. They were where...
                                    </span>
                                    </a>
                                </li>
                                <li>
                                    <a>
                      <span class="image">
                                        <img src="https://placehold.it/128x128" alt="Profile Image" />
                                    </span>
                      <span>
                                        <span>John Smith</span>
                      <span class="time">3 mins ago</span>
                      </span>
                      <span class="message">
                                        Film festivals used to be do-or-die moments for movie makers. They were where...
                                    </span>
                                    </a>
                                </li>
                                <li>
                                    <div class="text-center">
                                        <a href="/">
                                            <strong>See All Alerts</strong>
                                            <i class="fa fa-angle-right"></i>
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        */ ?>

                        <li role="presentation" class="dropdown">
                            <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
                                <?php if ($tariffOver) : ?>
                                    <button class="btn btn-danger btn-xs">ТАРИФ: <?= strtoupper($tariff->item_name) ?></button>
                                <?php else : ?>
                                    <button class="btn btn-primary btn-xs">ТАРИФ: <?= strtoupper($tariff->item_name) ?></button>
                                <?php endif; ?>
                            </a>
                            <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
                                <li>
                                    <div>Тариф: <strong><?= strtoupper($tariff->item_name) ?></strong></div>
                                    <div>Цена: <strong><?= Yii::$app->params['costTariff'][$tariff->item_name] ?> <i class="fa fa-rub"></i> за <?= Yii::$app->params['checkInterval']['prolongation'] ?> дней</strong></div>
                                    <div>Активирован до: <strong <?php if($tariffOver) echo 'class="text-danger"' ?>><?= getRusDate($validity) ?></strong></div>
                                    <br>
                                    <?php if ( $tariff->item_name != Yii::$app->params['tariff'][0] && $tariffOver ) : ?>
                                    <button
                                            class="btn btn-danger btn-xs btn-block"
                                            id="but-prolong"
                                            data-tariff=<?= $tariff->item_name ?>
                                            data-cost-tariff=<?= Yii::$app->params['costTariff'][$tariff->item_name] ?>
                                    >Активировать тариф</button>
                                    <?php endif; ?>
                                    <a class="btn btn-default btn-xs" href="<?= Url::to(['/payment/change-tariff']) ?>">Сменить тариф</a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                </nav>
            </div>

        </div>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
            <?php if (isset($this->params['h1'])): ?>
                <div class="page-title">
                    <div class="title_left">
                        <h1><?= $this->params['h1'] ?></h1>
                    </div>
                    <div class="title_right">
                        <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search for...">
                                <span class="input-group-btn">
                                <button class="btn btn-default" type="button">Go!</button>
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="clearfix"></div>

            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
        <!-- /page content -->
        <!-- footer content -->
        <footer>
            <div class="pull-left">
                <div>© <a href="https://reked.ru">REKED</a> <?=date('Y')?></div>
            </div>
            <div class="pull-right">
                <?php /*
                Gentelella - Bootstrap Admin Template by <a href="https://colorlib.com" rel="nofollow" target="_blank">Colorlib</a><br />
                Extension for Yii framework 2 by <a href="http://yiister.ru" rel="nofollow" target="_blank">Yiister</a>
                */ ?>

                <div>Личный кабинет в режиме бета-тестирования</div>
                <div class="mail-help"><a href="<?= Url::to(['site/support-contact']) ?>">Написать в техподдержку</a></div>
            </div>
            <div class="clearfix"></div>
        </footer>
        <!-- /footer content -->
</div>

<div id="custom_notifications" class="custom-notifications dsp_none">
    <ul class="list-unstyled notifications clearfix" data-tabbed_notifications="notif-group">
    </ul>
    <div class="clearfix"></div>
    <div id="notif-group" class="tabbed_notifications"></div>
</div>

<!-- ajax animation -->
<div id='bckgr_dark'>
    <div>
        <img src="/web/images/ajax-loader-circle.svg">
        <?php /*<p>Ожидайте, идет загрузка</p> */ ?>
    </div>
</div>

<?php \yii\bootstrap\Modal::begin([
    'size' => 'modal-md',
    'header' => '<h2>Для пополнения кошелька введите сумму</h2>',
    'id' => 'show_pay',
]); ?>
<?php \yii\bootstrap\Modal::end() ?>

<?php \yii\bootstrap\Modal::begin([
    'size' => 'modal-md',
    'header' => '<h2>Видеоподсказка</h2>',
    'id' => 'show_video_tips',
]); ?>
<?php \yii\bootstrap\Modal::end() ?>

<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(); ?>