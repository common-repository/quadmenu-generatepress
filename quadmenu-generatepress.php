<?php
/**
 * Plugin Name: QuadMenu - GeneratePress
 * Plugin URI: https://quadmenu.com/generatepress/
 * Description: Integrates QuadMenu with the GeneratePress theme.
 * Version: 1.0.4
 * Author: QuadMenu
 * Author URI: https://quadmenu.com
* License: GPLv3
 */
if (!defined('ABSPATH')) {
    die('-1');
}

if (!class_exists('QuadMenu_GeneratePress')) {

    final class QuadMenu_GeneratePress {

        public $generatepress_settings = array(
            'generate_settings[background_color]',
            'generate_settings[body_font_size]',
            'generate_settings[body_font_weight]',
            'generate_settings[font_body]',
            'generate_settings[font_navigation]',
            'generate_settings[form_button_background_color]',
            'generate_settings[form_button_background_color_hover]',
            'generate_settings[form_button_text_color]',
            'generate_settings[form_button_text_color_hover]',
            'generate_settings[link_color]',
            'generate_settings[link_color_hover]',
            'generate_settings[link_color_hover]',
            'generate_settings[navigation_background_hover_color]',
            'generate_settings[navigation_font_size]',
            'generate_settings[navigation_font_transform]',
            'generate_settings[navigation_font_weight]',
            'generate_settings[navigation_text_color]',
            'generate_settings[navigation_text_hover_color]',
            'generate_settings[subnavigation_background_color]',
            'generate_settings[subnavigation_text_current_color]',
            'generate_settings[subnavigation_background_hover_color]'
        );

        function __construct() {

            add_action('admin_notices', array($this, 'notices'));

            remove_filter('wp_nav_menu_items', 'generatepress_wc_menu_cart', 10);

            add_filter('wp_nav_menu_items', array($this, 'generatepress_wc_menu_cart'), 999, 2);

            add_action('wp_ajax_quadmenu_generatepress_customized', array($this, 'ajax'));

            add_action('wp_ajax_nopriv_quadmenu_generatepress_customized', array($this, 'ajax'));

            add_filter('wp_head', array($this, 'style'), 10);

            add_action('customize_preview_init', array($this, 'enqueue_preview'));

            add_filter('quadmenu_default_themes', array($this, 'themes'), 10);

            add_filter('quadmenu_developer_options', array($this, 'options'), 10);

            add_filter('quadmenu_default_options', array($this, 'defaults'), 999);
        }

        function notices() {

            $screen = get_current_screen();

            if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
                return;
            }

            $plugin = 'quadmenu/quadmenu.php';

            if (is_plugin_active($plugin)) {
                return;
            }

            if (is_quadmenu_installed()) {

                if (!current_user_can('activate_plugins')) {
                    return;
                }
                ?>
                <div class="error">
                    <p>
                        <a href="<?php echo wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1', 'activate-plugin_' . $plugin); ?>" class='button button-secondary'><?php _e('Activate QuadMenu', 'quadmenu'); ?></a>
                        <?php esc_html_e('QuadMenu GeneratePress not working because you need to activate the QuadMenu plugin.', 'quadmenu'); ?>   
                    </p>
                </div>
                <?php
            } else {

                if (!current_user_can('install_plugins')) {
                    return;
                }
                ?>
                <div class="error">
                    <p>
                        <a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=quadmenu'), 'install-plugin_quadmenu'); ?>" class='button button-secondary'><?php _e('Install QuadMenu', 'quadmenu'); ?></a>
                        <?php esc_html_e('QuadMenu GeneratePress not working because you need to install the QuadMenu plugin.', 'quadmenu'); ?>
                    </p>
                </div>
                <?php
            }
        }

        function generatepress_wc_menu_cart($nav, $args) {

            if (self::is_generatepress() && function_exists('is_cart')) {

                // If our primary menu is set, add the search icon
                if ($args->theme_location == apply_filters('generate_woocommerce_menu_item_location', 'primary') && generatepress_wc_get_setting('cart_menu_item')) {
                    return sprintf(
                            '%1$s
			<li class="wc-menu-item quadmenu-item quadmenu-item-level-0 quadmenu-float-opposite %4$s" title="%2$s">
				%3$s
			</li>', $nav, esc_attr__('View your shopping cart', 'gp-premium'), generatepress_wc_cart_link(), is_cart() ? 'current-menu-item' : ''
                    );
                }
            }

            return $nav;
        }

        function style() {

            if (self::is_generatepress()) {

                global $quadmenu;
                ?>
                <style>
                    body {
                        overflow-x: hidden;
                    }
                    @media (max-width: 768px) {
                        #site-navigation nav#quadmenu {
                            display: none;
                        }

                        #site-navigation.toggled nav#quadmenu {
                            display: block;
                        }

                    }
                    @media (min-width: 768px) {
                        #site-navigation nav#quadmenu .wc-menu-item a {
                            text-align: center;
                            line-height: <?php echo esc_attr($quadmenu['generatepress_navbar_height']); ?>px;
                            width: <?php echo esc_attr($quadmenu['generatepress_navbar_height']); ?>px;
                        }
                    }
                </style>
                <?php
            }
        }

        function ajax() {

            if (class_exists('QuadMenu_Compiler')) {

                global $quadmenu;

                check_ajax_referer('quadmenu', 'nonce');

                $options = apply_filters('quadmenu_developer_options', $quadmenu);
                $options = apply_filters('quadmenu_default_options', $options);

                $variables = QuadMenu_Compiler::less_variables($options);

                wp_send_json($variables);
            }

            wp_die();
        }

        static function is_generatepress() {

            if (!function_exists('generate_get_setting'))
                return false;

            return true;
        }

        function enqueue_preview() {

            add_filter('quadmenu_global_js_data', array($this, 'js_data'));

            wp_enqueue_script('quadmenu-generatepress', plugin_dir_url(__FILE__) . 'assets/quadmenu-generatepress.js', array('jquery'), GENERATE_VERSION, 'all');
        }

        function js_data($data) {

            $data['customizer_settings'] = $this->generatepress_settings;

            return $data;
        }

        function themes($themes) {

            $themes['generatepress'] = 'GeneratePress Theme';

            return $themes;
        }

        function options($options) {

            if (self::is_generatepress()) {

                $options['viewport'] = 0;

                $options['primary_unwrap'] = 0;

                $options['generatepress_layout_breakpoint'] = '768';
                $options['generatepress_theme_title'] = 'GeneratePress Theme';
                $options['generatepress_navbar_toggle_open'] = '#ffffff';
                $options['generatepress_navbar_toggle_close'] = '#ffffff';
                $options['generatepress_layout'] = 'embed';
            }

            return $options;
        }

        function defaults($defaults) {

            if (self::is_generatepress()) {

                $generate_settings = wp_parse_args(
                        get_option('generate_settings', array()), generate_get_default_fonts()
                );

                $defaults['primary_integration'] = 1;
                $defaults['primary_unwrap'] = 0;
                $defaults['primary_theme'] = 'generatepress';
                $defaults['generatepress_layout_offcanvas_float'] = 'right';
                $defaults['generatepress_layout_align'] = 'left';
                $defaults['generatepress_layout_width'] = 0;
                $defaults['generatepress_layout_width_inner'] = 1;
                $defaults['generatepress_layout_width_inner_selector'] = '#content';
                $defaults['generatepress_layout_trigger'] = 'hoverintent';
                $defaults['generatepress_layout_current'] = '';
                $defaults['generatepress_layout_animation'] = 'quadmenu_btt';
                $defaults['generatepress_layout_classes'] = '';
                $defaults['generatepress_layout_sticky'] = 1;
                $defaults['generatepress_layout_sticky_offset'] = 0;
                $defaults['generatepress_layout_generatepressder'] = 'hide';
                $defaults['generatepress_layout_caret'] = 'show';
                $defaults['generatepress_layout_hover_effect'] = '';
                $defaults['generatepress_navbar_background'] = 'color';
                $defaults['generatepress_navbar_background_color'] = 'transparent';
                $defaults['generatepress_navbar_background_to'] = 'transparent';
                $defaults['generatepress_navbar_background_deg'] = '17';
                $defaults['generatepress_navbar_divider'] = 'transparent';
                $defaults['generatepress_navbar_height'] = '60';
                $defaults['generatepress_navbar_width'] = '260';
                $defaults['generatepress_navbar_mobile_border'] = 'transparent';

                $defaults['generatepress_navbar_logo'] = array(
                    'url' => '',
                    'id' => '',
                    'height' => '',
                    'width' => '',
                    'thumbnail' => '',
                    'title' => '',
                    'caption' => '',
                    'alt' => '',
                    'description' => '',
                );
                $defaults['generatepress_navbar_logo_height'] = '43';
                $defaults['generatepress_navbar_logo_bg'] = 'transparent';
                $defaults['generatepress_navbar_link_margin'] = array(
                    'border-top' => '0px',
                    'border-right' => '0px',
                    'border-bottom' => '0px',
                    'border-left' => '0px',
                    'border-style' => '',
                    'border-color' => '',
                );
                $defaults['generatepress_navbar_link_radius'] = array(
                    'border-top' => '0px',
                    'border-right' => '0px',
                    'border-bottom' => '0px',
                    'border-left' => '0px',
                    'border-style' => '',
                    'border-color' => '',
                );
                $defaults['generatepress_sticky_height'] = $defaults['generatepress_navbar_height'];
                $defaults['generatepress_sticky_logo_height'] = $defaults['generatepress_navbar_logo_height'];
                $defaults['generatepress_dropdown_shadow'] = 'none';
                $defaults['generatepress_dropdown_margin'] = '0';
                $defaults['generatepress_dropdown_radius'] = array(
                    'border-top' => '0',
                    'border-right' => '0',
                    'border-left' => '3',
                    'border-bottom' => '3',
                );

                $defaults['generatepress_dropdown_link_transform'] = 'none';

                $defaults['generatepress_navbar_link_bg'] = 'transparent';

                $font = array(
                    'font-family' => 'Open Sans',
                    'font-size' => '13',
                    'font-style' => 'normal',
                    'font-weight' => '400',
                    'letter-spacing' => 'inherit',
                );

                $defaults['generatepress_font'] = wp_parse_args(array(
                    'font-family' => $generate_settings['font_navigation'],
                    'font-options' => '',
                    'google' => '1',
                    'font-weight' => $generate_settings['navigation_font_weight'],
                    'font-style' => '',
                    'subsets' => '',
                    'font-size' => $generate_settings['navigation_font_size']), $font);

                $defaults['generatepress_dropdown_font'] = wp_parse_args(array(
                    'font-family' => $generate_settings['font_navigation'],
                    'font-options' => '',
                    'google' => '1',
                    'font-weight' => $generate_settings['navigation_font_weight'],
                    'font-style' => '',
                    'subsets' => '',
                    'font-size' => $generate_settings['navigation_font_size']), $font);

                $defaults['generatepress_navbar_font'] = wp_parse_args(array(
                    'font-family' => $generate_settings['font_navigation'],
                    'font-options' => '',
                    'google' => '1',
                    'font-weight' => $generate_settings['navigation_font_weight'],
                    'font-style' => '',
                    'subsets' => '',
                    'font-size' => $generate_settings['navigation_font_size']), $font);

                // GP Premium
                // -------------------------------------------------------------

                if (function_exists('generate_get_color_setting')) {

                    $defaults['generatepress_navbar_text'] = generate_get_color_setting('navigation_text_color');
                    $defaults['generatepress_navbar_link'] = generate_get_color_setting('navigation_text_color');
                    $defaults['generatepress_navbar_link_hover'] = generate_get_color_setting('navigation_text_hover_color');
                    $defaults['generatepress_navbar_link_bg_hover'] = generate_get_color_setting('navigation_background_hover_color');
                    $defaults['generatepress_navbar_link_hover_effect'] = generate_get_color_setting('navigation_text_color');
                    $defaults['generatepress_navbar_link_transform'] = $generate_settings['navigation_font_transform'];
                    $defaults['generatepress_navbar_button'] = generate_get_color_setting('form_button_text_color');
                    $defaults['generatepress_navbar_button_bg'] = generate_get_color_setting('form_button_background_color');
                    $defaults['generatepress_navbar_button_hover'] = generate_get_color_setting('form_button_text_color_hover');
                    $defaults['generatepress_navbar_button_bg_hover'] = generate_get_color_setting('form_button_background_color_hover');
                    $defaults['generatepress_navbar_link_icon'] = generate_get_color_setting('link_color');
                    $defaults['generatepress_navbar_link_icon_hover'] = generate_get_color_setting('link_color_hover');
                    $defaults['generatepress_navbar_link_subtitle'] = generate_get_color_setting('entry_meta_link_color');
                    $defaults['generatepress_navbar_link_subtitle_hover'] = generate_get_color_setting('link_color_hover');
                    $defaults['generatepress_navbar_badge'] = generate_get_color_setting('link_color');
                    $defaults['generatepress_navbar_badge_color'] = generate_get_color_setting('background_color');
                    $defaults['generatepress_sticky_background'] = generate_get_color_setting('navigation_background_color');
                    $defaults['generatepress_navbar_scrollbar'] = generate_get_color_setting('link_color');
                    $defaults['generatepress_navbar_scrollbar_rail'] = generate_get_color_setting('background_color');
                    $defaults['generatepress_dropdown_border'] = array(
                        'border-top' => '0',
                        'border-right' => '',
                        'border-bottom' => '',
                        'border-left' => '',
                        'border-style' => '',
                        'border-color' => generate_get_color_setting('link_color')
                    );
                    $defaults['generatepress_dropdown_background'] = generate_get_color_setting('subnavigation_background_color');
                    $defaults['generatepress_dropdown_scrollbar'] = generate_get_color_setting('link_color');
                    $defaults['generatepress_dropdown_scrollbar_rail'] = generate_get_color_setting('background_color');
                    $defaults['generatepress_dropdown_title'] = generate_get_color_setting('subnavigation_text_current_color');
                    $defaults['generatepress_dropdown_title_border'] = array(
                        'border-top' => '1px',
                        'border-right' => '',
                        'border-bottom' => '',
                        'border-left' => '',
                        'border-style' => 'solid',
                        'border-color' => generate_get_color_setting('link_color')
                    );
                    $defaults['generatepress_dropdown_link'] = generate_get_color_setting('subnavigation_text_color');
                    $defaults['generatepress_dropdown_link_hover'] = generate_get_color_setting('subnavigation_text_hover_color');
                    $defaults['generatepress_dropdown_link_bg_hover'] = generate_get_color_setting('subnavigation_background_hover_color');
                    $defaults['generatepress_dropdown_link_border'] = array(
                        'border-top' => '0px',
                        'border-right' => '0px',
                        'border-bottom' => '0px',
                        'border-left' => '0px',
                        'border-style' => 'none',
                        'border-color' => generate_get_color_setting('background_color')
                    );

                    $defaults['generatepress_dropdown_button'] = generate_get_color_setting('form_button_text_color');
                    $defaults['generatepress_dropdown_button_bg'] = generate_get_color_setting('form_button_background_color');
                    $defaults['generatepress_dropdown_button_hover'] = generate_get_color_setting('form_button_text_color_hover');
                    $defaults['generatepress_dropdown_button_bg_hover'] = generate_get_color_setting('form_button_background_color_hover');
                    $defaults['generatepress_dropdown_link_icon'] = generate_get_color_setting('link_color');
                    $defaults['generatepress_dropdown_link_icon_hover'] = generate_get_color_setting('link_color_hover');
                    $defaults['generatepress_dropdown_link_subtitle'] = generate_get_color_setting('entry_meta_link_color');
                    $defaults['generatepress_dropdown_link_subtitle_hover'] = generate_get_color_setting('entry_meta_link_color');
                }
            }

            return $defaults;
        }

        static function activation() {

            update_option('_quadmenu_compiler', true);

            if (class_exists('QuadMenu')) {

                QuadMenu_Redux::add_notification('blue', esc_html__('Thanks for install QuadMenu GeneratePress. We have to create the stylesheets. Please wait.', 'quadmenu-generatepress'));

                QuadMenu_Activation::activation();
            }
        }

    }

    new QuadMenu_GeneratePress();

    register_activation_hook(__FILE__, array('QuadMenu_GeneratePress', 'activation'));
}

if (!function_exists('is_quadmenu_installed')) {

    function is_quadmenu_installed() {

        $file_path = 'quadmenu/quadmenu.php';

        $installed_plugins = get_plugins();

        return isset($installed_plugins[$file_path]);
    }

}