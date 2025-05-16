# Elementor ACF Visibility

A lightweight WordPress plugin that allows you to conditionally display or hide Elementor widgets and containers based on Advanced Custom Fields (ACF) values.

## Description

Elementor ACF Visibility gives you granular control over when Elementor elements appear on your website by connecting them to your ACF field data. With a simple, intuitive interface, you can create dynamic layouts that respond to your content without writing any code.

## Features

- Show or hide any Elementor widget based on ACF field values
- Show or hide any Elementor container based on ACF field values
- Multiple comparison options:
  - Equals - Show when field exactly matches a value
  - Not Equals - Show when field doesn't match a value
  - Is Empty - Show when field is empty
  - Is Not Empty - Show when field has any value
- Works seamlessly with existing Elementor layouts
- No performance impact when not in use
- Simple controls in the Advanced tab of every Elementor element

## Requirements

- WordPress 5.0 or higher
- Elementor 3.0 or higher
- Advanced Custom Fields 5.0 or higher (free or Pro)

## Installation

1. Upload the `elementor-acf-visibility` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit a page with Elementor and find the "ACF Visibility" section in the Advanced tab of any widget or container

## Usage

1. Edit any page with Elementor
2. Select a widget or container you want to conditionally display
3. Go to the Advanced tab and find "ACF Visibility"
4. Enable ACF Visibility
5. Enter the ACF field key (e.g., "field_5f7d8c9e1a2b3")
6. Choose your comparison type (Equals, Not Equals, Is Empty, Is Not Empty)
7. If needed, enter the value to compare against
8. Save and preview your page

## Example Use Cases

- Show pricing information only when a product has a sale price
- Display testimonial sections only when testimonials exist
- Show different hero sections based on page category
- Create different layouts for various content types
- Hide elements when certain information is missing
