<?php

/**
 * This is the model class for table "holiday".
 *
 * The followings are the available columns in table 'holiday':
 * @property integer $id
 * @property string $date
 */
class Holiday extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'holiday';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('date', 'required'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'date' => 'Date',
		);
	}

	public static function insertSeveral($items)
	{
		$values=$params=[];
		foreach ($items as $ind=>$date) {
			$params[]="(:date".$ind.")";
			$values[":date".$ind]=$date;
		}
		$sql="INSERT INTO ".Holiday::model()->tableName()." (date) VALUES ".implode(",",$params);
		Yii::app()->db->createCommand($sql)
			->bindValues($values)
			->execute();
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Holiday the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
