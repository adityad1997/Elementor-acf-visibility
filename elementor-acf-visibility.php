<?php
/**
 * Plugin Name: Elementor ACF Visibility
 * Description: Display or hide Elementor widgets and containers based on ACF content data for single posts.
 * Version: 1.0.2
 * Author: Moondroo Web Services
 * Text Domain: elementor-acf-visibility
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Elementor_ACF_Visibility {

    public function __construct() {
        // Add visibility logic for widgets and containers
        add_action( 'elementor/widget/render_content', [ $this, 'filter_widget_visibility' ], 10, 2 );
        add_action( 'elementor/frontend/container/before_render', [ $this, 'filter_container_visibility' ] );

        // Add visibility controls to widgets and containers
        add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'add_acf_visibility_controls' ], 10, 2 );
        add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'add_acf_visibility_controls' ], 10, 2 ); // Updated hook for containers
    }

    /**
     * Filter widget visibility based on ACF data.
     *
     * @param string $content The widget content.
     * @param \Elementor\Widget_Base $widget The widget instance.
     * @return string
     */
    public function filter_widget_visibility( $content, $widget ) {
        if ( ! is_singular() ) {
            return $content; // Only apply on single posts/pages.
        }

        $settings = $widget->get_settings();

        // Check if ACF visibility is enabled for this widget.
        if ( empty( $settings['acf_visibility_enabled'] ) || 'yes' !== $settings['acf_visibility_enabled'] ) {
            return $content;
        }

        // Get the ACF field key and value to check.
        $acf_field = $settings['acf_field_key'];
        
        // Get the comparison type (with backward compatibility)
        $comparison_type = isset($settings['acf_comparison_type']) ? $settings['acf_comparison_type'] : 'equals';
        
        // Get the ACF field value for the current post.
        $current_value = get_field( $acf_field, get_the_ID() );
        
        // Check if we should display based on the comparison type
        $should_display = false;
        
        switch ($comparison_type) {
            case 'equals':
                $acf_value = $settings['acf_field_value'];
                $should_display = ($current_value === $acf_value);
                break;
                
            case 'not_equals':
                $acf_value = $settings['acf_field_value'];
                $should_display = ($current_value !== $acf_value);
                break;
                
            case 'is_empty':
                $should_display = empty($current_value);
                break;
                
            case 'is_not_empty':
                $should_display = !empty($current_value);
                break;
                
            default:
                // Default to equals for backward compatibility
                $acf_value = $settings['acf_field_value'];
                $should_display = ($current_value === $acf_value);
        }

        // Hide the widget if it should not be displayed
        if (!$should_display) {
            return '';
        }

        return $content; // Show the widget if it should be displayed
    }

    /**
     * Filter container visibility based on ACF data.
     *
     * @param \Elementor\Element_Base $element The container element.
     */
    public function filter_container_visibility( $element ) {
        if ( ! is_singular() ) {
            return; // Only apply on single posts/pages.
        }

        $settings = $element->get_settings();

        // Check if ACF visibility is enabled for this container.
        if ( empty( $settings['acf_visibility_enabled'] ) || 'yes' !== $settings['acf_visibility_enabled'] ) {
            return;
        }

        // Get the ACF field key
        $acf_field = $settings['acf_field_key'];
        
        if ( empty( $acf_field ) ) {
            return; // No ACF field specified.
        }
        
        // Get the comparison type (with backward compatibility)
        $comparison_type = isset($settings['acf_comparison_type']) ? $settings['acf_comparison_type'] : 'equals';
        
        // Get the ACF field value for the current post.
        $current_value = get_field( $acf_field, get_the_ID() );
        
        // Check if we should display based on the comparison type
        $should_display = false;
        
        switch ($comparison_type) {
            case 'equals':
                $acf_value = $settings['acf_field_value'];
                if ( empty( $acf_value ) && $comparison_type === 'equals' ) {
                    return; // No ACF value specified for equals comparison.
                }
                $should_display = ($current_value === $acf_value);
                break;
                
            case 'not_equals':
                $acf_value = $settings['acf_field_value'];
                if ( empty( $acf_value ) && $comparison_type === 'not_equals' ) {
                    return; // No ACF value specified for not_equals comparison.
                }
                $should_display = ($current_value !== $acf_value);
                break;
                
            case 'is_empty':
                $should_display = empty($current_value);
                break;
                
            case 'is_not_empty':
                $should_display = !empty($current_value);
                break;
                
            default:
                // Default to equals for backward compatibility
                $acf_value = $settings['acf_field_value'];
                if ( empty( $acf_value ) ) {
                    return; // No ACF value specified.
                }
                $should_display = ($current_value === $acf_value);
        }

        // Hide the container if it should not be displayed
        if (!$should_display) {
            $element->add_render_attribute( '_wrapper', 'style', 'display: none;' );
        }
    }

    /**
     * Add ACF visibility controls to widgets and containers.
     *
     * @param \Elementor\Element_Base $element The element instance.
     * @param array $args The element arguments.
     */
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
                'label'       => __( 'ACF Field Key', 'elementor-acf-visibility' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'description' => __( 'Enter the ACF field key to check.', 'elementor-acf-visibility' ),
                'condition'   => [
                    'acf_visibility_enabled' => 'yes',
                ],
            ]
        );
        
        $element->add_control(
            'acf_comparison_type',
            [
                'label'       => __( 'Comparison Type', 'elementor-acf-visibility' ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'default'     => 'equals',
                'options'     => [
                    'equals'        => __( 'Equals', 'elementor-acf-visibility' ),
                    'not_equals'    => __( 'Not Equals', 'elementor-acf-visibility' ),
                    'is_empty'      => __( 'Is Empty', 'elementor-acf-visibility' ),
                    'is_not_empty'  => __( 'Is Not Empty', 'elementor-acf-visibility' ),
                ],
                'description' => __( 'Select the type of comparison to perform.', 'elementor-acf-visibility' ),
                'condition'   => [
                    'acf_visibility_enabled' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'acf_field_value',
            [
                'label'       => __( 'ACF Field Value', 'elementor-acf-visibility' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'description' => __( 'Enter the value to compare with the ACF field.', 'elementor-acf-visibility' ),
                'condition'   => [
                    'acf_visibility_enabled' => 'yes',
                    'acf_comparison_type' => ['equals', 'not_equals'],
                ],
            ]
        );

        $element->end_controls_section();
    }
}

new Elementor_ACF_Visibility();