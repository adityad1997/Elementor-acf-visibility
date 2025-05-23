<?php
/**
 * Plugin Name: Elementor ACF Visibility
 * Description: Display or hide Elementor widgets/containers based on ACF.
 * Version: 1.2.0
 * Author: Moondroo Web Services
 * Text Domain: elementor-acf-visibility
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Elementor_ACF_Visibility_JS {

    public function __construct() {
        // Add data attributes
        add_action( 'elementor/frontend/widget/before_render', [ $this, 'add_visibility_data_attributes' ] );
        add_action( 'elementor/frontend/container/before_render', [ $this, 'add_visibility_data_attributes' ] );

        // Add controls (same as before)
        add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'add_acf_visibility_controls' ], 10, 2 );
        add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'add_acf_visibility_controls' ], 10, 2 );

        // Enqueue JS and Localize Data
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // AJAX Handler
        add_action( 'wp_ajax_get_acf_values', [ $this, 'ajax_get_acf_values' ] );
        add_action( 'wp_ajax_nopriv_get_acf_values', [ $this, 'ajax_get_acf_values' ] );
    }

    /**
     * Get the current Post ID reliably.
     */
    private function get_current_post_id() {
        $post_id = get_queried_object_id();
        if ( ! $post_id ) { $post_id = get_the_ID(); }
        return $post_id ? $post_id : false;
    }

    /**
     * Add data attributes to elements instead of hiding them.
     *
     * @param \Elementor\Element_Base $element The element instance.
     */
    public function add_visibility_data_attributes( $element ) {
        $settings = $element->get_settings_for_display();

        if ( ! empty( $settings['acf_visibility_enabled'] ) && 'yes' === $settings['acf_visibility_enabled'] && ! empty( $settings['acf_field_key'] ) ) {
            $element->add_render_attribute( '_wrapper', [
                'data-acf-visibility' => 'true',
                'data-acf-field'      => $settings['acf_field_key'],
                'data-acf-compare'    => $settings['acf_comparison_type'] ?? 'equals',
                'data-acf-value'      => $settings['acf_field_value'] ?? '',
                // We add a class to easily find these elements and hide them initially
                'class'               => 'eav-check-visibility',
            ]);
        }
    }

    /**
     * Enqueue JS and pass Post ID / AJAX URL.
     */
    public function enqueue_scripts() {
        // Only enqueue if we are on a singular page (or potentially editor)
        if ( is_singular() || ( isset($_GET['elementor-preview']) && $_GET['elementor-preview'] ) ) {
            $post_id = $this->get_current_post_id();

            if ($post_id) {
                wp_enqueue_script(
                    'elementor-acf-visibility-js',
                    plugin_dir_url( __FILE__ ) . 'visibility-checker.js', // We will create this file
                    [ 'jquery' ], // Depends on jQuery
                    '1.2.0',
                    true // Load in footer
                );

                wp_localize_script(
                    'elementor-acf-visibility-js',
                    'eavData', // Object name in JS
                    [
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'post_id'  => $post_id,
                        'nonce'    => wp_create_nonce( 'eav_get_acf_values_nonce' ),
                    ]
                );

                // Add some basic CSS to hide elements *before* JS runs to prevent flashing
                wp_add_inline_style( 'elementor-frontend', '.eav-check-visibility { visibility: hidden; }' );
                wp_add_inline_style( 'elementor-frontend', '.eav-check-visibility.eav-show { visibility: visible; }' );

            }
        }
    }

    /**
     * AJAX handler to fetch ACF values.
     */
    public function ajax_get_acf_values() {
        check_ajax_referer( 'eav_get_acf_values_nonce', 'nonce' );

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $fields = isset($_POST['fields']) ? array_map('sanitize_text_field', $_POST['fields']) : [];

        if ( ! $post_id || empty($fields) ) {
            wp_send_json_error('Missing data.');
        }

        // You might want to add more security checks here - e.g., can current user view this post?

        $values = [];
        foreach ($fields as $field) {
            $values[$field] = get_field($field, $post_id, false); // Get raw value
        }

        wp_send_json_success($values);
    }

    // Keep add_acf_visibility_controls() exactly as it was in the previous version.
    public function add_acf_visibility_controls( $element, $args ) {
         $element->start_controls_section(
            'acf_visibility_section',
            [
                'label' => __( 'ACF Visibility', 'elementor-acf-visibility' ),
                'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
            ]
        );
        $element->add_control(
            'acf_visibility_enabled',
            [
                'label'        => __( 'Enable ACF Visibility', 'elementor-acf-visibility' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'elementor-acf-visibility' ),
                'label_off'    => __( 'No', 'elementor-acf-visibility' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );
        $element->add_control(
            'acf_field_key',
            [
                'label'       => __( 'ACF Field Key/Name', 'elementor-acf-visibility' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'description' => __( 'Enter the ACF field name.', 'elementor-acf-visibility' ),
                'condition'   => [ 'acf_visibility_enabled' => 'yes', ],
            ]
        );
        $element->add_control(
            'acf_comparison_type',
            [
                'label'       => __( 'Comparison Type', 'elementor-acf-visibility' ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'default'     => 'contains',
                'options'     => [
                    'equals'       => __( 'Equals', 'elementor-acf-visibility' ),
                    'not_equals'   => __( 'Not Equals', 'elementor-acf-visibility' ),
                    'contains'     => __( 'Contains', 'elementor-acf-visibility' ),
                    'not_contains' => __( 'Does Not Contain', 'elementor-acf-visibility' ),
                    'is_empty'     => __( 'Is Empty', 'elementor-acf-visibility' ),
                    'is_not_empty' => __( 'Is Not Empty', 'elementor-acf-visibility' ),
                ],
                'condition'   => [ 'acf_visibility_enabled' => 'yes', ],
            ]
        );
        $element->add_control(
            'acf_field_value',
            [
                'label'       => __( 'ACF Field Value', 'elementor-acf-visibility' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'description' => __( 'Value to compare with (1 or 0 for True/False).', 'elementor-acf-visibility' ),
                'condition'   => [
                    'acf_visibility_enabled' => 'yes',
                    'acf_comparison_type' => ['equals', 'not_equals', 'contains', 'not_contains'],
                ],
            ]
        );
        $element->end_controls_section();
    }
}

new Elementor_ACF_Visibility_JS();