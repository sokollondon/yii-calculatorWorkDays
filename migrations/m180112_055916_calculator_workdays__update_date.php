<?php

class m180112_055916_calculator_workdays__update_date extends CDbMigration
{
	public function up()
	{
        $this->addColumn('{{holiday}}', 'update_date', 'date');
	}

	public function down()
	{
        $this->dropColumn('{{holiday}}', 'update_date');
	}

	/*
	// Use safeUp/safeDown to do migration with transaction
	public function safeUp()
	{
	}

	public function safeDown()
	{
	}
	*/
}