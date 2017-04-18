<?php
if(!current_user_can('manage_options')) {
	wp_die(__('You have not sufficient capabilities to view this page', 'cbt'), __('Get the right permission', 'cbt'));
}
?>
<div class="thor-stuffbox" style="margin-top: 50px;">
	<h3 class="thor-h3"><?php _e('Custom Post Type Template › Settings','cbt'); ?></h3>
	  <?php
		if (isset($_GET['settings-updated'])) {
			echo '<div class="updated" ><p>'.__('Settings saved', 'cbt').'</p></div>';
		}			
?>
	<div class="thor-settings-wrap">
		<div class="thor-stuffbox" style="margin-top: 30px;">
			<div class="inside">
				<form method="post" action="options.php">
					<div id="settings">
						<?php settings_fields('thor-cbt-settings', 'thor_cbt_settings_section'); ?>
						<?php do_settings_sections('thor-cbt-settings'); ?>
						<?php submit_button(); ?>
					</div>
				</form>
			</div>			
		<br class="clear">
	  </div>
	</div>
</div>