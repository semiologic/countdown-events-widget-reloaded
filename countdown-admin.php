<?php



function dtr_admin_menu()
{
	add_options_page('Events Widget', 'Events Widget', 'manage_options', __FILE__, 'dtr_options_page');
}



function dtr_options_page()
{
	$date_formats = array(
		'M j',
		'M jS',
		'F j',
		'F jS',
		'j M',
		'jS M',
		'j F',
		'jS F',
		);

	if (isset($_POST['Submit'])) {
		check_admin_referer('countdown');
		$_POST = array_map('stripslashes', $_POST);

		$listformat = stripslashes(wp_filter_post_kses($_POST['listformat']));
		$dateformat = $_POST['dateformat'];
		$timeoffset = 0;
	
		if ( !in_array($dateformat, $date_formats) )
		{
			$dateformat = 'M j';
		}

		$options = array(
			'listformat' => $listformat,
			'dateformat' => $dateformat,
			'timeoffset' => $timeoffset
			);
		
		update_option('dtr_options', $options);
		echo '<div id="message" class="updated fade"><p><strong>Options Updated.</strong></p></div>';
	}
	$options = get_option('dtr_options');
	if(!is_array($options)) {
		$options['listformat'] = '<b>%date%</b> (%until%)<br />%event%';
		$options['dateformat'] = 'M j';
		$options['timeoffset'] = 0;
	}
	?>	<div class="wrap">
	<h2>Countdown Settings</h2>
	<form method="post">
		<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('countdown'); ?>

		<h3>Formatting</h3>
		
		<table class="form-table">
		
		<tr valign="top">
		<th scope="row">Event List</th>
		<td>
		<textarea name="listformat" cols="58" rows="4"><?php
		echo esc_html($options['listformat']);
		?></textarea>

		<p>Use HTML and the following special tags to format the way events get displayed in widgets:</p>
		<ul>
			<li>%date% - Outputs the event date in the format specified below.</li>
			<li>%event% - Outputs the event name.</li>
			<li>%until% - Outputs the days remaining until that event.</li>
		</ul>

		</td>
		</tr>
		
		<tr valign="top">
		<th scope="row">Date format</th>
		<td>
		<select name="dateformat">
		<?php
		foreach ( $date_formats as $k )
		{
			echo '<option value="' . $k . '"'
				. ( $k == $options['dateformat']
					? ' selected="selected"'
					: ''
					)
				. '>'
				. mysql2date($k, current_time('mysql'))
				. '</option>';
		}
		?>
		</select>
		</td>
		</tr>
		</table>

		<h3>Current Time</h3>
		
		<table class="form-table">
		
		<tr valign="top">
		<td>
		Based on your <a href="<?php echo trailingslashit(site_url()); ?>wp-admin/options-general.php">your timezone settings</a> in WordPress, it is <?php echo date('Y-m-d h:i a', current_time( 'timestamp', 0 )); ?>.
		</td>
		</tr>
		</table>

		<p class="submit"><input type="submit" name="Submit" value="Submit" /></p>
	</form>
	</div>
	<?php
}

add_action('admin_menu', 'dtr_admin_menu');

?>