<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "geo_page".
 *
 * @property int $id
 * @property int $user_id
 * @property string $page
 * @property bool $enabled
 *
 */
class GeoPage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'geo_page';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'page'], 'required'],
            [['page'], 'string'],
            [['enabled'], 'boolean'],
            [['enabled'], 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'page' => 'Page',
            'enabled' => 'Enabled',
        ];
    }

}
