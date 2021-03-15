<?php
/**
 * @var $cmpsApi \app\components\GoogleApi
 * @var $cmpsDb \app\models\GoogleCampaign
 */
use app\components\Parser;
?>
<table class="table table-striped table-hov">
    <thead>
    <tr>
        <th><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></th>
        <th>ID</th>
        <th>Название</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $orderedCmpsApi = Parser::orderLoadedCmps($cmpsApi, $cmpsDb);
    ?>
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

<button class="btn btn-sm btn-primary" id="load-g-cmp"><i class="fa fa-plus"></i>Загрузить кампании</button>
