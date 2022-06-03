<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Standard;

use DateTime;
use Exception;

class DateTools
{
    /**
     * @param string $string
     *
     * @return DateTime
     * @throws Exception
     */
    public static function dateTimeFromString(string $string): DateTime
    {
        return new DateTime($string);
    }

    /**
     * @param int $timestamp
     *
     * @return DateTime
     */
    public static function dateTimeFromTimestamp(int $timestamp): DateTime
    {
        return (new DateTime())->setTimestamp($timestamp);
    }

    /**
     * @param string $string
     * @param string $format
     *
     * @return string
     */
    public static function format(string $string, string $format = 'Y-m-d'): string
    {
        /*
            $date = DateTools::format('2017-12-19', 'l F Y');
        */

        //condition
        if (!$string || $string == '0000-00-00 00:00:00' || $string == '0000-00-00') {
            return '';
        }

        //case
        switch ($format) {
            case 'mysql':
                $format = 'Y-m-d';
                break;

            case 'date':
                $format = 'Y-m-d';
                break;

            case 'datetime':
                $format = 'Y-m-d H:i:s';
                break;

            case 'standard':
                $format = 'Y-m-d H:i';
                break;

            case 'entry_list':
                $format = 'Y-m-d';
                break;
        }

        //condition
        if (is_numeric($string)) {
            //vars
            $string = date($format, $string);
        } else {
            //vars
            $string = (self::dateTimeFromString($string))->format($format);
        }

        //return
        return $string;
    }

    /**
     * @param string $date_1
     * @param string $date_2
     * @param string $format
     *
     * @return string
     */
    public static function intervalDifference(string $date_1, string $date_2, string $format = '%a'): string
    {
        // %y Year %m Month %d Day %h Hours %i Minute %s Seconds => 1 Year 3 Month 14 Day 11 Hours 49 Minute 36 Seconds
        // %y Year %m Month %d Day                               => 1 Year 3 Month 14 Days
        // %m Month %d Day                                       => 3 Month 14 Day
        // %d Day %h Hours                                       => 14 Day 11 Hours
        // %d Day                                                => 14 Days
        // %h Hours %i Minutes %s Seconds                        => 11 Hours 49 Minutes 36 Seconds
        // %i Minute %s Seconds                                  => 49 Minute 36 Seconds
        // %h Hours                                              => 11 Hours
        // %a Days                                               => 468 Days

        //vars
        $datetime_1 = self::dateTimeFromString($date_1);
        $datetime_2 = self::dateTimeFromString($date_2);
        $interval = $datetime_1->diff($datetime_2);

        //return
        return $interval->format($format);
    }

    /**
     * @param string $date
     * @param string $period
     * @param string $format
     *
     * @return [type]
     */
    public static function intervalAdd(string $date, string $period, string $format = 'Y-m-d'): string
    {
        //vars
        $datetime = self::dateTimeFromString($date);
        $datetime = $datetime->modify($period); //period ~ [+1 day, +1 week, +1 month, +1 year]

        //return
        return $datetime->format($format);
    }

    /**
     * @param string $birthdate
     *
     * @return string
     */
    public static function getAge(string $birthdate): string
    {
        return self::intervalDifference($birthdate, date('Y-m-d'), '%y');
    }

    /**
     * Convert seconds to string representation (eg: 2h 25m)
     *
     * @param int $time
     * @param string $separator
     *
     * @return string
     */
    public static function secondsToString(int $time, string $separator = ' '): string
    {
        //init vars
        $result = [];

        //vars
        $hours = floor($time / 3600);
        $minutes = ($time % 3600) / 60;

        //condition
        if ($hours > 0) {
            $result[] = $hours . 'h';
        }

        //condition
        if ($minutes > 0) {
            $result[] = $minutes . 'm';
        }

        //return
        return implode($separator, $result);
    }

    /**
     * @param string $dateStart
     * @param string $dateEnd
     * @param bool $include_last_day
     *
     * @return array
     */
    public static function dayRange(string $dateStart, string $dateEnd, bool $include_last_day = true): array
    {
        $dateStart = self::dateTimeFromString($dateStart);
        $dateEnd = self::dateTimeFromString($dateEnd);

        if ($include_last_day === true) {
            $dateEnd = $dateEnd->modify('+1 day'); //because the end day from period is not included
        }

        $datePeriod = new \DatePeriod(
            $dateStart,
            new \DateInterval('P1D'),
            $dateEnd
        );

        $listPeriod = [];

        foreach ($datePeriod as $date) {
            $listPeriod[] = $date;
        }

        return $listPeriod;
    }

    /*
    public static function dateTime(string $string): \DateTime
    {
        return new \DateTime($string);
    }

	public static function format(string $string, string $format = 'Y-m-d'): string
	{
		//condition
		if (!$string || $string == '0000-00-00 00:00:00' || $string == '0000-00-00') {
            return '';
        }

		//case
		switch ($format)
		{
			case 'mysql':
				$format = 'Y-m-d';
            break;

			case 'date':
				$format = 'Y-m-d';
			break;

			case 'datetime':
				$format = 'Y-m-d H:i:s';
			break;

			case 'standard':
				$format = 'Y-m-d H:i';
			break;

            case 'entry_list':
                $format = 'Y-m-d';
            break;
		}

		//condition
		if (is_numeric($string))
		{
			//vars
			$string = date($format, $string);
		}
		else
		{
			//vars
			$string = (self::dateTime($string))->format($format);
		}

		//return
		return $string;
    }

    public static function interval_difference($date_1, $date_2, $format = '%a')
    {
        // %y Year %m Month %d Day %h Hours %i Minute %s Seconds => 1 Year 3 Month 14 Day 11 Hours 49 Minute 36 Seconds
        // %y Year %m Month %d Day                               => 1 Year 3 Month 14 Days
        // %m Month %d Day                                       => 3 Month 14 Day
        // %d Day %h Hours                                       => 14 Day 11 Hours
        // %d Day                                                => 14 Days
        // %h Hours %i Minutes %s Seconds                        => 11 Hours 49 Minutes 36 Seconds
        // %i Minute %s Seconds                                  => 49 Minute 36 Seconds
        // %h Hours                                              => 11 Hours
        // %a Days                                               => 468 Days

        //vars
        $datetime_1 = new \DateTime($date_1);
        $datetime_2 = new \DateTime($date_2);
        $interval   = $datetime_1->diff($datetime_2);

        //return
        return $interval->format($format);
    }

    public static function interval_relative($date)
    {
        //vars
        $dateTime = strtotime($date);
        $diffTime = floor((time() - $dateTime) / 3600);

        //condition
        if ($diffTime == 0)
            return 1; //less than an hour

        //condition
        if ($diffTime == 1)
            return 2; //less than two hours

        //condition
        if ($diffTime < 8)
            return 3; //less than 8 hours

        //vars
        list($dateYear, $dateMonth, $dateDay, $dateWeek) = explode('-', date('Y-n-j-W', $dateTime));
        list($nowYear, $nowMonth, $nowDay, $nowWeek)     = explode('-', date('Y-n-j-W'));

        //condition
        if ($nowYear.'-'.$nowMonth.'-'.$nowDay == $dateYear.'-'.$dateMonth.'-'.$dateDay)
            return 4; //today

        //condition
        if (date('Y-n-j', strtotime('yesterday')) == $dateYear.'-'.$dateMonth.'-'.$dateDay)
            return 5; //yesterday

        //condition
        if ($nowYear.'-'.$nowWeek == $dateYear.'-'.$dateWeek)
            return 6; //this week

        //condition
        if (date('Y-W', time() - (86400 * 7)) == $dateYear.'-'.$dateWeek)
            return 7; //last week

        //condition
        if ($nowYear.'-'.$nowMonth == $dateYear.'-'.$dateMonth)
            return 8; //this month

        if (date('Y-n', strtotime('first day of previous month')) == $dateYear.'-'.$dateMonth)
            return 9; //last month

        if ($diffTime < 3 * 30 * 24)
            return 10; //less than 3 months

        //vars
        $diffYear = $nowYear - $dateYear;

        //case
        switch ($diffYear)
        {
            case 0:
                //return
                return 11; //this year
            break;

            case 1:
                //return
                return 12; //last year
            break;
        }

        //return
        return 13; //more than one year
    }

    public static function interval_add($date, $period, $format = 'Y-m-d')
    {
        //period ~ [+1 day, +1 week, +1 month, +1 year]
        //return
        return date($format, strtotime($period, strtotime($date)));
    }

    public static function checkDateFormat($date, $format = 'Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) == $date;
    }

    public static function getAge($birthdate)
    {
        return self::interval_difference($birthdate, date('Y-m-d'), '%y');
    }


    public static function secondsDifference(\DateTime $time_one, \Datetime $time_two, bool $positive = true): int
    {
        $time_diff = $time_one->getTimestamp() - $time_two->getTimestamp();

        if ($positive) {
            $time_diff = abs($time_diff);
        }

        return $time_diff;
    }


    public static function secondsToNumber(int $time, string $return = 'hours'): float
    {
        if ($return == 'minutes') {
            return round($time / 60, 2);
        } else {
            return round($time / 60 / 60, 2);
        }
    }


    public static function secondsToString(int $time, string $separator = ' '): string
    {
        //init vars
        $result = [];

        //vars
        $hours   = floor($time / 3600);
        $minutes = ($time % 3600) / 60;

        //condition
        if ($hours > 0) {
            $result[] = $hours.'h';
        }

        //condition
        if ($minutes > 0) {
            $result[] = $minutes.'m';
        }

        //return
        return implode($separator, $result);
    }

    public static function dateRange(string $dateStart, string $dateEnd, bool $include_last_day = true): array
    {
        $dateStart = new \DateTime($dateStart);
        $dateEnd   = new \DateTime($dateEnd);

        if ($include_last_day === true) {
            $dateEnd = $dateEnd->modify('+1 day'); //because the end date from period is not included
        }

        $datePeriod = new \DatePeriod(
            $dateStart,
            new \DateInterval('P1D'),
            $dateEnd
        );

        $listPeriod = [];

        foreach($datePeriod as $date) {
            $listPeriod[] = $date;
        }

        return $listPeriod;
    }

	/*

		static public function get_date_by_ywd($year, $week, $day)
			{
				//vars
				$date = new DateTime();
				$date->setISODate($year, $week, $day); //yyyy, ww, dd

				//return
				return $date->format('Y-m-d');
			}

		static public function get_date_by_yw($year, $week)
			{
				//vars
				$date = new DateTime();
				$date->setISODate($year, $week); //yyyy, ww

				//return
				return $date->format('Y-m-d');
			}
	*/
}
