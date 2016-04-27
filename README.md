# yii-calculatorWorkDays
Калькулятор рабочих дней.

Получает данные о праздничных днях (парсит) с сайта http://calendar.yoip.ru/work/2016-proizvodstvennyj-calendar.html и сохраняет в БД.

Обновляет раз в год при необходимости



INSTALLATION
------------

1) Скопировать папку `yii-calculatorWorkDays` в `protected/extensions`

2) Поправить файл `config/main.php`
```php
return [
  'components' => [
    'calculatorWorkDays' => [
			'class' => 'ext.yii-calculatorWorkDays.CalculatorWorkDaysComponent',
			//'curl_opt_proxy' => 'login:pass@host:3128', //прокси
		],
  ]
];
```

QUICK START
------------
```php
  /** @var CalculatorWorkDaysComponent $calcWD */
  $calcWD = Yii::app()->calculatorWorkDays;
  //Получить дату, начиная с 2016-05-01 посчитать 7 рабочих дней
  echo $calcWD->getDateFromWorkDay('2016-05-01',7); //Вернет "2016-05-13" (учитывая выходные/праздничные дни)
```