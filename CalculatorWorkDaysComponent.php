<?php

/**
 * Калькулятор рабочих дней
 * Получает данные о праздничных днях (парсит) с сайта http://calendar.yoip.ru/work/2016-proizvodstvennyj-calendar.html и сохраняет в БД.
 * Обновляет раз в год при необходимости
 *
 * @property string $errorMes Сообщение об ошибке
 * Class CalculatorWorkDaysComponent
 */
class CalculatorWorkDaysComponent extends CApplicationComponent
{
    public $errorMes;
    public $curl_opt_proxy;
    public $monthRu=[1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'];

    public function init(){
        Yii::import('ext.yii-calculatorWorkDays.models.*');
        parent::init();
    }

    public function updateHolidayBase($year)
    {
        $holidays = $this->parseHoliday($year);
        if(count($holidays)){
            Holiday::model()->deleteAll("date >= '".$year."-01-01' AND date < '".($year+1)."-01-01'");
            Holiday::insertSeveral($holidays);
            return true;
        }else{
            return false;
        }
    }

    /**
     * Парсит праздничные дни со стороннего сайта
     * @param $year
     * @return array|bool Массив праздничных-выходных дней
     *  Array
     *  (
     *    [0] => 2016-01-01
     *    [1] => 2016-01-02
     *  )
     */
    public function parseHoliday($year)
    {
        require_once(dirname(__FILE__).'/classes/simple_html_dom.php');

        //Получаем страницу
        $headers = [
            "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3",
            "Connection:keep-alive",
            "Host:calendar.yoip.ru",
            "User-Agent:Mozilla/5.0 (Windows NT 6.1; rv:20.0) Gecko/20100101 Firefox/20.0"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"http://calendar.yoip.ru/work/$year-proizvodstvennyj-calendar.html");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if($this->curl_opt_proxy)
            curl_setopt($ch, CURLOPT_PROXY, $this->curl_opt_proxy);
        $data = curl_exec($ch);//Получаем страницу
//        $data = file_get_contents(dirname(__FILE__)."/temp_page2parse.htm"); //test Получаем страницу из файла, чтобы не напрягать сторонний сайт
//        $fp = fopen(dirname(__FILE__)."/temp_page2parse.htm", "w");fwrite($fp, $data);fclose($fp); //Сохраняем страницу в файл для теста
//        echo $data;
        $html = str_get_html($data);

        //Парсим
        $holidays=[];
        foreach($html->find('.col-6') as $elMonth) {
            /** @var simple_html_dom_node $elMonth */
            //Получаем номер месяца
            $month_name=$elMonth->find('h2')[0]->plaintext;
            $month_name=explode(" ",$month_name)[0];
            $month = array_search($month_name,$this->monthRu);
            //var_dump($month);

            if($month){
                //Праздничные дни
                foreach ($elMonth->find('.danger') as $elCelebration) {
                    $day=$elCelebration->plaintext;
                    //var_dump($day);
                    if($day){
                        $holidays[]=date('Y-m-d',mktime(0,0,0,$month,$day,$year));
                    }
                }
                //Выходные дни
                foreach ($elMonth->find('.warning') as $elHoliday) {
                    $day=$elHoliday->plaintext;
                    //var_dump($day);
                    if($day){
                        $holidays[]=date('Y-m-d',mktime(0,0,0,$month,$day,$year));
                    }
                }
            }
        }

        sort($holidays);
        if(count($holidays)){
            return $holidays;
        }else{
            $this->errorMes="Не удалось получить данные о праздниках за год: ".$year;
            return false;
        }
    }

    /**
     * Считает дату по количеству рабочих дней
     * @param string $date_start Дата начала, например 2016-02-11
     * @param int $work_days Кол-во рабочих дней
     * @param bool $isIncludeDateStart Включать начальную дату?
     * @return int|bool Дата окончания
     */
    public function getDateFromWorkDay($date_start, $work_days, $isIncludeDateStart=true){
        if(!strtotime($date_start)) return false;
        //Получаем праздники за текущий и сле. год
        $year_current=(int)date("Y",strtotime($date_start));
        $holidays = $this->getHolidays($year_current,1);

        //Перебираем дни, считаем рабочие
        $day_count=1;
        $date_end=date("Y-m-d",strtotime($date_start));
        if($isIncludeDateStart){
            $date_end=date("Y-m-d",strtotime($date_end)-3600*24);//Вычитаем день
        }
        while($day_count<=$work_days){
            $date_end=date("Y-m-d",strtotime($date_end)+3600*24);//Прибавляем день
            //Если день рабочий -- счетчик_дней++
            if(!in_array($date_end,$holidays)){
                $day_count++;
            }
        }

        return $date_end;
    }

    /**
     * Плучить массив праздников
     * @param int $year_current
     * @param int $year_count На сколько лет вперед получать праздники
     * @param bool $is_update Обновлять базу если нет данных
     * @return array|false
     * Array
     * (
     * [0] => 2016-01-01
     * [1] => 2016-01-02
     * )
     */
    public function getHolidays($year_current, $year_count=0, $is_update=true)
    {
        $holidays = Yii::app()->db->createCommand()
            ->select('id, date, update_date')
            ->from(Holiday::model()->tableName())
            ->where('date >=:date_start AND date<=:date_end')
            ->order('date')
            ->bindValues([
                ':date_start'=>$year_current."-01-01",
                ':date_end'=>($year_current+$year_count)."-12-31",
            ])
            ->queryAll();
        $update_year = null;
        if(count($holidays)){
            reset($holidays);
            $update_year = date("Y", strtotime(current($holidays)['update_date']));
            $holidays = CHtml::listData($holidays, 'id', 'date');
        }

        if($is_update){
            //Если в базе нет данных -- обновляем базу
            if(!count($holidays)){
                $is_upd_ok = $this->updateHolidayBase($year_current);
                if($is_upd_ok){
                    $this->updateHolidayBase(($year_current+1));
                    $holidays = $this->getHolidays($year_current,1, false);
                }else{
                    return false;
                }
            }else{
                //Если данные устаревшие и должны быть новые -- обновляем базу
                if($update_year < $year_current && $year_current <= date("Y")){
                    $is_upd_ok = $this->updateHolidayBase($year_current);
                    if($is_upd_ok){
                        $this->updateHolidayBase(($year_current+1));
                        $holidays = $this->getHolidays($year_current,1, false);
                    }
                }else{
                    //Если в базе нет данных за сделующий год -- обновляем базу
                    $last_holiday = array_pop($holidays);
                    if(date('Y',strtotime($last_holiday))!=($year_current+1)){
                        $is_upd_ok = $this->updateHolidayBase($year_current+1);
                        if($is_upd_ok){
                            $holidays = $this->getHolidays($year_current,1, false);
                        }
                    }
                }
            }
        }

        return $holidays;
    }

}