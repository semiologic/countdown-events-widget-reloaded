<?php



function dtr_admin_menu2()
{
	add_options_page('Events', 'Events', 'edit_pages', __FILE__, 'dtr_management_page');
}



function dtr_management_page()
{
	$datefile = get_option('countdown_datefile');

	if ( !$datefile )
	{
		$datefile = implode('', file(dirname(__FILE__) . '/default-dates.txt'));

		update_option('countdown_datefile', $datefile);
	}

	#echo '<pre>';
	#var_dump(get_option('countdown_datefile'), $_POST['dates']);
	#echo '</pre>';

	if ( !empty($_POST['update_countdown']) ) {
		check_admin_referer('countdown');
		$_POST = array_map('stripslashes', $_POST);

		$datefile = stripslashes(wp_filter_post_kses($_POST['dates']));

		update_option('countdown_datefile', $datefile);

		echo '<div id="message" class="updated fade"><p><strong>Options Updated.</strong></p></div>';
	}
	?>	<div class="wrap">
	<h2>Manage Events</h2>
	<form action="" method="post" id="countdown_events">
		<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('countdown'); ?>
		<input type="hidden" name="update_countdown" value="1" />
		<h3>Dates</h3>
		<table class="form-table">
		<tr valign="top">
		<td>
		<p>This is your current list of events. You can add new events manually, or you can use the form underneath.</p>
		<textarea style="width:95%; height:200px;" name="dates" id="dates" class="code"><?php echo esc_html($datefile); ?></textarea>
		</td>
		</table>
		<p class="submit"><input type="submit" value="Save Events" /></p>
	</form>

		<h3>Add an Event</h3>

		<script type="text/javascript">
		function $d(e)
		{
			return document.getElementById(e);
		}
		function newevent()
		{
			var event = '';
			if($d('net1').checked) {
				event = 'every ' + $d('t1freq').value + ' week from ' + $d('t1yearstart').value + '-' + $d('t1monthstart').value + '-' + $d('t1daystart').value;
				if($d('t1end').checked) event = event + ' until ' + $d('t1yearend').value + '-' + $d('t1monthend').value + '-' + $d('t1dayend').value;
			}
			if($d('net2').checked) {
				event = $d('t2year').value + '-' + $d('t2month').value + '-' + $d('t2day').value;
			}
			if($d('net3').checked) {
				event = $d('t3month').value + '-' + $d('t3day').value;
			}
			if($d('net4').checked) {
				event = $d('t4freq').value + ' ' + $d('t4day').value + ' ' + $d('t4month').value;
			}
			if(event == '') {
				alert('You need to select one of the options to specify the type of event to create.');
				return;
			}
			if($d('neweventname').value == '') {
				alert('You did not set an event name.  Set the event name at the top of the form.');
				return;
			}
			event += ' ' + $d('neweventname').value;
			//if(confirm('Add this event:\n' + event)) {
				$d('dates').value = $d('dates').value + '\n' + event;
				// alert('You must submit the options form to save this event.');
				$d('countdown_events').submit();
			//}
		}
		</script>

		<table class="form-table">
		<tr valign="top">
		<td colspan="2">
		<p>Use this form to add an event. Choose the type of event, fill out the attributes, and click "Create".</p>
		<p>Don't forget to submit the changes to your options (including the new events you've added) after you've created a new event with this form.</p>
		</td>
		</tr>
		
		<tr valign="top">
		<th scope="row">
		Event Name
		</th>
		<td>
		<input type="text" id="neweventname" size="60" />
		</td>
		</tr>
		
		<tr valign="top">
		<th scope="row">
		<label><input id="net2" type="radio" value="t2" name="eventtype" checked="checked" />&nbsp;Single Day Event</label>
		</th>
		<td>
		  Year: <input type="text" id="t2year" value="<?php echo mysql2date('Y', current_time('mysql')); ?>" size="4"/>
		  Month: <input type="text" id="t2month" value="<?php echo mysql2date('m', current_time('mysql')); ?>" size="4"/>
		  Day: <input type="text" id="t2day" value="<?php echo mysql2date('d', current_time('mysql')); ?>" size="4"/>
		</td>
		</tr>
		
		<tr valign="top">
		<th scope="row">
		<label><input id="net3" type="radio" value="t3" name="eventtype" />&nbsp;Yearly Event</label>
		</th>
		<td>
			Month: <input type="text" id="t3month" value="<?php echo mysql2date('m', current_time('mysql')); ?>" size="4"/>
			Day: <input type="text" id="t3day" value="<?php echo mysql2date('d', current_time('mysql')); ?>" size="4"/>
		</td>
		</tr>
		
		
		<tr valign="top">
		<th scope="row">
		<label><input id="net4" type="radio" value="t4" name="eventtype" />&nbsp;Special Event</label>
		</th>
		<td>
			<select id="t4freq"><option value="1st">1st</option><option value="2nd">2nd</option><option value="3rd">3rd</option><option value="4th">4th</option><option value="5th">5th</option><option value="last">last</option></select>
			<select id="t4day"><option value="mon">Monday</option><option value="tue">Tuesday</option><option value="wed">Wednesday</option><option value="thu">Thursday</option><option value="fri">Friday</option><option value="sat">Saturday</option><option value="sun">Sunday</option></select>
			of
			<select id="t4month"><option value="jan">January</option><option value="feb">February</option><option value="mar">March</option><option value="apr">April</option><option value="may">May</option><option value="jun">June</option><option value="jul">July</option><option value="aug">August</option><option value="sep">September</option><option value="oct">October</option><option value="nov">November</option><option value="dec">December</option><option value="any">Any</option></select>
		</td>
		</tr>
		

		<tr valign="top">
		<th scope="row">
		<label><input id="net1" type="radio" value="t1" name="eventtype" />&nbsp;Repeating Event</label>
		</th>
		<td>
			Every <select id="t1freq"><option value="">week</option><option value="2nd">2 weeks</option><option value="3rd">Every 3rd week</option><option value="4th">Every 4th week</option></select>
			<br />
			Starting:
			  Year:<input type="text" id="t1yearstart" value="<?php echo date('Y'); ?>" size="4"/>
			  Month:<input type="text" id="t1monthstart" value="<?php echo date('m'); ?>" size="4"/>
			  Day:<input type="text" id="t1daystart" value="<?php echo date('d'); ?>" size="4"/>
			<br />
			Ending (<label><input type="checkbox" id="t1end" />Use End Date?</label>):
			  Year:<input type="text" id="t1yearend" value="<?php echo date('Y'); ?>" size="4"/>
			  Month:<input type="text" id="t1monthend" value="<?php echo date('m'); ?>" size="4"/>
			  Day:<input type="text" id="t1dayend" value="<?php echo date('d'); ?>" size="4"/>
		</td>
		</tr>
		</table>

		<p class="submit"><input type="submit" value="Create Event" onclick="newevent();return false;" /> <input type="button" value="Save Events" onclick="countdown_events.submit();" /></p>

	<h3>Ucoming Events</h3>
	<p>The next 10 events based on your current settings:</p>
	<ul>
	<?php dates_to_remember(10); ?>	</ul>
	</div>
	<?php
}

add_action('admin_menu', 'dtr_admin_menu2');
?>