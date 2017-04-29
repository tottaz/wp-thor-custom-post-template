<?php
if(!current_user_can('manage_options')) {
	wp_die(__('You have not sufficient capabilities to view this page', 'cbt'), __('Get the right permission', 'cbt'));
}
?>
<div class="thor-stuffbox" style="margin-top: 50px;">
	<h3 class="thor-h3"><?php _e('Custom Post Type Template › Settings','cbt'); ?></h3>

<?php
	$args = array(
				'public'   => true,
				'_builtin' => false
			);
		$post_types = get_post_types($args);
		array_push($post_types,'post');
	//Save Form value
	$msg = '';
	if ( count($_POST) > 0 && isset($_POST['template_settings'])){
			
			if(!empty($_POST['post_type_name'])){
				$impVal=implode(',', $_POST['post_type_name']);
				delete_option ( 'thor_cbt');
				add_option ( 'thor_cbt', $impVal );
				$msg = '<div id="message" class="updated below-h2 msgText"><p>Setting Saved.</p></div>';
			}else{
				delete_option ( 'thor_cbt');
				add_option ( 'thor_cbt', 'post' );
				$msg = '<div id="message" class="error msgText"><p>Please select atleast one post type.</p></div>';
			}
			
	}
	if(isset($_REQUEST['template_reset']) && $_REQUEST['template_reset']=='reset'){
			
			$impVal =$_POST['post_type_name']='';
			delete_option ( 'thor_cbt');
			add_option ( 'thor_cbt','post');	
			
			$msg = '<div id="message" class="updated below-h2 msgText"><p>Default Setting Saved.</p></div>';
	}	
?>

	<div class="thor-settings-wrap">
		<div class="thor-stuffbox" style="margin-top: 30px;">
			<?php echo $msg; ?>
			<div class="inside">
			<form action="" method="post" enctype="multipart/form-data" class="form-inline">
				<div class="form-group">
					<?php foreach($post_types as $type) {
							 $obj = get_post_type_object( $type );
							 $post_types_name = $obj->labels->singular_name; 
							
							if(get_option('thor_cbt') != '') {
								$postType_title = get_option('thor_cbt');
								$postType_chkd = explode(',',$postType_title);
								$chd = '';
								if(in_array($type, $postType_chkd)){
									 $chd = 'checked="checked"';
								}
							}		
					?>
					<div class="checkbox-inline">
							<input type="checkbox" name="post_type_name[]" value="<?php echo $type; ?>" id="<?php echo $type; ?>" <?php echo $chd; ?> class="form-control" />
							<label for="<?php echo $type; ?>"><?php echo $post_types_name; ?></label>
					</div>
					<?php } ?>
				</div>

				<div class="type_submit">
					<button type="submit" class="btn btn-primary">Submit</button> 
					<input type="hidden" name="template_settings" value="save" style="display:none;" />
				</div>
			</form>
			<blockquote><?php _e('Note: Select one or more custom post type where you need to enable custom post template selection.');?></blockquote>
			</div>	
		</div>
	</div>
</div>