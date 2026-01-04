<?php
/**
 * Theme Customizer
 *
 * @package TechPub
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function techpub_customize_register( $wp_customize ) {
    $wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
    $wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
    $wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

    if ( isset( $wp_customize->selective_refresh ) ) {
        $wp_customize->selective_refresh->add_partial(
            'blogname',
            array(
                'selector'        => '.site-title a',
                'render_callback' => 'techpub_customize_partial_blogname',
            )
        );
        $wp_customize->selective_refresh->add_partial(
            'blogdescription',
            array(
                'selector'        => '.site-description',
                'render_callback' => 'techpub_customize_partial_blogdescription',
            )
        );
    }

    // Add color scheme section
    $wp_customize->add_section(
        'techpub_colors',
        array(
            'title'    => __( 'Theme Colors', 'techpub' ),
            'priority' => 30,
        )
    );

    // Primary color setting
    $wp_customize->add_setting(
        'primary_color',
        array(
            'default'           => '#0B88EE',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        )
    );

    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize,
            'primary_color',
            array(
                'label'    => __( 'Primary Color', 'techpub' ),
                'section'  => 'techpub_colors',
                'settings' => 'primary_color',
            )
        )
    );

    // Secondary color setting
    $wp_customize->add_setting(
        'secondary_color',
        array(
            'default'           => '#38CB89',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        )
    );

    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize,
            'secondary_color',
            array(
                'label'    => __( 'Secondary Color', 'techpub' ),
                'section'  => 'techpub_colors',
                'settings' => 'secondary_color',
            )
        )
    );
}
add_action( 'customize_register', 'techpub_customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function techpub_customize_partial_blogname() {
    bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function techpub_customize_partial_blogdescription() {
    bloginfo( 'description' );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function techpub_customize_preview_js() {
    wp_enqueue_script( 'techpub-customizer', get_template_directory_uri() . '/assets/js/customizer.js', array( 'customize-preview' ), TECHPUB_VERSION, true );
}
add_action( 'customize_preview_init', 'techpub_customize_preview_js' );

/**
 * Output custom CSS for color scheme
 */
function techpub_custom_colors() {
    $primary_color = get_theme_mod( 'primary_color', '#0B88EE' );
    $secondary_color = get_theme_mod( 'secondary_color', '#38CB89' );

    if ( $primary_color !== '#0B88EE' || $secondary_color !== '#38CB89' ) :
        ?>
        <style type="text/css">
            :root {
                --primary: <?php echo esc_attr( $primary_color ); ?>;
                --secondary: <?php echo esc_attr( $secondary_color ); ?>;
            }
        </style>
        <?php
    endif;
}
add_action( 'wp_head', 'techpub_custom_colors' );
