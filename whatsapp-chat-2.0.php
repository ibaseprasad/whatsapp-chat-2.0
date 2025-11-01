<?php
/**
 * Plugin Name:       WhatsApp Chat 2.0
 * Plugin URI:        https://example.com/whatsapp-chat-2-0
 * Description:       Advanced floating WhatsApp chat widget with multi-agent routing, scheduling, and customization options.
 * Version:           2.0.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whatsapp-chat-2-0
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WAC_PLUGIN_VERSION', '2.0.0' );
define( 'WAC_PLUGIN_FILE', __FILE__ );
define( 'WAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WAC_PLUGIN_DIR . 'includes/class-whatsapp-chat.php';

WhatsApp_Chat_Plugin::instance();
