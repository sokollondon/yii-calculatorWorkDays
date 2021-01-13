<?php

class m160419_034812_calculator_workdays__holiday extends CDbMigration
{
	public function up()
	{
		//СОЗДАЕМ
		$this->createTable('{{holiday}}', [
			'id'            => 'pk',
			'date'       => 'date NOT NULL',
		]);
	}

	public function down()
	{
		$this->dropTable('{{holiday}}');
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