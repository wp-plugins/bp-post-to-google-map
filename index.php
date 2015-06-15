<?php

/**

 * Plugin Name: BP Post to Google Map

 * Plugin URI:  http://beyond-paper.com/

 * Description: Adds geocoding to Posts to display on Google Map.

 * Version: 1.2

 * Author: Diane Ensey

 * Author URI: http://beyond-paper.com

 * License: GPL2

**/



/*  Copyright 2015 Diane Ensey (email: diane@beyond-paper.com)

    This program is free software; you can redistribute it and/or modify

    it under the terms of the GNU General Public License, version 2, as 

    published by the Free Software Foundation.



    This program is distributed in the hope that it will be useful,

    but WITHOUT ANY WARRANTY; without even the implied warranty of

    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

    GNU General Public License for more details.



    You should have received a copy of the GNU General Public License

    along with this program; if not, write to the Free Software

    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

defined( 'ABSPATH' ) or die( 'No direct access permitted!' );



register_activation_hook( __FILE__, 'bp_map_post_install' ); //registers a plugin function to run when the plugin is activated

register_uninstall_hook( __FILE__, 'bp_expire_category_uninstall' ); //deletes Settings 





/*******************************FRONT END SECTION ******************************************/



//Actions

add_shortcode( 'bpmap', 'bpmap_func' );

add_action ('wp_enqueue_scripts', 'bp_map_post_scripts'); 

add_action( 'wp_ajax_bp_map_data', 'bp_map_data' );

add_action( 'wp_ajax_nopriv_bp_map_data', 'bp_map_data' );

add_action( 'wp_head', 'bp_add_ajax_library' );







//Pre-populate the options

function bp_map_post_install() { 

	$value = '1';

	add_option( 'bp_map_settings[title]', $value );

	add_option( 'bp_map_settings[featured]', $value );

	add_option( 'bp_map_settings[class]', $value );

	add_option( 'bp_map_settings[excerpt]', $value );

	add_option( 'bp_map_settings[readmore]', $value );

	add_option( 'bp_map_settings[centerlat]', '0.351560' );

	add_option( 'bp_map_settings[centerlng]', '1.23469' );

	add_option( 'bp_map_settings[zoom]', '1' );



}

//Remove options on uninstallation

function bp_map_post_uninstall(){

	delete_option( 'bp_map_settings[title]');

	delete_option( 'bp_map_settings[featured]');

	delete_option( 'bp_map_settings[class]');

	delete_option( 'bp_map_settings[excerpt]');

	delete_option( 'bp_map_settings[readmore]');

	delete_option( 'bp_map_settings[centerlat]');

	delete_option( 'bp_map_settings[centerlng]');

	delete_option( 'bp_map_settings[zoom]');

}





/*

 *  Shortcode

*/

 function bpmap_func( $atts ) {

    $a = shortcode_atts( array(

        'width' => '800px',

        'height' => '600px',

    ), $atts );

	return '<div id="bpmap-canvas" >Loading...</div>';

}



/*

 * 

*/

function bp_map_post_scripts(){

	 if ( !is_admin()){

		wp_enqueue_style( 'bp_map_style', plugins_url( '/css/style.css', __FILE__ ) );

		wp_register_script('bp_map_post_js', plugins_url( '/js/bp_map_post.js', __FILE__ ),array('jquery') );

    	wp_enqueue_script('bp_map_post_js' );

		$opts = get_option( 'bp_map_settings');

		wp_localize_script( 'bp_map_post_js', 'bpOpts', $opts );



		

	 }

}



/**

 * Adds the WordPress Ajax Library to the frontend.

 */

function bp_add_ajax_library() {

 

    $html = '<script type="text/javascript">';

        $html .= 'var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '"';

    $html .= '</script>';

 

    echo $html;

 

} // end add_ajax_library



function bp_map_data(){

	global $wpdb;

	global $post;



$opts = get_option( 'bp_map_settings');

	

$args = array(

	'meta_key' => '_bp_map_show',

	'meta_value' => '1',

	'meta_compare' => '=',

	'post_type' => 'post'

);



$query = new WP_Query( $args );

	$a = array();

	$t = array();

	

	if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();

		$t['title'] = get_the_title();

		$opts['title'] ? $title = "<a href='".get_permalink()."'>".get_the_title()."</a>" : $title='';

		$opts['featured'] ? $feat = get_the_post_thumbnail( get_the_ID(), 'thumb') : $feat='';

		$opts['excerpt'] ? $exc = get_the_excerpt() : $exc = '';

		$opts['readmore'] ? $rm = "<a href='".get_permalink()."'>Read More...</a>" : $rm = '';

		if(isset($opts['class']) && $opts['class'] == '1'){$class = 'rside';}else{$class = '';}

		$t['html'] = "<div id='bpInfowindow' class='".$class."'>".$feat."<div><h2>".$title."</h2><p>".$exc." <span class='readmore'>".$rm."</span></p></div></div>";

		$t['lat'] = get_post_meta( get_the_ID(), '_bp_map_lat', true );

		$t['lng'] = get_post_meta( get_the_ID(), '_bp_map_long', true );

		$a[] = $t;

	endwhile; 

	 wp_reset_postdata();

 	else: 

	endif; 



	$resp = json_encode($a);

	echo $resp;

}







/*******************************ADMIN SECTION *********************************************/



//Actions for Admin

if(is_admin()){

	add_action( 'admin_menu', 'bp_map_create_menu' );

	add_action( 'admin_init', 'register_bp_map_settings' );

	add_action( 'add_meta_boxes', 'bp_map_add_meta_box' );

	add_action( 'save_post', 'bp_map_save_meta_box_data' );

} 





/**

 *  Create Options

**/



function register_bp_map_settings(){

	register_setting('bp_map_group','bp_map_settings','bp_map_validate');

    wp_enqueue_script('bp_map_post_js', plugins_url( '/js/bp_map_post.js', __FILE__ ),array('jquery') );



	

}



/**

 *  Function to contain menu-building code

**/

function bp_map_create_menu() {

	add_options_page( 'BP Post to Map', 'BP Post to Map', 'administrator', 'bp-post-map', 'bp_map_options_do_page' );

}



/**

 *  HTML Output for the options page

**/

function bp_map_options_do_page() {

	if ( !current_user_can( 'manage_options' ) )  {

		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	}

	wp_enqueue_style('bp_map_post_css', plugins_url( '/css/admin.css', __FILE__ ));

	?>

    <div class="bpwrap">

        <h2>BP Post to Google Map Settings</h2>

                    <div id="bpinfo">

            	<h3>Like This Plugin?</h3>

                <p>Please review it in the WordPress Plugin Repository.</p>

                <p>A Pro version of this plugin is coming soon, featuring:</p>

                	<ul>

                    	<li>Lat/Lng from Address on Post pages</li>

                        <li>Custom Map Markers</li>

                        <li>Assign Map Marker by Post</li>

                        <li>Display Maps by Category</li>

                        <li>More customization for the Infowindow (map popup)</li>

                        </ul>

                  <p>Interested? Have feature requests? Be the first to get access by emailing me at <a href="diane@beyond-paper.com">diane@beyond-paper.com</a>. </p>

                <p>Check out my other plugin:

                	<ul>

                    	<li><a href="http://bit.ly/1LDwbeD">BP Expire Category</a> - Removes post from a category on the date you choose.</li>

                    </ul>

                </p>

                <p><a href="http://beyond-paper.com/blog/">My blog</a> features information on WordPress, content marketing, SEO and small business.</p>

                <p>Need a custom plugin or help with your site?  Email me at <a href="diane@beyond-paper.com">diane@beyond-paper.com</a></p>

            </div>



        

        <form id="bpform" method="post" action="options.php">

            <?php settings_fields('bp_map_group'); ?>

            <?php $options = get_option('bp_map_settings'); 


			?>

            <table class="bpform-table">

            	 <tr><td colspan="3"><h3>Map Infowindow (Popup) Options</h3></td></tr>

                <tr valign="top"><th scope="row">Show Title</th>

                    <td><input name="bp_map_settings[title]" type="checkbox" value="1" <?php checked('1', $options['title']); ?> />

                   </td>

                    

                </tr>

                <tr valign="top"><th scope="row">Show Featured Image</th>

                    <td><input name="bp_map_settings[featured]" type="checkbox" value="1" <?php checked('1', $options['featured']); ?> />

                     </td>

                   

                </tr>

                <tr valign="top"><th scope="row">Show Image on Right</th>

                    <td><input name="bp_map_settings[class]" type="checkbox" value="1" <?php checked('1', $options['class']); ?> /></td>

                </tr>

                <tr valign="top"><th scope="row">Show Excerpt</th>

                    <td><input name="bp_map_settings[excerpt]" type="checkbox" value="1" <?php checked('1', $options['excerpt']); ?> />

                    </td>

                    

                </tr>

                <tr valign="top"><th scope="row">Show Read More link</th>

                    <td><input name="bp_map_settings[readmore]" type="checkbox" value="1" <?php checked('1', $options['readmore']); ?> />

                    </td>

                    

                </tr>

                <tr><td colspan="2"><h3>Default Map Center and Zoom</h3></td></tr>

                <tr><td colspan="2"><p>Need to get a lat/lng?  I like <a href="http://www.latlong.net/">LatLong.net</a>.</p></td></tr>

                <tr valign="top"><th scope="row">Latitude</th>

                    <td><input name="bp_map_settings[centerlat]" type="text" value="<?php if(!isset($options['centerlat'])){echo '0';}else{echo $options['centerlat'];} ?>"  /></td>

                </tr>

                <tr valign="top"><th scope="row">Longitude</th>

                    <td><input name="bp_map_settings[centerlng]" type="text" value="<?php if(!isset($options['centerlng'])){echo '0';}else{echo $options['centerlng'];}  ?>"  /></td>

                </tr>

                <tr valign="top"><th scope="row">Zoom Level<br />(0=Whole Earth, 19=Building Level)</th>

                    <td><input name="bp_map_settings[zoom]" type="text" min="0" max="19" value="<?php if(isset($options['zoom'])){echo $options['zoom'];}else{echo '1';}  ?>"  /></td>

                </tr>

            </table>

            <p class="submit">

            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />

            </p>

        </form>

        <p><strong>Shortcode:</strong>  <pre>[bpmap]</pre> Map is responsive and will fit to the container it is put in.</p>

    </div>

    <?php 

}



/**

 *  Sanitize input

**/

 

// Sanitize and validate input. Accepts an array, return a sanitized array.

function bp_map_validate($input) {
	//print_r($input);
    // Our first value is either 0 or 1
	if(isset($input['title'])){
   	 $input['title'] = ( $input['title'] == 1 ? 1 : 0 );
	}else{$input['title'] = 0;}

	if(isset($input['featured'])){
    $input['featured'] = ( $input['featured'] == 1 ? 1 : 0 );
	}else{$input['featured'] = 0;}

	if(isset($input['class'])){
    $input['class'] = ( $input['class'] == 1 ? 1 : 0 );
	}else{$input['class'] = 0;}

	if(isset($input['excerpt'])){
    $input['excerpt'] = ( $input['excerpt'] == 1 ? 1 : 0 );
	}else{$input['excerpt'] = 0;}

	if(isset($input['readmore'])){
    $input['readmore'] = ( $input['readmore'] == 1 ? 1 : 0 );
	}else{$input['readmore'] = 0;}

	if(!preg_match('([-+]?\d{1,2}([.]\d+))', $input['centerlat'])) {

		$input['centerlat'] = '0.351560';

	}else{

		$input['centerlat'] = sanitize_text_field($input['centerlat']);

	}

	if(!preg_match('([-+]?\d{1,3}([.]\d+))', $input['centerlng'])) {

		$input['centerlng'] = '1.23469';

	}else{

		$input['centerlng'] = sanitize_text_field($input['centerlng']);

	}



	$input['zoom'] = intval($input['zoom']*1);

	if($input['zoom']>19){

		$input['zoom'] = '1';

	}else{

		$input['zoom'] = sanitize_text_field($input['zoom']);

	}





    return $input;

}



/**

 * All of the below puts a metabox on the Post page,

 * populates it if there is data, and saves the new

 * data when the Post is saved. 

**/



/**

 * Adds a box to the main column on the Post and Page edit screens.

**/

function bp_map_add_meta_box() {

	$screens = array( 'post' );

	foreach ( $screens as $screen ) {

		add_meta_box(

			'bp_map_sectionid',

			__( 'BP Post to Map', 'bp_map_textdomain' ),

			'bp_map_meta_box_callback',

			$screen

		);

	}

}



/**

 * Prints the box content.

 * 

 * @param WP_Post $post The object for the current post/page.

**/

function bp_map_meta_box_callback( $post ) {



	// Add a nonce field so we can check for it later.

	wp_nonce_field( 'bp_map_meta_box', 'bp_map_meta_box_nonce' );



	/*

	 * Use get_post_meta() to retrieve an existing value

	 * from the database and use the value for the form.

	**/

	$show = get_post_meta( $post->ID, '_bp_map_show', true );

	$lat = get_post_meta( $post->ID, '_bp_map_lat', true );

	$long = get_post_meta( $post->ID, '_bp_map_long', true );

	//$icon = get_post_meta( $post->ID, '_bp_map_icon', true ); Pro Feature

	$show = ( $show == 1 ? 1 : 0 );

	if(!preg_match('([-+]?\d{1,2}([.]\d+))', $lat)) {

		$lat = 0;

	}

	if(!preg_match('([-+]?\d{1,3}([.]\d+))', $long) ){

		$long = 0;

	}



	

	$show==1 ? $checked = 'checked="checked"':$checked = '';

	echo '<p><label for="bp_map_show">';

	_e( 'Add Post to Map', 'bp_map_textdomain' );

	echo '</label> ';

	echo '<input type="checkbox" id="bp_map_show" name="bp_map_show" value="1" size="25" '.$checked.' /></p>';



	echo '<div id="bp_map_post_opts" style="display:none;">';

	echo'<p><a href="http://www.latlong.net/">LatLong.net</a></p>';

	echo '<p><label for="bp_map_lat">';

	_e( 'Latitude:', 'bp_map_textdomain' );

	echo '</label> ';

	echo '<input type="text" id="bp_map_lat" name="bp_map_lat" value="' . esc_attr( $lat ) . '" size="25" /> ';

	

	echo '<label for="bp_map_long">';

	_e( 'Longitude:', 'bp_map_textdomain' );

	echo '</label> ';

	echo '<input type="text" id="bp_map_long" name="bp_map_long" value="' . esc_attr( $long ) . '" size="25" /></p></div>';

/**

	echo '<p><label for="bp_map_icon">';

	_e( 'Icon', 'bp_map_textdomain' );

	echo '</label> ';

	echo '<input type="text" id="bp_map_icon" name="bp_map_icon" value="' . esc_attr( $icon ) . '" size="25" /></p>';

	Pro Feature

	**/

}



/**

 * When the post is saved, saves our custom data.

 *

 * @param int $post_id The ID of the post being saved.

**/

function bp_map_save_meta_box_data( $post_id ) {

	/*

	 * We need to verify this came from our screen and with proper authorization,

	 * because the save_post action can be triggered at other times.

	**/

	// Check if our nonce is set.

	if ( ! isset( $_POST['bp_map_meta_box_nonce'] ) ) {

		return;

	}

	// Verify that the nonce is valid.

	if ( ! wp_verify_nonce( $_POST['bp_map_meta_box_nonce'], 'bp_map_meta_box' ) ) {

		return;

	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

		return;

	}

	// Check the user's permissions.

		if ( ! current_user_can( 'edit_post', $post_id ) ) {

			return;

		}

	/* OK, it's safe for us to save the data now.**/

	

	// Make sure that it is set.

	if ( ! isset( $_POST['bp_map_lat'] ) || ! isset( $_POST['bp_map_long'] ) ) {

		return;

	}

	// Sanitize user input.

	$show = ( $_POST['bp_map_show'] == 1 ? 1 : 0 );

	if(!preg_match('([-+]?\d{1,2}([.]\d+))', $_POST['bp_map_lat'])) {

		$lat = 0;

	}else{

		$lat = sanitize_text_field($_POST['bp_map_lat']);	

	}

	if(!preg_match('([-+]?\d{1,3}([.]\d+))', $_POST['bp_map_long'])) {

		$long = 0;;

	}else{

		$long = sanitize_text_field($_POST['bp_map_long']);	

	}

	//$icon = sanitize_text_field( $_POST['bp_map_icon'] );  Pro Feature



	// Update the meta field in the database.

	update_post_meta( $post_id, '_bp_map_lat', $lat );

	update_post_meta( $post_id, '_bp_map_long', $long );

	update_post_meta( $post_id, '_bp_map_show', $show );

	//update_post_meta( $post_id, '_bp_map_icon', $icon );   Pro Feature

}

