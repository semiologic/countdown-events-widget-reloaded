<?php
/*
Plugin Name: Countdown Events Widget Reloaded
Plugin URI: http://redalt.com/wiki/Countdown
Description: Adds template tags to count down to a specified date. Browse Manage / Events to configure your events.
Version: 3.2 fork
Author: Owen Winkler & Denis de Bernardy
Author URI: http://www.getsemiologic.com
License: MIT License - http://www.opensource.org/licenses/mit-license.php
*/

/*
Countdown - Adds template tags to count down to a specified date

This code is licensed under the MIT License.
http://www.opensource.org/licenses/mit-license.php
Copyright (c) 2006 Owen Winkler

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the
Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software,
and to permit persons to whom the Software is furnished to
do so, subject to the following conditions:

The above copyright notice and this permission notice shall
be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Fork since v.1.2, by Denis de Bernardy <http://www.semiologic.com>

- Widget support
- default options
- Mu compat
- Security fixes
- Revamp of admin interface
*/


function dtr_monthtonum($m)
{
	switch(strtolower(substr($m, 0, 3)))
	{
	case 'jan': return 1;
	case 'feb': return 2;
	case 'mar': return 3;
	case 'apr': return 4;
	case 'may': return 5;
	case 'jun': return 6;
	case 'jul': return 7;
	case 'aug': return 8;
	case 'sep': return 9;
	case 'oct': return 10;
	case 'nov': return 11;
	case 'dec': return 12;
	}
	return 0;
}

function dtr_weekdaytonum($m)
{
	switch(strtolower(substr($m, 0, 3)))
	{
	case 'mon': return 1;
	case 'tue': return 2;
	case 'wed': return 3;
	case 'thu': return 4;
	case 'fri': return 5;
	case 'sat': return 6;
	case 'sun': return 0;
	}
	return 0;
}

function dtr_xsttonum($x)
{
	switch(substr($x, 0, 1))
	{
	case '1': return 1;
	case '2': return 2;
	case '3': return 3;
	case '4': return 4;
	case '5': return 5;
	case 'l': return 6;
	}
}

function dtr_xst_weekday($index, $weekday, $month)
{
	$now = getdate();
	$year = $now['year'] + (($month<$now['mon'])? 1 : 0);

	$day = 1;
	$firstday = intval(date('w', mktime(0,0,0,$month, $day, $year)));

	$day += $weekday - $firstday;
	if($day <= 0) $day += 7;
	$index --;
	while($index > 0)
	{
		$day += 7;
		$index --;
		if(!checkdate($month, $day + 7, $year)) break;
	}
	return mktime(0, 0, 0, $month, $day, $year);
}

function dates_to_remember($showonly = -1, $timefrom = null, $startswith = '<li>', $endswith = '</li>', $paststartswith = '<li class="pastevent">', $pastendswith = '</li>')
{
	$options = get_option('dtr_options');
	if(!is_array($options)) {
		$options['listformat'] = '<b>%date%</b> (%until%)<br />' . "\n" . '%event%';
		$options['dateformat'] = 'M j';
		$options['timeoffset'] = 0;
		update_option('dtr_options', $options);
	}

	$datefile = get_option('countdown_datefile');

	if ( !$datefile )
	{
		$datefile = implode('', file(dirname(__FILE__) . '/default-dates.txt'));

		update_option('countdown_datefile', $datefile);
	}

	#echo '<pre>';
	#var_dump($datefile, get_option('countdown_datefile'));
	#echo '</pre>';

	$dates = explode("\n", $datefile);
	$dtr = array();
	$dtrflags = array();

	if($timefrom == null) $timefrom = strtotime(date('Y-m-d', time() + ($options['timeoffset'] * 3600)));

	foreach($dates as $entry)
	{
		$entry = trim($entry);

		if ( $entry == ''
			|| strpos($entry, '#') === 0
			|| strpos($entry, '*') === 0
			)
		{
			continue;
		}

		if ( preg_match('/every ?(2nd|other|3rd|4th)? week (starting|from) ([0-9]{4}-[0-9]{2}-[0-9]{2})( until ([0-9]{4}-[0-9]{2}-[0-9]{2}))?[\\s]+(.*)/i',
			$entry, $matches)
			)
		{
			switch($matches[1])
			{
			case '2nd':
			case 'other': $inc = 14; break;
			case '3rd': $inc = 21; break;
			case '4th': $inc = 28; break;
			default: $inc = 7;
			}
			$date_info = getdate(strtotime($matches[3]));
			$absday = ceil($date_info[0] / 86400);
			$today_info = getdate(time() + ($options['timeoffset'] * 3600));
			$todayday = ceil($today_info[0] / 86400);
			if($absday == $todayday)
			{
				$eventtime = $absday * 86400;
			}
			else
			{
				$chunk = ceil(($todayday - $absday) / $inc);
				$absday = $absday + ($chunk * $inc);
				$eventtime = $absday * 86400;
			}
			if($matches[5] != '')
			{
				$limit = strtotime($matches[5]);
				if($timefrom - 86400 > $limit) $eventtime = $limit;
			}
			$eventname = $matches[6];
		}
		elseif ( preg_match('/easter[\\s]+(.*)/i', $entry, $matches)
			&& function_exists('easter_date')
			) {
			$eventtime = easter_date(intval(date('Y')));
			if($eventtime < time()) $eventtime = easter_date(intval(date('Y')) + 1);
			$eventname = $matches[1];
		}
		elseif ( preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})[\\s]+(?:through|thru)[\\s+]([0-9]{4}-[0-9]{2}-[0-9]{2})[\\s]+(.*)/i',
			$entry, $matches)
			)
		{
			$eventtime = strtotime($matches[1]);
			$eventname = $matches[3];
		}
		elseif ( preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})[\\s]+(.*)/i', $entry, $matches) )
		{
			$eventtime = strtotime($matches[1]);
			$eventname = $matches[2];
		}
		elseif ( preg_match('/([0-9]{2}-[0-9]{2})[\\s]+(.*)/i', $entry, $matches) )
		{
			$eventtime = strtotime(date('Y', time() + ($options['timeoffset'] * 3600)).'-'.$matches[1]);
			if($timefrom > $eventtime) $eventtime = strtotime(date('Y', time() + 31536000).'-'.$matches[1]);
			$eventname = $matches[2];
		}
		elseif ( preg_match('/(1st|2nd|3rd|4th|5th|last)[\\s]+(mon(?:day)?|tue(?:sday)?|wed(?:nesday)?|thu(?:rsday)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?)[\\s]+(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:tember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?|all)(.*)/i',
			$entry, $matches)
			)
		{
			$eventname = $matches[4];
			$xst = dtr_xsttonum($matches[1]);
			$day = dtr_weekdaytonum($matches[2]);
			if($matches[3] == 'all')
			{
				$month = dtr_monthtonum(date('M', $timefrom));
				$eventtime = dtr_xst_weekday($xst, $day, $month);
				if($eventtime < $timefrom)
				{
					$zero_hour = getdate($timefrom);
					$month = dtr_monthtonum(date('M', mktime(0,0,0, ($zero_hour['mon'] % 12) + 1, 1, $zero_hour['year'])));
					$eventtime = dtr_xst_weekday($xst, $day, $month);
				}
			}
			else
			{
				$month = dtr_monthtonum($matches[3]);
				$eventtime = dtr_xst_weekday($xst, $day, $month);
			}
		}
		else
		{
			continue;
		}

		if ( preg_match('/^the[\\s]+(mon(?:day)?|tue(?:sday)?|wed(?:nesday)?|thu(?:rsday)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?)[\\s]+(before|after)/i',
			$entry, $matches)
			) {
			switch($matches[2]) {
				case 'before':
                    $direction = 'last';
                    break;
				case 'after':
                    $direction = 'next';
                    break;
                default:
                    $direction = 'next';
                    break;
			}
			$eventtime = strtotime("{$direction} {$matches[1]}", $eventtime);
		}
		
		if ( preg_match('/^([0-9]+)[\\s]+(days?|weeks?|months?)[\\s]+(before|after)/i',
			$entry, $matches)
			) {
            $direction = '+';
			switch($matches[3]) {
				case 'before':
                    $direction = '-';
                    break;
				case 'after':
                    $direction = '+';
                    break;
			}

			$amount = intval($matches[1]);
            $unit = "months";
			switch($matches[2]) {
			case 'week':
			case 'weeks':
				$unit = "weeks";
				break;
			case 'day':
			case 'days':
				$unit = "days";
				break;
			case 'month':
			case 'months':
				$unit = "months";
				break;
			}
			$eventtime = strtotime("{$direction}{$amount} {$unit}", $eventtime);
		}

		$flags = array();
		$eventname = preg_replace('/%(.*?)%/e', '($flags[]="\\1")?"":""', $eventname);

		if($timefrom <= $eventtime)
		{
			while(isset($dtr[$eventtime]) && $dtr[$eventtime] != $eventname) $eventtime ++;
			$dtr[$eventtime] = $eventname;
			$dtrflags[$eventtime] = $flags;
		}
	}
	ksort($dtr);

	foreach($dtr as $eventtime => $event)
	{
		$do_daystil = !in_array('nocountdown', $dtrflags[$eventtime]);
		countdown_days($event, date('Y-m-d', $eventtime), $startswith, $endswith, $paststartswith, $pastendswith, $do_daystil);
		$showonly --;
		if($showonly == 0) break;
	}
}

function countdown_days($event, $date, $startswith = '', $endswith = '', $paststartswith = '', $pastendswith = '', $do_daystil = true) {
	$options = get_option('dtr_options');

	$until = intval((strtotime($date) - strtotime(date('Y-m-d', time() + ($options['timeoffset'] * 3600)))) / 86400);
	$remaining = '';
	if($until >= 0) {
 		echo $startswith;
		$date_format = $options['dateformat'];
		$date_str = date($date_format, strtotime($date));
		if($do_daystil)
		{
			switch($until)
			{
			case 0: $remaining = 'Today'; break;
			case 1: $remaining = '1 day'; break;
			default: $remaining = "{$until} days"; break;
			}
		}
		echo str_replace(array('%date%', '%event%', '%until%', '%untilnum%'), array($date_str, $event, $remaining, $until), $options['listformat']);
		echo $endswith;
	}
	else
	{
 		echo $paststartswith;
		$date_format = $options['dateformat'];
		$date_str = date($date_format, strtotime($date));
		if($do_daystil)
		{
			switch(abs($until))
			{
			case 1: $remaining = '1 day ago'; break;
			default: $remaining = "{$until} days ago"; break;
			}
		}
		echo str_replace(array('%date%', '%event%', '%until%', '%untilnum%'), array($date_str, $event, $remaining, $until), $options['listformat']);
		echo $pastendswith;
	}
}


/**
 * countdown_widget
 *
 * @package Countdown
 **/

add_action('widgets_init', array('countdown_widget', 'widgets_init'));

/**
 * @property int|string alt_option_name
 */
class countdown_widget extends WP_Widget {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( get_option('widget_countdown') === false ) {
			foreach ( array(
				'countdown_widget' => 'upgrade',
				) as $ops => $method ) {
				if ( get_option($ops) !== false ) {
					$this->alt_option_name = $ops;
					add_filter('option_' . $ops, array(get_class($this), $method));
					break;
				}
			}
		}
	} # init()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	static function widgets_init() {
		register_widget('countdown_widget');
	} # widgets_init()
	
	
	/**
	 * countdown_widget()
	 *
	 * @return void
	 **/

	function countdown_widget() {
		$widget_ops = array(
			'classname' => 'countdown',
			'description' => __('Displays upcoming events, which you configure under Settings / Events.', 'countdown'),
			);
		
		$this->init();
		$this->WP_Widget('countdown', __('Events Widget', 'countdown'), $widget_ops);
	} # countdown_widget()
	
	
	/**
	 * widget()
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 **/

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, countdown_widget::defaults());
		extract($instance, EXTR_SKIP);
		
		$title = apply_filters('widget_title', $title);
		
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		
		echo '<ul>' . "\n";
		
		dates_to_remember($number);
		
		echo '</ul>' . "\n";
		
		echo $after_widget;
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = intval($new_instance['number']);
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, countdown_widget::defaults());
		extract($instance, EXTR_SKIP);

		echo '<p>'
			. '<label>'
			. __('Title', 'countdown') . '<br />'
			. '<input type="text" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. sprintf(__('Display %s upcoming events', 'countdown'),
				'<input type="text" size="3" name="' . $this->get_field_name('number') . '"'
					. ' value="' . intval($number) . '"'
					. ' />'
				)
			. '</label>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance
	 **/

	function defaults() {
		return array(
			'title' => __('Upcoming Events', 'countdown'),
			'number' => 5,
			);
	} # defaults()
	
	
	/**
	 * upgrade()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade($ops) {
		$widget_contexts = class_exists('widget_contexts')
			? get_option('widget_contexts')
			: false;
		
		if ( empty($ops['number']) )
			unset($ops['number']);
		
		if ( isset($widget_contexts['countdown']) ) {
			$ops['widget_contexts'] = $widget_contexts['countdown'];
		}
		
		return $ops;
	} # upgrade()
} # countdown_widget


if ( is_admin() )
{
	include dirname(__FILE__) . '/countdown-admin.php';
	include dirname(__FILE__) . '/countdown-manage.php';
}
?>