<?php
/**
 * @var $yaCampaigns \app\models\YandexCampaign
 * @var $gCampaigns \app\models\GoogleCampaign
 * @var $replCmp object
 * @var $marks array
 * @var $curReplacements array
 * @var $adGroups array
 * @var $replPage string
 * @var $paginationPages \yii\data\Pagination
 * @var $pageNumber int
 */

use yii\helpers\Url;
use app\components\Parser;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;
use yii\widgets\LinkPager;

$this->title = 'Настройка подмен';

$replCmpType = isset($replCmp->yandexAds) ? 'yandex' : 'google';
$adsName = ($replCmpType == 'yandex') ? 'yandexAds' : 'googleAds';

?>

<h3>Настройка подменяемых элементов
    <small class="text-info">Здесь нужно заполнить таблицу с подменами для размеченных страниц</small>
</h3>

<?php $videoTipLink = Yii::$app->params['videoTipUrls']['replacement'] ?>
<?php if ( Yii::$app->user->identity->video_tip && $videoTipLink ) : ?>
    <?= \app\components\widgets\VideoTipsWidget::widget(['videoUrl' => $videoTipLink]) ?>
<?php endif; ?>
<hr>

<?php Pjax::begin([
    'id' => 'pjax_replacement',
    'timeout' => 20000,
    'enablePushState' => true
]) ?>

<div id="repl_cont" class="repl-wrap">
    <div class="row">
        <div class="col-md-6">
            <h4>Рекламная кампания:</h4>
            <div class="form-group">
                <select class="form-control" id="replacement_campaign">
                    <option disabled>Выберите кампанию</option>
                    
                    <?php if (!empty($yaCampaigns) ) : ?>
                        <optgroup label="Кампании Яндекс Директ">
                            <?php foreach($yaCampaigns as $yaCampaign) : ?>
                                <option <?php if($replCmpType == 'yandex' && $yaCampaign->id == $replCmp->id) echo ' selected ' ?>
                                        value="ya_campaign=<?=$yaCampaign->campaign_id?>">
                                    <?=$yaCampaign->campaign_name?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    
                    <?php if (!empty($gCampaigns) ) : ?>
                        <optgroup label="Кампании Google Ads">
                            <?php foreach($gCampaigns as $gCampaign) : ?>
                                <option <?php if($replCmpType == 'google' && $gCampaign->id == $replCmp->id) echo ' selected ' ?>
                                        value="g_campaign=<?=$gCampaign->campaign_id?>">
                                <?=$gCampaign->campaign_name?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <h4>Страница сайта:</h4>
            <div class="form-group">
                <select class="form-control" id="replacement_page">
                    <option disabled>Выберите размеченную страницу</option>
                    <?php foreach (Parser::getUniqUrlsFromAdGroups($adGroups, $adsName) as $url) : ?>
                        <option <?php if ($url == $replPage) echo ' selected ' ?> value="<?= $url ?>">
                            <?= $url ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <?php
        // проверка размечена ли данная страница
        if ( empty($marks) ) :
            echo $this->render('no-marks', compact ('replPage'));
    ?>

    <?php else : ?>
        <div class="x_panel">

            <div class="x_content">
                <table class="table table-bordered table-striped">
                    <col>
                    <col>
                    <?php $col_size = getColumnSize($marks, 58.3); ?>
                    <?php foreach ( $marks as $mark ) : ?>
                        <?php if ( $mark->type === 'txt' ) : ?>
                            <col style="width: <?= $col_size->txt ?>%">
                        <?php endif; ?>
                        <?php if ( $mark->type === 'img' ) : ?>
                            <col style="width: <?= $col_size->img ?>%">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <col>
                    <thead>
                    <tr>
                        <th scope="col" rowspan="2" class="col-md-2">Объявления</th>
                        <th scope="col" rowspan="2" class="col-md-1">Таргетинги</th>
                        <th colspan="<?= count($marks) ?>" scope="colgroup" class="col-md-8">Подменяемые элементы</th>
                        <th scope="col" rowspan="2" class="col-md-1"></th>
                    </tr>
                    <tr>
                        <?php foreach ($marks as $mark) : ?>
                            <th scope="col" class="col-md-2"><?= $mark->name ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>

                    <tbody>
						<?php
                            $numb_txt_repl = 0; // номер текстовой подмены
						?>
						<?php foreach($adGroups as $adGroup) : ?>

                            <?php // если в группе нет объявлений, выдаем предупреждение ?>
                            <?php /*
                                // !!! временный костыль. Нужно исправить
                                // также есть в controllers/ReplacementController.php line-76
                            */ ?>
							<?php if ( empty($adGroup->$adsName) ) : ?>
                                <?php /*
								<tr>
									<td colspan="<?= 3 + count($marks) ?>">
										<p class="no-ads">
											В группе нет объявлений
										</p>
									</td>
								</tr>
                                */ ?>
							<?php else : ?>
                                <?php
                                    // оставляем лишь те объявления, ссылки которых ведут на $replPage
                                    // если отфильтрованных объявлений не осталось, не отображаем строку с группой
                                    $curAds = Parser::filterAdsByPage($adGroup->$adsName, $replPage);
                                    if ( empty($curAds) ) continue;
                                ?>
								<tr>
									<?php
										if ( $replCmpType == 'yandex' ) {
											echo $this->render('yandex-tr', [
                                                'curAds' => $curAds,
                                                'keywords' => $adGroup->yandexKeywords
                                            ]);
										} else {
											echo $this->render('google-tr', [
                                                'curAds' => $curAds,
                                                'targetings' => $adGroup->googleTargetings
                                            ]);
										}
									?>


                                    <?php foreach ($marks as $mark) : ?>
                                        <?php
											$replacement = Parser::getCurReplacementFromReplacementsByMarkAndAdGroup($curReplacements, $mark, $adGroup);
											//debug($replacement);
                                        ?>
                                        <td>
                                            <div class="td-container">

                                                <?php if ( $mark->type === 'img' ) : ?>

                                                    <?php if ( isset($replacement->image_name) ) : ?>
                                                        <div class="repl-img"
                                                             data-mark_id="<?= $mark->id ?>"
                                                             data-repl_identity_id="<?= $adGroup->replacementIdentity->id ?>"
                                                             data-selected="false"
                                                             data-width="<?= $mark->img_width ?>"
                                                             data-height="<?= $mark->img_height ?>"
                                                             data-mode="update"
                                                        >
                                                            <img src="<?= getUserImageUrl() . $replacement->image_name ?>"
                                                                 alt="<?= 'replacement-' . $replacement->id  ?>"
                                                            >
                                                        </div>
                                                    <?php else :  ?>
                                                        <div class="repl-img"
                                                             data-mark_id="<?= $mark->id ?>"
                                                             data-repl_identity_id="<?= $adGroup->replacementIdentity->id ?>"
                                                             data-selected="false"
                                                             data-width="<?= $mark->img_width ?>"
                                                             data-height="<?= $mark->img_height ?>"
                                                             data-mode="save"
                                                        >
                                                            <img src="/web/images/no-image.png" alt="no-image">
                                                        </div>
                                                    <?php endif; ?>

                                                <?php endif; ?>

                                                <?php if ( $mark->type === 'txt' ) : ?>

                                                    <span class="save-ok glyphicon glyphicon-ok" data-numb_txt_repl="<?= $numb_txt_repl ?>"></span>
                                                    <div class="txt-area"
														data-repl_identity_id="<?= $adGroup->replacementIdentity->id ?>"
														data-numb_txt_repl="<?= $numb_txt_repl ?>"
														data-mark_id="<?= $mark->id ?>"
														data-delta="<?= $replacement ? Parser::insertJsonIntoHtml($replacement->delta) : '' ?>"
													>
													</div>

                                                    <div class="editor-tbr-<?= $numb_txt_repl ?>">
                                                        <span class="ql-formats">
                                                            <button class="ql-bold"></button>
                                                            <button class="ql-italic"></button>
                                                            <button class="ql-underline"></button>
                                                            <button class="ql-header" value="1"></button>
                                                            <button class="ql-header" value="2"></button>
                                                            <select class="ql-size">
                                                                <option value="8px">8px</option>
                                                                <option value="10px">10px</option>
                                                                <option value="12px" selected>normal</option>
                                                                <option value="14px">14px</option>
                                                                <option value="16px">16px</option>
                                                                <option value="18px">18px</option>
                                                                <option value="20px">20px</option>
                                                                <option value="22px">22px</option>
                                                                <option value="24px">24px</option>
                                                                <option value="48px">48px</option>
                                                            </select>
                                                        </span>
                                                    </div>

                                                    <?php $numb_txt_repl++; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>




                                    <td>
										<span class="repl-icon repl-watch glyphicon glyphicon-eye-open"
                                              data-repl_page="<?= $replPage ?>"
                                              data-repl_identity_id="<?= $adGroup->replacementIdentity->id ?>"
                                              data-toggle="tooltip" data-placement="bottom" title="Просмотреть"
                                        ></span>
										<span class="repl-icon repl-del glyphicon glyphicon-remove"
                                              data-repl_identity_id="<?= $adGroup->replacementIdentity->id ?>"
                                              data-toggle="tooltip" data-placement="bottom" title="Очистить"
                                        ></span>
									</td>
									
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= LinkPager::widget([
    'pagination' => $paginationPages,
]); ?>
<br>

<?php if ( count($adGroups) >= 10 ) : ?>
    <ul class="pagination page-number">
        <li class="pagin_elem <?php if ($pageNumber == 10) echo ' active' ?>" data-page_numb="10"><a href="#">10</a></li>
        <li class="pagin_elem <?php if ($pageNumber == 30) echo ' active' ?>" data-page_numb="30"><a href="#">30</a></li>
        <li class="pagin_elem <?php if ($pageNumber == 50) echo ' active' ?>" data-page_numb="50"><a href="#">50</a></li>
        <li class="disabled"><span>записей на странице</span></li>
    </ul>
<?php endif; ?>

<a id="btn_pjax_replacement" style="display: none" href="<?= Yii::$app->request->getUrl() ?>">Refresh</a>
<?php Pjax::end() ?>

<?php Modal::begin([
    'size' => 'modal-lg',
    'header' => '<h2>Загрузка изображений</h2>',
    'id' => 'loading-image',
    'clientOptions' => ['backdrop' => 'static', 'keyboard' => false],
    'closeButton' => false
]); ?>
<?php Modal::end() ?>

<div class="extra-ads">
</div>
