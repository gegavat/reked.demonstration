<?php
/* @var $apiAcc object */
/* @var $dbAccs array */
use yii\helpers\Url;
$this->registerMetaTag(['name' => 'csrf-param', 'content' => Yii::$app->getRequest()->csrfParam], 'csrf-token');
$this->registerMetaTag(['name' => 'csrf-token', 'content' => Yii::$app->getRequest()->getCsrfToken()], 'csrf-param');

$this->registerCss(<<<CSS
    p {
        font-weight: 600;
    }
CSS
);
$this->registerJs(<<<JS
    function getCheckedCheckBoxes() {
        var checkboxes = document.getElementsByClassName('checkbox');
        var checkboxesChecked = [];
        for (var index = 0; index < checkboxes.length; index++) {
            if (checkboxes[index].checked)
                checkboxesChecked.push(checkboxes[index].value);
        }
        return checkboxesChecked;
    }
    
    document.getElementById('request').onclick=function() {
        var accounts = getCheckedCheckBoxes();
        if ( accounts.length === 0 )
            alert ('Необходимо выбрать хотя бы один аккаунт');
        else {
            var managerId = document.getElementById('request').dataset.manager_id;
            var currentUrl = window.location.href.split('?')[0];
            location.href = currentUrl + '?' + 'managerId=' + managerId + '&' + 'accIds=' + accounts.join('-');
        }
    }
JS
    , \yii\web\View::POS_END);
?>

<h2>Выберите аккаунты для загрузки</h2>
<div><?= $apiAcc->email ?> - <?= $apiAcc->mccAcc->managerId ?></div>
<hr>

<?php foreach ( $apiAcc->mccAcc->accIds as $accId ) : ?>
    <?php
    $isLoadedAcc = false;
    foreach ( $dbAccs as $dbAcc ) :
        if ( $dbAcc->account_id == $accId ) :
            $isLoadedAcc = true;
        endif;
    endforeach;
    if ($isLoadedAcc) :
        echo "<input class='checkbox' type='checkbox' value=$accId disabled>$accId (уже загружен)";
    else :
        echo "<input class='checkbox' type='checkbox' value=$accId>$accId";
    endif;
    ?>
    <br>
<?php endforeach; ?>
<p><button id="request" data-manager_id="<?= $apiAcc->mccAcc->managerId ?>">Выбрать</button></p>
