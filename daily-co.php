<?php
/**
 * Plugin Name:     Daily Co
 * Plugin URI:      https://github.com/TwisterMc/DailyCo_WP_Plugin/tree/develop
 * Description:     Integrate Daily.co meetings into a website. Created for a specific use case.
 * Author:          Thomas McMahon
 * Author URI:      https://www.twistermc.com/
 * Text Domain:     daily_co
 * Domain Path:     /languages
 * Version:         0.3.0
 *
 * @package         Daily_Co
 */

/**
 * Activation hook to setup our secrets when the plugin is activated
 */
register_activation_hook( __FILE__, '_plugin_activation' );

function _plugin_activation() {
	if ( ! get_option( 'dailyco_secret_key' ) ) {
		add_option( 'dailyco_secret_key', wp_generate_password( 8 ) );
	}

	if ( ! get_option( 'dailyco_secret_iv' ) ) {
		add_option( 'dailyco_secret_iv', wp_generate_password( 8 ) );
	}
}

/**
 * Add Settings Link to Plugins Page
 */
add_filter( 'plugin_action_links_daily-co/daily-co.php', 'dc_settings_link' );
function dc_settings_link( $links ) {
	// Build and escape the URL.
	$url = esc_url(
		add_query_arg(
			'page',
			'dailyco',
			get_admin_url() . 'admin.php'
		)
	);
	// Create the link.
	$settings_link = "<a href='$url'>" . __( 'Settings', 'daily_co' ) . '</a>';
	// Adds the link to the end of the array.
	array_push(
		$links,
		$settings_link
	);
	return $links;
}

/**
 * Enqueue scripts and styles
 */
function daily_co_scripts() {
	$localize = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'apikey'  => dailyco_crypt( get_option( 'dailyco_api_key' ), 'd' ),
	);

	wp_enqueue_style( 'styles-daily-co', plugins_url( 'assets/style.css', __FILE__ ) );
	wp_enqueue_script( 'script-daily-co', plugins_url( 'assets/scripts.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script( 'script-daily-co-source', 'https://unpkg.com/@daily-co/daily-js', array(), '1.0.0', true );
	wp_localize_script( 'script-daily-co', 'daily_co_script', $localize );
}
add_action( 'wp_enqueue_scripts', 'daily_co_scripts' );

/**
 * Add iFrame wrap to the footer.
 * Doing this so we don't load an iFrame on every page unless we need it.
 */
function dailyco_add_iframe_wrapper() {
	echo '<div id="dailyco_call_iframe_wrap"></div>';
}
add_action( 'wp_footer', 'dailyco_add_iframe_wrapper' );

/**
 * Shortcode [dailyco]
 */
function daily_co_shortcode_func() {
	return dailyco_render_markup();
}

add_shortcode( 'dailyco', 'daily_co_shortcode_func' );

// @todo: Re-work this so it's not all in PHP.
function dailyco_render_markup() {
	if ( ! is_admin() && is_user_logged_in() ) {
		if ( ! get_option( 'dailyco_api_key' ) ) {
			$settings_url     = site_url( '/wp-admin/options-general.php?page=dailyco' );
			$dailyco_content  = '<div class="dailyco_notice">';
			$dailyco_content .= __( 'There doesn\'t appear to be an API key in the settings. ', 'daily_co' );
			$dailyco_content .= '<a href="' . $settings_url . '">' . __( 'Edit Settings.', 'daily_co' ) . '</a>';
			$dailyco_content .= '</div>';
		} else {
			$dailyco_content  = '<div class="dailyco_wrapper">';
			$dailyco_content .= '<h3 class="dailyco_header">' . get_option( 'dailyco_heading_text', 'Who would you like to meet with?' ) . '</h3>';
			$dailyco_content .= '<form id="dailycoForm" name="dailycoForm" class="dailycoForm">';
			$dailyco_content .= '<div class="df_row">';
			$dailyco_content .= '<div class="df_column">';
			$dailyco_content .= '<label for="name">' . esc_html__( 'Name', 'daily_co' ) . '</label>';
			$dailyco_content .= '</div>';
			$dailyco_content .= '<div class="df_double-column">';
			$dailyco_content .= '<input type="text" name="name" required="required" />';
			$dailyco_content .= '</div>';
			$dailyco_content .= '</div>';
			$dailyco_content .= '<div class="df_row">';
			$dailyco_content .= '<div class="df_column">';
			$dailyco_content .= '<label for="email">' . esc_html__( 'Email', 'daily_co' ) . '</label>';
			$dailyco_content .= '</div>';
			$dailyco_content .= '<div class="df_double-column">';
			$dailyco_content .= '<input type="email" name="email" required="required" />';
			$dailyco_content .= '</div>';
			$dailyco_content .= '</div>';
			$dailyco_content .= '<div class="df_row">';
			$dailyco_content .= '<div class="df_column">';
			$dailyco_content .= '</div>';
			$dailyco_content .= '<div class="df_double-column">';
			$dailyco_content .= '<button type="submit" id="createRoom">' . get_option( 'dailyco_button_text', 'Who would you like to meet with?' ) . '</button>';
			$dailyco_content .= '</div>';
			$dailyco_content .= '</div>';
			$dailyco_content .= '<div class="df_row">';
			$dailyco_content .= '<div class="df_column">';
			$dailyco_content .= '</div>';
			$dailyco_content .= '<div class="df_double-column">';
			$dailyco_content .= '<div>' . get_option( 'dailyco_sub_text', 'All rooms expire within 24 hours.' ) . '</div>';
			$dailyco_content .= '</div>';
			$dailyco_content .= '</form>';
			$dailyco_content .= '</div>';
		}
	} else {
		$dailyco_content  = '<div class="dailyco_notice">' . __( 'The video chat feature is not available unless ', 'daily_co' );
		$dailyco_content .= '<a href="' . esc_url( wp_login_url() ) . '">logged in</a>.</div><div></div>';
	}

	return $dailyco_content;

}

/**
 * Connecting PHP and JS and sending the emails
 */
add_action( 'wp_ajax_dailyco_email', 'dailyco_email' );
add_action( 'wp_ajax_nopriv_dailyco_email', 'dailyco_email' );

function dailyco_email() {
	$to        = $_POST['email'];
	$from      = get_from_email();
	$from_name = get_option( 'dailyco_email_from', get_bloginfo( 'name' ) );
	$subject   = get_option( 'dailyco_email_subject', 'Video Meeting Request' );
	$body      = dailyco_email_message( $_POST['name'], $_POST['link'] );
	$headers   = array( 'Content-Type: text/html; charset=UTF-8' );
	$headers[] = 'From: ' . $from_name . ' <' . $from . '>';

	wp_mail( $to, $subject, $body, $headers );
}

/**
 * Generate From Email Address
 */
function get_from_email() {
	$from_email = 'no-reply@' . $_SERVER['SERVER_NAME'];
	return $from_email;
}

/**
 * Default Email Body
 */
function dailyco_email_message_default() {
	$message  = 'Hello [invitee],';
	$message .= "\n\n";
	$message .= '[requester] would like to have a video conference with you.';
	$message .= "\n\n";
	$message .= 'Please click the following link to join.';
	$message .= "\n\n";
	$message .= '[video_link]';
	$message .= "\n\n";
	$message .= 'Thanks';
	$message .= "\n\n";
	$message .= 'This message was sent via [site_info].';

	return $message;
}

/**
 * Setup the email body
 */
function dailyco_email_message( $name, $link ) {
	$message = wp_strip_all_tags( get_option( 'dailyco_email_template', dailyco_email_message_default() ) );
	$message = wpautop( $message );
	$message = str_replace( '[invitee]', $name, $message );
	$message = str_replace( '[requester]', dailyco_get_current_user(), $message );
	$message = str_replace( '[video_link]', '<a href="' . $link . '">' . $link . '</a>', $message );
	$message = str_replace( '[site_info]', get_bloginfo( 'name' ) . ' ' . get_bloginfo( 'url' ), $message );
	return $message;
}

/**
 * Get Current User
 */
function dailyco_get_current_user() {
	$current_user            = wp_get_current_user();
	$current_user_first_name = esc_html( $current_user->user_firstname );
	$current_user_last_name  = esc_html( $current_user->user_lastname );

	return $current_user_first_name . ' ' . $current_user_last_name;
}

/**
 * WordPress Widget
 */
class Dailyco_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'foo_widget', // Base ID
			'DailyCo Video Chat Widget', // Name
			array( 'description' => __( 'Embeds a video chat form.', 'daily_co' ) ) // Args
		);
	}

	public function widget( $args, $instance ) {
		echo dailyco_render_markup();
	}

	public function form( $instance ) {
		echo '<p>';
		echo __( 'You can configure options <a href="options-general.php?page=dailyco">here.</a>', 'daily_co' );
		echo '</p>';
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
	}
}

add_action( 'widgets_init', 'wpdocs_register_widgets' );

function wpdocs_register_widgets() {
	register_widget( 'Dailyco_Widget' );
}

/**
 * Settings Page in WP Admin
 */
function dailyco_register_settings() {
	register_setting( 'dailyco_options_group', 'dailyco_api_key', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_heading_text', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_button_text', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_sub_text', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_secret_key', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_secret_iv', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_email_from', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_email_template', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_email_subject', 'dailyco_callback' );
}
add_action( 'admin_init', 'dailyco_register_settings' );

function dailyco_register_options_page() {
	global $dailyco_settings_page;
	$dailyco_settings_page = add_options_page( 'DailyCo Settings', 'DailyCo', 'manage_options', 'dailyco', 'dailyco_options_page' );
}
add_action( 'admin_menu', 'dailyco_register_options_page' );

// Load Plugin JS for Admin
function pw_load_scripts( $hook ) {
	$localize = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'apikey'  => dailyco_crypt( get_option( 'dailyco_api_key' ), 'd' ),
	);

	global $dailyco_settings_page;
	if ( $hook !== $dailyco_settings_page ) {
		return;
	}
	wp_enqueue_script( 'script-daily-co', plugins_url( 'assets/scripts.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script( 'script-daily-co-source', 'https://unpkg.com/@daily-co/daily-js', array(), '1.0.0', true );
	wp_localize_script( 'script-daily-co', 'daily_co_script', $localize );
}
add_action( 'admin_enqueue_scripts', 'pw_load_scripts' );

// hook into the update function to encode our api key
function dailyco_update_api_field( $new_value, $old_value ) {

	// check if encoded first.
	if ( dailyco_crypt( $old_value, 'd' ) === $new_value ) {
		// already encoded
		return $old_value;
	} else {
		$new_value = dailyco_crypt( $new_value, 'e' );
		return $new_value;
	}
}

function dailyco_init() {
	add_filter( 'pre_update_option_dailyco_api_key', 'dailyco_update_api_field', 10, 2 );
}

add_action( 'init', 'dailyco_init' );


// create the options page
function dailyco_options_page() {
?>
	<div class="wrap">
		<h1><?php esc_html_e( 'DailyCo Options', 'daily_co' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'dailyco_options_group' );
			$api_key   = get_option( 'dailyco_api_key' );
			$api_d_key = dailyco_crypt( $api_key, 'd' );

			$heading_text = get_option( 'dailyco_heading_text', 'Who would you like to meet with?' );
			$button_text  = get_option( 'dailyco_button_text', 'Invite & Join Meeting' );
			$sub_text     = get_option( 'dailyco_sub_text', 'All rooms expire within 24 hours.' );

			$secret_key = get_option( 'dailyco_secret_key' );
			$secret_iv  = get_option( 'dailyco_secret_iv' );

			$email_from     = get_option( 'dailyco_email_from', get_bloginfo( 'name' ) );
			$email_subject  = get_option( 'dailyco_email_subject', 'Video Meeting Request' );
			$email_template = get_option( 'dailyco_email_template', dailyco_email_message_default() );
			?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dailyco_api_key"><?php esc_html_e( 'Daily.co API Key', 'daily_co' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="dailyco_api_key" name="dailyco_api_key" value="<?php echo ! empty( $api_d_key ) ? $api_d_key : ''; ?>" />
						<p class="description"><?php _e( 'This can be found on your <a href="https://dashboard.daily.co/" target="_blank">Daily.co dashboard</a> under the Developers tab.', 'daily_co' ); ?></p>
					</td>
				</tr>
			</table>
			<table class="form-table" role="presentation">
				<h2 class="title"><?php esc_html_e( 'Form Customization', 'daily_co' ); ?></h2>
				<tr>
					<th scope="row"><label for="dailyco_heading_text"><?php esc_html_e( 'Heading', 'daily_co' ); ?></label></th>
					<td><input type="text" class="regular-text" id="dailyco_heading_text" name="dailyco_heading_text" value="<?php echo $heading_text; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dailyco_button_text"><?php esc_html_e( 'Button Text', 'daily_co' ); ?></label></th>
					<td><input type="text" class="regular-text" id="dailyco_button_text" name="dailyco_button_text" value="<?php echo $button_text; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dailyco_sub_text"><?php esc_html_e( 'Sub Text', 'daily_co' ); ?></label></th>
					<td><input type="text" class="regular-text" id="dailyco_sub_text" name="dailyco_sub_text" value="<?php echo $sub_text; ?>" /></td>
				</tr>
			</table>
			<h2 class="title"><?php esc_html_e( 'Email Customization', 'daily_co' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dailyco_email_from"><?php esc_html_e( 'From Name', 'daily_co' ); ?></label></th>
					<td><input type="text" class="regular-text" id="dailyco_email_from" name="dailyco_email_from" value="<?php echo $email_from; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dailyco_email_from_email"><?php esc_html_e( 'From Email', 'daily_co' ); ?></label></th>
					<td>
						<input readonly type="text" class="regular-text" id="dailyco_email_from_email" name="dailyco_email_from_email" value="<?php echo get_from_email(); ?>" />
						<p>Not editable.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dailyco_email_subject"><?php esc_html_e( 'Subject', 'daily_co' ); ?></label></th>
					<td><input type="text" class="regular-text" id="dailyco_email_subject" name="dailyco_email_subject" value="<?php echo $email_subject; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dailyco_email_template"><?php esc_html_e( 'Email Template', 'daily_co' ); ?></label></th>
					<td>
						<textarea type="text" class="regular-text" id="dailyco_email_template" name="dailyco_email_template" style="height: 300px;"><?php echo $email_template; ?></textarea>
						<p class="description">Use merge tags to personalize the email.</p>
						<p class="description">Valid merge tags: [invitee], [requester], [video_link], [site_info]</p>
						<p class="description">No HTML is allowed.</p>
					</td>
				</tr>
			</table>
			<h2 class="title"><?php esc_html_e( 'Existing Rooms', 'daily_co' ); ?></h2>
			<div id="rooms" class="rooms">No Rooms</div>
			<h2 class="title"><?php esc_html_e( 'Secrets for Encryption', 'daily_co' ); ?></h2>
			<p class="description">These keys are used to encrypt the API key in the database. You are not able to change these, but you have the keys if you need them for any reason.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dailyco_secret_key"><?php esc_html_e( 'Secret Key', 'daily_co' ); ?></label></th>
					<td><input type="text" readonly class="regular-text" id="dailyco_secret_key" name="dailyco_secret_key" value="<?php echo $secret_key; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dailyco_secret_iv"><?php esc_html_e( 'Secret IV', 'daily_co' ); ?></label></th>
					<td><input type="text" readonly class="regular-text" id="dailyco_secret_iv" name="dailyco_secret_iv" value="<?php echo $secret_iv; ?>" /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Encrypt and decrypt
 *
 * @author Nazmul Ahsan <n.mukto@gmail.com>
 * @link http://nazmulahsan.me/simple-two-way-function-encrypt-decrypt-string/
 *
 * @param string $string string to be encrypted/decrypted
 * @param string $action what to do with this? e for encrypt, d for decrypt
 */
function dailyco_crypt( $string, $action = 'e' ) {

	// you may change these values to your own
	$secret_key = get_option( 'dailyco_secret_key' );
	$secret_iv  = get_option( 'dailyco_secret_iv' );

	$output         = false;
	$encrypt_method = 'AES-256-CBC';
	$key            = hash( 'sha256', $secret_key );
	$iv             = substr( hash( 'sha256', $secret_iv ), 0, 16 );

	if ( 'e' === $action ) {
		$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
	} elseif ( 'd' === $action ) {
		$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
	}

	return $output;
}
