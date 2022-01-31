<?php

class M16_Events_Hero {
    public function __construct() {
        // Setup the Theme Customizer settings and controls...
        add_action('customize_register', array(&$this, 'register'));
        // Output custom CSS to live site
        add_action('wp_head', array(&$this, 'get_banner'));
    }

    //adds new areas and fields to WP theme customizer
    public static function register($wp_customize) {
        //new panel for theme options
        $wp_customize->add_panel('theme_opts', array(
            'title'    => 'Theme Options',
            'priority' => 1,
        ));

        //new section for header options
        $wp_customize->add_section('header_opts', array(
            'title'    => 'Header Options',
            'priority' => 120,
            'panel'    => 'theme_opts'
        ));

        // department/theme bug
        $wp_customize->add_setting('WMS_text_bug', array(
            'capability'        => 'edit_theme_options',
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('WMS_text_bug', array(
            'type'        => 'text',
            'section'     => 'header_opts',
            'label'       => 'Department Bug',
            'description' => 'Add a department bug next to the wordmark.',
        ));
    }

    /**
     * This will put custom CSS into the live theme's WP head.
     *
     * Used by hook: 'wp_head'
     *
     * @see add_action('wp_head',$func)
     */
    public static function get_banner() {
        $randomHeader = get_random_header_image(); //images from customizer
        echo <<< EOD
        <!--header rotate CSS-->
        <style type="text/css">
            .events-hero {
                background-image: url({$randomHeader});
            }

            .tribe-events-page-template .network-header:before {
                background-image: linear-gradient(180deg, rgba(0, 0, 0, .8) 0, rgba(0, 0, 0, .35) 0), url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='2' height='2' viewBox='0 0 4 4'%3E%3Cpath fill='%23000000' fill-opacity='0.85' d='M1 3h1v1H1V3zm2-2h1v1H3V1z'%3E%3C/path%3E%3C/svg%3E"), url({$randomHeader});
            }
        </style>
        <!--/header rotate CSS-->

EOD;
    }
}

new M16_Events_Hero();