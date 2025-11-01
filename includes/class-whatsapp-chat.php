<?php
/**
 * Main plugin class for WhatsApp Chat 2.0.
 *
 * @package WhatsApp_Chat_2_0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WhatsApp_Chat_Plugin' ) ) {
    class WhatsApp_Chat_Plugin {

        /**
         * Option key used to store the plugin settings.
         *
         * @var string
         */
        protected $option_name = 'whatsapp_chat_options';

        /**
         * Cached options.
         *
         * @var array
         */
        protected $options = [];

        /**
         * Singleton instance.
         *
         * @var self|null
         */
        protected static $instance = null;

        /**
         * Get singleton instance.
         *
         * @return self
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor.
         */
        protected function __construct() {
            $this->options = $this->get_options();
            $this->hooks();
        }

        /**
         * Register WordPress hooks.
         */
        protected function hooks() {
            add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
            add_action( 'wp_footer', [ $this, 'render_chat_widget' ] );
        }

        /**
         * Retrieve plugin options with defaults.
         *
         * @return array
         */
        public function get_options() {
            $defaults = [
                'enable_chat'      => 1,
                'global_phone'     => '',
                'prefill_message'  => __( 'Hello! I would like to know more about your services.', 'whatsapp-chat-2-0' ),
                'welcome_message'  => __( 'How can we help you today?', 'whatsapp-chat-2-0' ),
                'offline_message'  => __( 'We are currently offline. Please leave a message and we will get back to you soon.', 'whatsapp-chat-2-0' ),
                'button_label'     => __( 'Chat with us', 'whatsapp-chat-2-0' ),
                'button_position'  => 'right',
                'button_color'     => '#25D366',
                'button_text_color'=> '#ffffff',
                'show_branding'    => 1,
                'schedule'         => $this->get_default_schedule(),
                'agents'           => [],
                'gdpr_notice'      => '',
                'display_condition'=> 'everywhere',
                'custom_pages'     => [],
            ];

            $options = get_option( $this->option_name, [] );

            return wp_parse_args( $options, $defaults );
        }

        /**
         * Default schedule definition.
         *
         * @return array
         */
        protected function get_default_schedule() {
            $days = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
            $schedule = [];

            foreach ( $days as $day ) {
                $schedule[ $day ] = [
                    'enabled' => in_array( $day, [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ], true ) ? 1 : 0,
                    'start'   => '09:00',
                    'end'     => '17:00',
                ];
            }

            return $schedule;
        }

        /**
         * Register settings page in the admin menu.
         */
        public function register_settings_page() {
            add_options_page(
                __( 'WhatsApp Chat 2.0', 'whatsapp-chat-2-0' ),
                __( 'WhatsApp Chat 2.0', 'whatsapp-chat-2-0' ),
                'manage_options',
                'whatsapp-chat-2-0',
                [ $this, 'render_settings_page' ]
            );
        }

        /**
         * Register settings with the WordPress settings API.
         */
        public function register_settings() {
            register_setting( 'whatsapp_chat_options_group', $this->option_name, [ $this, 'sanitize_options' ] );

            add_settings_section(
                'whatsapp_chat_general',
                __( 'General settings', 'whatsapp-chat-2-0' ),
                '__return_false',
                'whatsapp-chat-2-0'
            );

            add_settings_field(
                'enable_chat',
                __( 'Enable chat widget', 'whatsapp-chat-2-0' ),
                [ $this, 'render_checkbox_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_general',
                [ 'label_for' => 'enable_chat' ]
            );

            add_settings_field(
                'global_phone',
                __( 'Primary phone number', 'whatsapp-chat-2-0' ),
                [ $this, 'render_text_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_general',
                [ 'label_for' => 'global_phone', 'description' => __( 'Enter the WhatsApp phone number in international format without spaces or symbols.', 'whatsapp-chat-2-0' ) ]
            );

            add_settings_field(
                'prefill_message',
                __( 'Default pre-filled message', 'whatsapp-chat-2-0' ),
                [ $this, 'render_textarea_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_general',
                [ 'label_for' => 'prefill_message' ]
            );

            add_settings_field(
                'welcome_message',
                __( 'Welcome message', 'whatsapp-chat-2-0' ),
                [ $this, 'render_text_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_general',
                [ 'label_for' => 'welcome_message' ]
            );

            add_settings_field(
                'offline_message',
                __( 'Offline message', 'whatsapp-chat-2-0' ),
                [ $this, 'render_textarea_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_general',
                [ 'label_for' => 'offline_message' ]
            );

            add_settings_field(
                'gdpr_notice',
                __( 'GDPR notice (optional)', 'whatsapp-chat-2-0' ),
                [ $this, 'render_textarea_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_general',
                [ 'label_for' => 'gdpr_notice', 'description' => __( 'Displayed underneath the agent list to comply with privacy regulations.', 'whatsapp-chat-2-0' ) ]
            );

            add_settings_section(
                'whatsapp_chat_button',
                __( 'Floating button', 'whatsapp-chat-2-0' ),
                '__return_false',
                'whatsapp-chat-2-0'
            );

            add_settings_field(
                'button_label',
                __( 'Button label', 'whatsapp-chat-2-0' ),
                [ $this, 'render_text_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_button',
                [ 'label_for' => 'button_label' ]
            );

            add_settings_field(
                'button_position',
                __( 'Button position', 'whatsapp-chat-2-0' ),
                [ $this, 'render_select_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_button',
                [
                    'label_for'  => 'button_position',
                    'options'    => [
                        'right' => __( 'Bottom right', 'whatsapp-chat-2-0' ),
                        'left'  => __( 'Bottom left', 'whatsapp-chat-2-0' ),
                    ],
                ]
            );

            add_settings_field(
                'button_color',
                __( 'Button background color', 'whatsapp-chat-2-0' ),
                [ $this, 'render_color_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_button',
                [ 'label_for' => 'button_color' ]
            );

            add_settings_field(
                'button_text_color',
                __( 'Button text color', 'whatsapp-chat-2-0' ),
                [ $this, 'render_color_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_button',
                [ 'label_for' => 'button_text_color' ]
            );

            add_settings_field(
                'show_branding',
                __( 'Display WhatsApp icon', 'whatsapp-chat-2-0' ),
                [ $this, 'render_checkbox_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_button',
                [ 'label_for' => 'show_branding' ]
            );

            add_settings_section(
                'whatsapp_chat_schedule',
                __( 'Availability schedule', 'whatsapp-chat-2-0' ),
                '__return_false',
                'whatsapp-chat-2-0'
            );

            add_settings_field(
                'schedule',
                __( 'Business hours', 'whatsapp-chat-2-0' ),
                [ $this, 'render_schedule_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_schedule',
                [ 'label_for' => 'schedule' ]
            );

            add_settings_section(
                'whatsapp_chat_agents',
                __( 'Support agents', 'whatsapp-chat-2-0' ),
                '__return_false',
                'whatsapp-chat-2-0'
            );

            add_settings_field(
                'agents',
                __( 'Agents', 'whatsapp-chat-2-0' ),
                [ $this, 'render_agents_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_agents'
            );

            add_settings_section(
                'whatsapp_chat_display_rules',
                __( 'Display rules', 'whatsapp-chat-2-0' ),
                '__return_false',
                'whatsapp-chat-2-0'
            );

            add_settings_field(
                'display_condition',
                __( 'Display on', 'whatsapp-chat-2-0' ),
                [ $this, 'render_select_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_display_rules',
                [
                    'label_for' => 'display_condition',
                    'options'   => [
                        'everywhere' => __( 'Entire site', 'whatsapp-chat-2-0' ),
                        'homepage'   => __( 'Homepage only', 'whatsapp-chat-2-0' ),
                        'posts'      => __( 'Single posts', 'whatsapp-chat-2-0' ),
                        'pages'      => __( 'All pages', 'whatsapp-chat-2-0' ),
                        'selected'   => __( 'Selected pages', 'whatsapp-chat-2-0' ),
                    ],
                ]
            );

            add_settings_field(
                'custom_pages',
                __( 'Page IDs (comma separated)', 'whatsapp-chat-2-0' ),
                [ $this, 'render_text_field' ],
                'whatsapp-chat-2-0',
                'whatsapp_chat_display_rules',
                [ 'label_for' => 'custom_pages', 'description' => __( 'Used when "Selected pages" is chosen. Provide WordPress page IDs separated by commas.', 'whatsapp-chat-2-0' ) ]
            );
        }

        /**
         * Sanitize settings input before saving.
         *
         * @param array $input Submitted values.
         *
         * @return array
         */
        public function sanitize_options( $input ) {
            $output = $this->get_options();

            $output['enable_chat']       = isset( $input['enable_chat'] ) ? 1 : 0;
            $output['global_phone']      = isset( $input['global_phone'] ) ? preg_replace( '/[^0-9]/', '', $input['global_phone'] ) : '';
            $output['prefill_message']   = isset( $input['prefill_message'] ) ? sanitize_textarea_field( $input['prefill_message'] ) : '';
            $output['welcome_message']   = isset( $input['welcome_message'] ) ? sanitize_text_field( $input['welcome_message'] ) : '';
            $output['offline_message']   = isset( $input['offline_message'] ) ? sanitize_textarea_field( $input['offline_message'] ) : '';
            $output['gdpr_notice']       = isset( $input['gdpr_notice'] ) ? sanitize_textarea_field( $input['gdpr_notice'] ) : '';
            $output['button_label']      = isset( $input['button_label'] ) ? sanitize_text_field( $input['button_label'] ) : '';
            $output['button_position']   = isset( $input['button_position'] ) && in_array( $input['button_position'], [ 'left', 'right' ], true ) ? $input['button_position'] : 'right';
            $button_color = isset( $input['button_color'] ) ? sanitize_hex_color( $input['button_color'] ) : false;
            $text_color   = isset( $input['button_text_color'] ) ? sanitize_hex_color( $input['button_text_color'] ) : false;

            $output['button_color']      = $button_color ? $button_color : '#25D366';
            $output['button_text_color'] = $text_color ? $text_color : '#ffffff';
            $output['show_branding']     = isset( $input['show_branding'] ) ? 1 : 0;
            $output['display_condition'] = isset( $input['display_condition'] ) ? sanitize_key( $input['display_condition'] ) : 'everywhere';

            if ( isset( $input['custom_pages'] ) ) {
                $ids = array_filter( array_map( 'absint', explode( ',', $input['custom_pages'] ) ) );
                $output['custom_pages'] = $ids;
            } else {
                $output['custom_pages'] = [];
            }

            $output['schedule'] = $this->sanitize_schedule( $input );
            $output['agents']   = $this->sanitize_agents( $input );

            $this->options = $output;

            return $output;
        }

        /**
         * Sanitize schedule data.
         *
         * @param array $input Submitted values.
         *
         * @return array
         */
        protected function sanitize_schedule( $input ) {
            $sanitized = $this->get_default_schedule();

            if ( ! isset( $input['schedule'] ) || ! is_array( $input['schedule'] ) ) {
                return $sanitized;
            }

            foreach ( $sanitized as $day => $defaults ) {
                $data = isset( $input['schedule'][ $day ] ) ? $input['schedule'][ $day ] : [];

                $sanitized[ $day ]['enabled'] = isset( $data['enabled'] ) ? 1 : 0;
                $sanitized[ $day ]['start']   = isset( $data['start'] ) ? sanitize_text_field( $data['start'] ) : $defaults['start'];
                $sanitized[ $day ]['end']     = isset( $data['end'] ) ? sanitize_text_field( $data['end'] ) : $defaults['end'];
            }

            return $sanitized;
        }

        /**
         * Sanitize agents data.
         *
         * @param array $input Submitted values.
         *
         * @return array
         */
        protected function sanitize_agents( $input ) {
            if ( ! isset( $input['agents'] ) || ! is_array( $input['agents'] ) ) {
                return [];
            }

            $agents = [];

            foreach ( $input['agents'] as $agent ) {
                if ( empty( $agent['name'] ) && empty( $agent['phone'] ) ) {
                    continue;
                }

                $name  = isset( $agent['name'] ) ? sanitize_text_field( $agent['name'] ) : '';
                $phone = isset( $agent['phone'] ) ? preg_replace( '/[^0-9]/', '', $agent['phone'] ) : '';

                if ( empty( $name ) || empty( $phone ) ) {
                    continue;
                }

                $agents[] = [
                    'name'     => $name,
                    'phone'    => $phone,
                    'title'    => isset( $agent['title'] ) ? sanitize_text_field( $agent['title'] ) : '',
                    'message'  => isset( $agent['message'] ) ? sanitize_text_field( $agent['message'] ) : '',
                    'priority' => isset( $agent['priority'] ) ? absint( $agent['priority'] ) : 0,
                ];
            }

            usort(
                $agents,
                function ( $a, $b ) {
                    return $a['priority'] <=> $b['priority'];
                }
            );

            return $agents;
        }

        /**
         * Enqueue admin assets.
         */
        public function enqueue_admin_assets( $hook ) {
            if ( 'settings_page_whatsapp-chat-2-0' !== $hook ) {
                return;
            }

            wp_enqueue_style( 'wac-admin', WAC_PLUGIN_URL . 'assets/css/admin.css', [], WAC_PLUGIN_VERSION );
            wp_enqueue_script( 'wac-admin', WAC_PLUGIN_URL . 'assets/js/admin.js', [ 'wp-util' ], WAC_PLUGIN_VERSION, true );

            wp_localize_script(
                'wac-admin',
                'WhatsAppChatAdmin',
                [
                    'newAgentTemplate' => $this->get_agent_template(),
                ]
            );
        }

        /**
         * Returns HTML template for a new agent row.
         *
         * @return string
         */
        protected function get_agent_template() {
            ob_start();
            $this->render_agent_row( [
                'name'     => '',
                'phone'    => '',
                'title'    => '',
                'message'  => '',
                'priority' => count( $this->options['agents'] ),
            ], '__index__' );

            return ob_get_clean();
        }

        /**
         * Enqueue frontend assets.
         */
        public function enqueue_frontend_assets() {
            if ( ! $this->should_display_widget() ) {
                return;
            }

            wp_enqueue_style( 'wac-frontend', WAC_PLUGIN_URL . 'assets/css/frontend.css', [], WAC_PLUGIN_VERSION );
            wp_enqueue_script( 'wac-frontend', WAC_PLUGIN_URL . 'assets/js/frontend.js', [], WAC_PLUGIN_VERSION, true );

            wp_localize_script(
                'wac-frontend',
                'WhatsAppChatConfig',
                [
                    'isAvailable'     => $this->is_available(),
                    'buttonLabel'     => $this->options['button_label'],
                    'offlineMessage'  => $this->options['offline_message'],
                    'welcomeMessage'  => $this->options['welcome_message'],
                    'gdprNotice'      => $this->options['gdpr_notice'],
                    'buttonColor'     => $this->options['button_color'],
                    'buttonTextColor' => $this->options['button_text_color'],
                ]
            );
        }

        /**
         * Render the chat widget markup in the footer.
         */
        public function render_chat_widget() {
            if ( ! $this->should_display_widget() ) {
                return;
            }

            $is_available = $this->is_available();
            $agents       = $this->get_agents();
            $button_class = 'right' === $this->options['button_position'] ? 'wac-button-right' : 'wac-button-left';
            ?>
            <div class="wac-floating-wrapper <?php echo esc_attr( $button_class ); ?>" data-available="<?php echo esc_attr( $is_available ? '1' : '0' ); ?>">
                <button class="wac-floating-button" style="background-color: <?php echo esc_attr( $this->options['button_color'] ); ?>; color: <?php echo esc_attr( $this->options['button_text_color'] ); ?>">
                    <?php if ( $this->options['show_branding'] ) : ?>
                        <span class="wac-icon" aria-hidden="true"></span>
                    <?php endif; ?>
                    <span class="wac-label"><?php echo esc_html( $this->options['button_label'] ); ?></span>
                </button>
                <div class="wac-chat-window" role="dialog" aria-live="polite" aria-hidden="true">
                    <div class="wac-chat-header">
                        <div>
                            <h2 class="wac-title"><?php echo esc_html( $this->options['button_label'] ); ?></h2>
                            <p class="wac-subtitle"><?php echo esc_html( $is_available ? $this->options['welcome_message'] : $this->options['offline_message'] ); ?></p>
                        </div>
                        <button type="button" class="wac-close" aria-label="<?php esc_attr_e( 'Close chat', 'whatsapp-chat-2-0' ); ?>">&times;</button>
                    </div>
                    <div class="wac-chat-body">
                        <?php if ( $is_available ) : ?>
                            <?php if ( ! empty( $agents ) ) : ?>
                                <ul class="wac-agents">
                                    <?php foreach ( $agents as $index => $agent ) : ?>
                                        <li class="wac-agent" data-agent-index="<?php echo esc_attr( $index ); ?>">
                                            <a class="wac-agent-link" href="<?php echo esc_url( $this->build_whatsapp_link( $agent ) ); ?>" target="_blank" rel="noopener">
                                                <span class="wac-agent-name"><?php echo esc_html( $agent['name'] ); ?></span>
                                                <?php if ( ! empty( $agent['title'] ) ) : ?>
                                                    <span class="wac-agent-title"><?php echo esc_html( $agent['title'] ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( ! empty( $agent['message'] ) ) : ?>
                                                    <span class="wac-agent-message"><?php echo esc_html( $agent['message'] ); ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <div class="wac-agent-single">
                                    <a class="wac-agent-link" href="<?php echo esc_url( $this->build_whatsapp_link() ); ?>" target="_blank" rel="noopener">
                                        <?php esc_html_e( 'Start WhatsApp chat', 'whatsapp-chat-2-0' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <p class="wac-offline-message"><?php echo esc_html( $this->options['offline_message'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $this->options['gdpr_notice'] ) ) : ?>
                            <p class="wac-gdpr"><?php echo esc_html( $this->options['gdpr_notice'] ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Determine whether the widget should render on the current request.
         *
         * @return bool
         */
        protected function should_display_widget() {
            if ( empty( $this->options['enable_chat'] ) ) {
                return false;
            }

            if ( is_admin() ) {
                return false;
            }

            $condition = $this->options['display_condition'];

            switch ( $condition ) {
                case 'homepage':
                    return is_front_page();
                case 'posts':
                    return is_single() && 'post' === get_post_type();
                case 'pages':
                    return is_page();
                case 'selected':
                    if ( empty( $this->options['custom_pages'] ) || ! is_page() ) {
                        return false;
                    }

                    return in_array( get_queried_object_id(), $this->options['custom_pages'], true );
                default:
                    return true;
            }
        }

        /**
         * Check if chat should be considered available right now.
         *
         * @return bool
         */
        protected function is_available() {
            $timestamp = current_time( 'timestamp' );
            $day       = strtolower( wp_date( 'l', $timestamp ) );

            if ( empty( $this->options['schedule'][ $day ]['enabled'] ) ) {
                return false;
            }

            $start = $this->options['schedule'][ $day ]['start'];
            $end   = $this->options['schedule'][ $day ]['end'];

            $start_time = strtotime( $start, $timestamp );
            $end_time   = strtotime( $end, $timestamp );

            if ( false === $start_time || false === $end_time ) {
                return true;
            }

            if ( $end_time < $start_time ) {
                $end_time += DAY_IN_SECONDS;
            }

            return $timestamp >= $start_time && $timestamp <= $end_time;
        }

        /**
         * Retrieve agent list or fallback to global phone.
         *
         * @return array
         */
        protected function get_agents() {
            if ( ! empty( $this->options['agents'] ) ) {
                return $this->options['agents'];
            }

            if ( empty( $this->options['global_phone'] ) ) {
                return [];
            }

            return [
                [
                    'name'    => __( 'Support', 'whatsapp-chat-2-0' ),
                    'phone'   => $this->options['global_phone'],
                    'title'   => '',
                    'message' => '',
                ],
            ];
        }

        /**
         * Build WhatsApp link for agent.
         *
         * @param array|null $agent Agent data.
         *
         * @return string
         */
        protected function build_whatsapp_link( $agent = null ) {
            $phone   = $agent ? $agent['phone'] : $this->options['global_phone'];
            $message = $agent && ! empty( $agent['message'] ) ? $agent['message'] : $this->options['prefill_message'];

            if ( empty( $phone ) ) {
                return '#';
            }

            $query = rawurlencode( $message );

            return sprintf( 'https://wa.me/%1$s?text=%2$s', rawurlencode( $phone ), $query );
        }

        /**
         * Render the plugin settings page.
         */
        public function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $this->options = $this->get_options();
            ?>
            <div class="wrap wac-admin">
                <h1><?php esc_html_e( 'WhatsApp Chat 2.0', 'whatsapp-chat-2-0' ); ?></h1>
                <p class="description"><?php esc_html_e( 'Create an advanced WhatsApp support hub with agent routing, working hours, and design controls.', 'whatsapp-chat-2-0' ); ?></p>
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'whatsapp_chat_options_group' );
                    do_settings_sections( 'whatsapp-chat-2-0' );
                    submit_button( __( 'Save settings', 'whatsapp-chat-2-0' ) );
                    ?>
                </form>
            </div>
            <?php
        }

        /**
         * Render checkbox field.
         */
        public function render_checkbox_field( $args ) {
            $id = $args['label_for'];
            ?>
            <label>
                <input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $this->option_name . '[' . $id . ']' ); ?>" value="1" <?php checked( ! empty( $this->options[ $id ] ) ); ?> />
                <?php if ( ! empty( $args['description'] ) ) : ?>
                    <span class="description"><?php echo esc_html( $args['description'] ); ?></span>
                <?php endif; ?>
            </label>
            <?php
        }

        /**
         * Render text field.
         */
        public function render_text_field( $args ) {
            $id          = $args['label_for'];
            $value       = isset( $this->options[ $id ] ) ? $this->options[ $id ] : '';
            $input_value = is_array( $value ) ? implode( ',', array_map( 'absint', $value ) ) : $value;
            ?>
            <input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $this->option_name . '[' . $id . ']' ); ?>" value="<?php echo esc_attr( $input_value ); ?>" class="regular-text" />
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
            <?php endif; ?>
            <?php
        }

        /**
         * Render textarea field.
         */
        public function render_textarea_field( $args ) {
            $id    = $args['label_for'];
            $value = isset( $this->options[ $id ] ) ? $this->options[ $id ] : '';
            ?>
            <textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $this->option_name . '[' . $id . ']' ); ?>" class="large-text" rows="4"><?php echo esc_textarea( $value ); ?></textarea>
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
            <?php endif; ?>
            <?php
        }

        /**
         * Render select field.
         */
        public function render_select_field( $args ) {
            $id      = $args['label_for'];
            $value   = isset( $this->options[ $id ] ) ? $this->options[ $id ] : '';
            $options = isset( $args['options'] ) ? $args['options'] : [];
            ?>
            <select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $this->option_name . '[' . $id . ']' ); ?>">
                <?php foreach ( $options as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
            <?php endif; ?>
            <?php
        }

        /**
         * Render color field.
         */
        public function render_color_field( $args ) {
            $id    = $args['label_for'];
            $value = isset( $this->options[ $id ] ) ? $this->options[ $id ] : '';
            ?>
            <input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $this->option_name . '[' . $id . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="wac-color-field" data-default-color="<?php echo esc_attr( $value ); ?>" />
            <?php
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );

            if ( ! empty( $args['description'] ) ) :
                ?>
                <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
                <?php
            endif;
        }

        /**
         * Render schedule field table.
         */
        public function render_schedule_field() {
            $days = [
                'monday'    => __( 'Monday', 'whatsapp-chat-2-0' ),
                'tuesday'   => __( 'Tuesday', 'whatsapp-chat-2-0' ),
                'wednesday' => __( 'Wednesday', 'whatsapp-chat-2-0' ),
                'thursday'  => __( 'Thursday', 'whatsapp-chat-2-0' ),
                'friday'    => __( 'Friday', 'whatsapp-chat-2-0' ),
                'saturday'  => __( 'Saturday', 'whatsapp-chat-2-0' ),
                'sunday'    => __( 'Sunday', 'whatsapp-chat-2-0' ),
            ];
            ?>
            <table class="widefat fixed wac-schedule">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Day', 'whatsapp-chat-2-0' ); ?></th>
                        <th><?php esc_html_e( 'Enabled', 'whatsapp-chat-2-0' ); ?></th>
                        <th><?php esc_html_e( 'Start time', 'whatsapp-chat-2-0' ); ?></th>
                        <th><?php esc_html_e( 'End time', 'whatsapp-chat-2-0' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $days as $key => $label ) :
                        $day_settings = $this->options['schedule'][ $key ];
                        ?>
                        <tr>
                            <td><?php echo esc_html( $label ); ?></td>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[schedule][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $day_settings['enabled'], 1 ); ?> />
                                </label>
                            </td>
                            <td>
                                <input type="time" name="<?php echo esc_attr( $this->option_name ); ?>[schedule][<?php echo esc_attr( $key ); ?>][start]" value="<?php echo esc_attr( $day_settings['start'] ); ?>" />
                            </td>
                            <td>
                                <input type="time" name="<?php echo esc_attr( $this->option_name ); ?>[schedule][<?php echo esc_attr( $key ); ?>][end]" value="<?php echo esc_attr( $day_settings['end'] ); ?>" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        /**
         * Render agents repeater field.
         */
        public function render_agents_field() {
            ?>
            <div class="wac-agents-wrapper" id="wac-agent-repeater">
                <div class="wac-agent-rows">
                    <?php
                    if ( ! empty( $this->options['agents'] ) ) {
                        foreach ( $this->options['agents'] as $index => $agent ) {
                            $this->render_agent_row( $agent, $index );
                        }
                    } else {
                        $this->render_agent_row(
                            [
                                'name'     => '',
                                'phone'    => '',
                                'title'    => '',
                                'message'  => '',
                                'priority' => 0,
                            ],
                            0
                        );
                    }
                    ?>
                </div>
                <button type="button" class="button wac-add-agent" data-template="wac-agent-template"><?php esc_html_e( 'Add agent', 'whatsapp-chat-2-0' ); ?></button>
            </div>
            <script type="text/html" id="wac-agent-template">
                <?php echo $this->get_agent_template(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </script>
            <?php
        }

        /**
         * Render a single agent row.
         *
         * @param array       $agent Agent data.
         * @param int|string  $index Index for name attributes.
         */
        protected function render_agent_row( $agent, $index ) {
            ?>
            <div class="wac-agent-row" data-index="<?php echo esc_attr( $index ); ?>">
                <div class="wac-field">
                    <label>
                        <span><?php esc_html_e( 'Name', 'whatsapp-chat-2-0' ); ?></span>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[agents][<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $agent['name'] ); ?>" />
                    </label>
                </div>
                <div class="wac-field">
                    <label>
                        <span><?php esc_html_e( 'Phone', 'whatsapp-chat-2-0' ); ?></span>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[agents][<?php echo esc_attr( $index ); ?>][phone]" value="<?php echo esc_attr( $agent['phone'] ); ?>" placeholder="5511999999999" />
                    </label>
                </div>
                <div class="wac-field">
                    <label>
                        <span><?php esc_html_e( 'Role / title', 'whatsapp-chat-2-0' ); ?></span>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[agents][<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $agent['title'] ); ?>" />
                    </label>
                </div>
                <div class="wac-field">
                    <label>
                        <span><?php esc_html_e( 'Custom greeting', 'whatsapp-chat-2-0' ); ?></span>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[agents][<?php echo esc_attr( $index ); ?>][message]" value="<?php echo esc_attr( $agent['message'] ); ?>" />
                    </label>
                </div>
                <div class="wac-field">
                    <label>
                        <span><?php esc_html_e( 'Priority (lower first)', 'whatsapp-chat-2-0' ); ?></span>
                        <input type="number" min="0" step="1" name="<?php echo esc_attr( $this->option_name ); ?>[agents][<?php echo esc_attr( $index ); ?>][priority]" value="<?php echo esc_attr( $agent['priority'] ); ?>" />
                    </label>
                </div>
                <button type="button" class="button-link wac-remove-agent" aria-label="<?php esc_attr_e( 'Remove agent', 'whatsapp-chat-2-0' ); ?>">&times;</button>
            </div>
            <?php
        }
    }
}
