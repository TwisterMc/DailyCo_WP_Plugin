<?php
/**
 * Plugin Name:     Daily Co
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Integrate Daily.co meetings into a website. Created for a specific use case.
 * Author:          Thomas McMahon
 * Author URI:      https://www.twistermc.com/
 * Text Domain:     daily_co
 * Domain Path:     /languages
 * Version:         0.2.0
 *
 * @package         Daily_Co
 */


/**
 * Enqueue scripts and styles
 */
function daily_co_scripts() {
	$localize = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'apikey'  => get_option( 'dailyco_api_key' ),
	);

	wp_enqueue_style( 'styles-daily-co', plugins_url( 'assets/style.css', __FILE__ ) );
	wp_enqueue_script( 'script-daily-co', plugins_url( 'assets/scripts.js', __FILE__ ), array(), '1.0.0', true );
	wp_enqueue_script( 'script-daily-co-source', 'https://unpkg.com/@daily-co/daily-js', array(), '1.0.0', true );
	wp_localize_script( 'script-daily-co', 'daily_co_script', $localize );
}
add_action( 'wp_enqueue_scripts', 'daily_co_scripts' );

/**
 * Shortcode [dailyco]
 */
function daily_co_shortcode_func() {
	return dailyco_render_markup();
}

add_shortcode( 'dailyco', 'daily_co_shortcode_func' );

function dailyco_render_markup() {
	if ( ! is_admin() && is_user_logged_in() ) {
		$dailyco_content  = '<div class="dailyco_wrapper">';
		$dailyco_content .= '<h3 class="dailyco_header">' . get_option( 'dailyco_heading_text' ) . '</h3>';
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
		$dailyco_content .= '<button type="submit" id="createRoom">' . get_option( 'dailyco_button_text' ) . '</button>';
		$dailyco_content .= '</div>';
		$dailyco_content .= '</div>';
		$dailyco_content .= '<div class="df_row">';
		$dailyco_content .= '<div class="df_column">';
		$dailyco_content .= '</div>';
		$dailyco_content .= '<div class="df_double-column">';
		$dailyco_content .= '<div>' . get_option( 'dailyco_sub_text' ) . '</div>';
		$dailyco_content .= '</div>';
		$dailyco_content .= '</form>';
		$dailyco_content .= '</div>';

		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			$dailyco_content .= '<div class="dailyco_admin">';
			$dailyco_content .= '<h4>Debug / Admin Only Section &#x27A1; Rooms:</h4>';
			$dailyco_content .= '<div id="rooms" class="rooms"></div>';
			$dailyco_content .= '</div>';
		}
	} else {
		$dailyco_content = '<strong>' . esc_html__( 'The chat feature is not available unless logged in.', 'daily_co' ) . '</strong>';
	}

	return $dailyco_content;

}

/**
 * Connecting PHP and JS and sending the emails
 */
add_action( 'wp_ajax_dailyco_email', 'dailyco_email' );
add_action( 'wp_ajax_nopriv_dailyco_email', 'dailyco_email' );

// TODO: Setup from and email details
function dailyco_email() {
	$to        = $_POST['email'];
	$from      = 'no-reply@olaf.local';
	$from_name = 'Me Myself';
	$subject   = 'Chat Session Request - ' . get_bloginfo( 'name' );
	$body      = dailyco_email_message( $_POST['name'], $_POST['link'] );
	$headers   = array( 'Content-Type: text/html; charset=UTF-8' );
	$headers[] = 'From: ' . $from_name . ' <' . $from . '>';

	wp_mail( $to, $subject, $body, $headers );
}

/**
 * Setup the email body
 */
function dailyco_email_message( $name, $link ) {
	$message  = '<p>Hello ' . $name . ',</p>';
	$message .= '<p>' . dailyco_get_current_user() . ' would like to have a video conference with you. Please click the following link to join.</p>';
	$message .= '<p><a href="' . $link . '">' . $link . '</a></p>';
	$message .= '<p>This message was sent via ' . get_bloginfo( 'name' ) . ' ' . get_bloginfo( 'url' ) . '</p>';

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
	add_option( 'dailyco_option_name', 'This is my option value.' );
	register_setting( 'dailyco_options_group', 'dailyco_api_key', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_heading_text', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_button_text', 'dailyco_callback' );
	register_setting( 'dailyco_options_group', 'dailyco_sub_text', 'dailyco_callback' );
}
add_action( 'admin_init', 'dailyco_register_settings' );

function dailyco_register_options_page() {
	add_options_page( 'DailyCo Settings', 'DailyCo', 'manage_options', 'dailyco', 'dailyco_options_page' );
}
add_action( 'admin_menu', 'dailyco_register_options_page' );

function dailyco_options_page()
{
?>
	<div class="wrap">
		<h1><?php esc_html_e( 'DailyCo Options', 'daily_co' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'dailyco_options_group' );
			$api_key      = get_option( 'dailyco_api_key' );
			$heading_text = get_option( 'dailyco_heading_text' );
			$button_text  = get_option( 'dailyco_button_text' );
			$sub_text     = get_option( 'dailyco_sub_text' );

			$message = dailyco_crypt($api_key, 'd');
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
			?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dailyco_api_key"><?php esc_html_e( 'Daily.co API Key', 'daily_co' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="dailyco_api_key" name="dailyco_api_key" value="<?php echo ! empty( $api_key ) ? $api_key : ''; ?>" />
						<p class="description"><?php _e( 'This can be found on your <a href="https://dashboard.daily.co/" target="_blank">Daily.co dashboard</a> under the Developers tab.', 'daily_co' ); ?></p>
					</td>
				</tr>
			</table>
			<table class="form-table" role="presentation">
				<h2 class="title"><?php esc_html_e( 'Text Customization', 'daily_co' ); ?></h2>
				<tr>
					<th scope="row"><label for="dailyco_heading_text"><?php esc_html_e( 'Heading', 'daily_co' ); ?></label></th>
					<td><input type="text" class="regular-text" id="dailyco_heading_text" name="dailyco_heading_text" value="<?php echo ! empty( $heading_text ) ? $heading_text : 'Who would you like to meet with?'; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dailyco_button_text"><?php esc_html_e( 'Button Text', 'daily_co' ); ?></label></th>
					<td><input type="text" class="regular-text" id="dailyco_button_text" name="dailyco_button_text" value="<?php echo ! empty( $button_text ) ? $button_text : 'Invite & Join Meeting'; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dailyco_sub_text"><?php esc_html_e( 'Sub Text', 'daily_co' ); ?></label></th>
					<td><input type="text" class="regular-text" id="dailyco_sub_text" name="dailyco_sub_text" value="<?php echo ! empty( $sub_text ) ? $sub_text : 'All rooms expire within 24 hours.'; ?>" /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

/**
 * Encrypt and decrypt
 * TODO: // Integrate
 *
 * @author Nazmul Ahsan <n.mukto@gmail.com>
 * @link http://nazmulahsan.me/simple-two-way-function-encrypt-decrypt-string/
 *
 * @param string $string string to be encrypted/decrypted
 * @param string $action what to do with this? e for encrypt, d for decrypt
 */
function dailyco_crypt( $string, $action = 'e' ) {

	// you may change these values to your own
	$secret_key = 'SCvThGINWy&C';
	$secret_iv  = '9!^c7J6owOJ#';

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
