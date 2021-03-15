<?php

use yii\db\Migration;

/**
 * Class m181221_202911_add_secret_key_in_user_table
 */
class m181221_202911_add_secret_key_in_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'secret_key', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'secret_key');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181221_202911_add_secret_key_in_user_table cannot be reverted.\n";

        return false;
    }
    */
}
