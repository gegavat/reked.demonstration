<?php
/**
 * @var $cmpsApi \app\components\YandexApi
 * @var $cmpsDb \app\models\YandexCampaign
 */
use app\components\Parser;

$headers = $cmpsApi[0];
$cmpsApi = isset($cmpsApi[1]->result->Campaigns) ? $cmpsApi[1]->result->Campaigns : [];
// извлечение доступных баллов яндекс.директ
$units = Parser::getYandexUnits($headers);

$orderedCmpsApi = Parser::orderLoadedCmps($cmpsApi, $cmpsDb);
?>

<?php if ( empty($orderedCmpsApi) ) : ?>
    <div class="text-danger">В Яндексе нет активных рекламных кампаний...</div>
<?php else : ?>
    <table class="table table-striped table-hov">
        <thead>
        <tr>
            <th><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></th>
            <th>ID</th>
            <th>Название</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orderedCmpsApi as $cmpApi) : ?>
            <tr>
                <td>
                    <input type="checkbox" <?php if(isset($cmpApi->isDownloaded)) echo "disabled"?> class="form-check-input" data-id="<?= $cmpApi->Id ?>" data-name="<?= $cmpApi->Name ?>">
                </td>
                <td><?= $cmpApi->Id ?></td>
                <td><?= $cmpApi->Name ?> <?php if(isset($cmpApi->isDownloaded)) echo "<span class='text-danger'>(уже загружена)</span>"?> </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <hr>
    <?php if( (int)$units < 50000 ) : ?>
        <p class="alert alert-danger">Количество доступных баллов Яндекс.Директ: <i><?= $units ?></i><br>
            Возможны проблемы при загрузке кампаний</p>
    <?php else : ?>
        <p class="text-muted">Количество доступных баллов Яндекс.Директ: <i><?= $units ?></i></p>
    <?php endif; ?>
    <button class="btn btn-sm btn-primary" id="load-ya-cmp"><i class="fa fa-plus"></i>Загрузить кампании</button>

<?php endif; ?>
