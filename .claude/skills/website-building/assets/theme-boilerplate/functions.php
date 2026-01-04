<?php
/**
 * TechPub Theme functions and definitions
 *
 * @package TechPub
 */

if ( ! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
}

// Theme Constants
define( 'TECHPUB_VERSION', '1.0.0' );
define( 'TECHPUB_THEME_DIR', get_template_directory() );
define( 'TECHPUB_THEME_URI', get_template_directory_uri() );

/**
 * Theme Setup
 */
function techpub_setup() {
    // Add theme support
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );
    add_theme_support( 'custom-logo', array(
        'height'      => 60,
        'width'       => 250,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'align-wide' );

    // Register navigation menus
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'techpub' ),
        'footer'  => __( 'Footer Menu', 'techpub' ),
    ) );

    // Add image sizes
    add_image_size( 'featured-large', 1200, 675, true );  // 16:9
    add_image_size( 'featured-medium', 800, 450, true );
    add_image_size( 'featured-small', 400, 225, true );
    add_image_size( 'card-thumbnail', 600, 338, true );

    // Set content width
    $GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'techpub_setup' );

/**
 * Enqueue scripts and styles
 */
function techpub_scripts() {
    // Styles
    wp_enqueue_style( 'techpub-style', get_stylesheet_uri(), array(), TECHPUB_VERSION );
    wp_enqueue_style( 'techpub-main', TECHPUB_THEME_URI . '/assets/css/main.css', array(), TECHPUB_VERSION );

    // Scripts
    wp_enqueue_script( 'techpub-navigation', TECHPUB_THEME_URI . '/assets/js/navigation.js', array(), TECHPUB_VERSION, true );
    wp_enqueue_script( 'techpub-main', TECHPUB_THEME_URI . '/assets/js/main.js', array( 'jquery' ), TECHPUB_VERSION, true );

    // Localize script for AJAX
    wp_localize_script( 'techpub-main', 'techpubData', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'techpub_nonce' ),
    ) );

    // Comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'techpub_scripts' );

/**
 * Register widget areas
 */
function techpub_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'techpub' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Add widgets here.', 'techpub' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer Widget Area', 'techpub' ),
        'id'            => 'footer-1',
        'description'   => __( 'Appears in the footer section.', 'techpub' ),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="footer-widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'techpub_widgets_init' );

/**
 * Custom template tags
 */
require TECHPUB_THEME_DIR . '/inc/template-tags.php';

/**
 * Customizer additions
 */
require TECHPUB_THEME_DIR . '/inc/customizer.php';

/**
 * Calculate reading time
 */
function techpub_reading_time( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    $content = get_post_field( 'post_content', $post_id );
    $word_count = str_word_count( strip_tags( $content ) );
    $reading_time = ceil( $word_count / 200 ); // 200 words per minute

    return $reading_time;
}

/**
 * Custom excerpt length
 */
function techpub_excerpt_length( $length ) {
    return 30;
}
add_filter( 'excerpt_length', 'techpub_excerpt_length' );

/**
 * Custom excerpt more
 */
function techpub_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'techpub_excerpt_more' );

/**
 * Add lazy loading to images
 */
function techpub_add_lazy_loading( $content ) {
    if ( ! is_admin() ) {
        $content = preg_replace( '/<img(.*?)>/', '<img$1 loading="lazy">', $content );
    }
    return $content;
}
add_filter( 'the_content', 'techpub_add_lazy_loading' );

/**
 * Pagination
 */
function techpub_pagination() {
    the_posts_pagination( array(
        'mid_size'  => 2,
        'prev_text' => '<span class="screen-reader-text">' . __( 'Previous', 'techpub' ) . '</span>',
        'next_text' => '<span class="screen-reader-text">' . __( 'Next', 'techpub' ) . '</span>',
    ) );
}

/**
 * Add meta description
 */
function techpub_meta_description() {
    if ( is_single() || is_page() ) {
        global $post;
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words( strip_tags( $post->post_content ), 30 );
        echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    } elseif ( is_home() || is_front_page() ) {
        echo '<meta name="description" content="' . esc_attr( get_bloginfo( 'description' ) ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'techpub_meta_description' );

/**
 * Add Open Graph tags
 */
function techpub_open_graph_tags() {
    if ( is_single() || is_page() ) {
        global $post;
        $title = get_the_title();
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words( strip_tags( $post->post_content ), 30 );
        $image = has_post_thumbnail() ? get_the_post_thumbnail_url( $post->ID, 'full' ) : '';
        $url = get_permalink();

        echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
        echo '<meta property="og:type" content="article">' . "\n";
        if ( $image ) {
            echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
        }
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'techpub_open_graph_tags' );

/**
 * Schema.org markup
 */
function techpub_schema_markup() {
    if ( is_single() ) {
        global $post;

        $schema = array(
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => get_the_title(),
            'description'   => get_the_excerpt(),
            'image'         => get_the_post_thumbnail_url( $post->ID, 'full' ),
            'datePublished' => get_the_date( 'c' ),
            'dateModified'  => get_the_modified_date( 'c' ),
            'author'        => array(
                '@type' => 'Person',
                'name'  => get_the_author(),
                'url'   => get_author_posts_url( get_the_author_meta( 'ID' ) ),
            ),
            'publisher'     => array(
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
                'logo'  => array(
                    '@type' => 'ImageObject',
                    'url'   => get_site_icon_url(),
                ),
            ),
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'techpub_schema_markup' );

/**
 * Remove unnecessary header items
 */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

/**
 * Disable embeds
 */
function techpub_disable_embeds() {
    wp_deregister_script( 'wp-embed' );
}
add_action( 'wp_footer', 'techpub_disable_embeds' );

/**
 * Defer JavaScript
 */
function techpub_defer_javascript( $tag, $handle, $src ) {
    // Skip jQuery
    if ( 'jquery' === $handle || 'jquery-core' === $handle ) {
        return $tag;
    }

    // Add defer attribute
    return str_replace( ' src=', ' defer src=', $tag );
}
add_filter( 'script_loader_tag', 'techpub_defer_javascript', 10, 3 );
