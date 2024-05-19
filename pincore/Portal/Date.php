<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal;

use Illuminate\Support\DateFactory;
use Pinoox\Component\Source\Portal;

/**
 * @method static \Illuminate\Support\Carbon create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromDate($year = null, $month = null, $day = null, $tz = null)
 * @method static \Illuminate\Support\Carbon|false createFromFormat($format, $time, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTime($hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTimeString($time, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTimestamp($timestamp, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTimestampMs($timestamp, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTimestampUTC($timestamp)
 * @method static \Illuminate\Support\Carbon createMidnightDate($year = null, $month = null, $day = null, $tz = null)
 * @method static \Illuminate\Support\Carbon|false createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
 * @method static void disableHumanDiffOption($humanDiffOption)
 * @method static void enableHumanDiffOption($humanDiffOption)
 * @method static mixed executeWithLocale($locale, $func)
 * @method static \Illuminate\Support\Carbon fromSerialized($value)
 * @method static array getAvailableLocales()
 * @method static array getDays()
 * @method static int getHumanDiffOptions()
 * @method static array getIsoUnits()
 * @method static array getLastErrors()
 * @method static string getLocale()
 * @method static int getMidDayAt()
 * @method static \Illuminate\Support\Carbon|null getTestNow()
 * @method static \Symfony\Component\Translation\TranslatorInterface getTranslator()
 * @method static int getWeekEndsAt()
 * @method static int getWeekStartsAt()
 * @method static array getWeekendDays()
 * @method static bool hasFormat($date, $format)
 * @method static bool hasMacro($name)
 * @method static bool hasRelativeKeywords($time)
 * @method static bool hasTestNow()
 * @method static \Illuminate\Support\Carbon instance($date)
 * @method static bool isImmutable()
 * @method static bool isModifiableUnit($unit)
 * @method static bool isMutable()
 * @method static bool isStrictModeEnabled()
 * @method static bool localeHasDiffOneDayWords($locale)
 * @method static bool localeHasDiffSyntax($locale)
 * @method static bool localeHasDiffTwoDayWords($locale)
 * @method static bool localeHasPeriodSyntax($locale)
 * @method static bool localeHasShortUnits($locale)
 * @method static void macro($name, $macro)
 * @method static \Illuminate\Support\Carbon|null make($var)
 * @method static \Illuminate\Support\Carbon maxValue()
 * @method static \Illuminate\Support\Carbon minValue()
 * @method static void mixin($mixin)
 * @method static \Illuminate\Support\Carbon now($tz = null)
 * @method static \Illuminate\Support\Carbon parse($time = null, $tz = null)
 * @method static string pluralUnit(string $unit)
 * @method static void resetMonthsOverflow()
 * @method static void resetToStringFormat()
 * @method static void resetYearsOverflow()
 * @method static void serializeUsing($callback)
 * @method static void setHumanDiffOptions($humanDiffOptions)
 * @method static bool setLocale($locale)
 * @method static void setMidDayAt($hour)
 * @method static void setTestNow($testNow = null)
 * @method static void setToStringFormat($format)
 * @method static void setTranslator(\Symfony\Component\Translation\TranslatorInterface $translator)
 * @method static void setUtf8($utf8)
 * @method static void setWeekEndsAt($day)
 * @method static void setWeekStartsAt($day)
 * @method static void setWeekendDays($days)
 * @method static bool shouldOverflowMonths()
 * @method static bool shouldOverflowYears()
 * @method static string singularUnit(string $unit)
 * @method static \Illuminate\Support\Carbon today($tz = null)
 * @method static \Illuminate\Support\Carbon tomorrow($tz = null)
 * @method static void useMonthsOverflow($monthsOverflow = true)
 * @method static void useStrictMode($strictModeEnabled = true)
 * @method static void useYearsOverflow($yearsOverflow = true)
 * @method static \Illuminate\Support\Carbon yesterday($tz = null)
 * @method static mixed use($handler)
 * @method static useDefault()
 * @method static useCallable(callable $callable)
 * @method static useClass($dateClass)
 * @method static useFactory($factory)
 * @method static \Illuminate\Support\DateFactory ___()
 *
 * @see \Illuminate\Support\DateFactory
 */
class Date extends Portal
{
	public static function __register(): void
	{
		self::__bind(DateFactory::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'date';
	}


	/**
	 * Get exclude method names .
	 * @return string[]
	 */
	public static function __exclude(): array
	{
		return [];
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [];
	}
}
