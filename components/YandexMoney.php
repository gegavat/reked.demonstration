<?php /*

namespace app\components;

use yii\base\BaseObject;

class YandexMoney extends BaseObject {

    protected $formParams = [
        'url' => 'https://money.yandex.ru/quickpay/confirm.xml',
        'receiver' => '41001546857021',
        'quickpay-form' => 'shop',
        'targets' => 'Пополнение счета в сервисе Reked',
        'paymentType' => 'PC',
        'sum' => '1500'
    ];

    public function __construct($token = null, $config = []) {


        parent::__construct($config);
    }

    public function postRedirect($url, array $data) {
        $returnHtml = '<html><head><script>function closethisasap(){document.forms["post_redirect"].submit()}</script></head>' .
            '<body onload="closethisasap()"><form name="post_redirect" method="post" action="$th">' .
            '<input type="hidden" name="" '
    <?php
    if ( !is_null($data) ) {
        foreach ($data as $k => $v) {
            echo '<input type="hidden" name="' . $k . '" value="' . $v . '"> ';
        }
    }
    ?>
</form>
</body>
</html>

        ?>

        <?php
        exit;
    }

}
 */ ?>