# WordPress Theme Architecture

## Table of Contents
- Theme Directory Structure
- Required Files
- Template Hierarchy
- Functions and Hooks
- Custom Post Types
- Enqueuing Assets

## Theme Directory Structure

```
theme-name/
├── style.css              # Main stylesheet with theme metadata (REQUIRED)
├── index.php             # Main template file (REQUIRED)
├── functions.php         # Theme functions and features
├── header.php            # Header template
├── footer.php            # Footer template
├── sidebar.php           # Sidebar template
├── single.php            # Single post template
├── page.php              # Page template
├── archive.php           # Archive template
├── category.php          # Category archive template
├── tag.php               # Tag archive template
├── author.php            # Author archive template
├── search.php            # Search results template
├── 404.php               # 404 error template
├── comments.php          # Comments template
├── front-page.php        # Front page template
├── home.php              # Blog posts index
├── screenshot.png        # Theme screenshot (1200x900px)
├── assets/
│   ├── css/
│   │   ├── main.css      # Additional styles
│   │   └── responsive.css
│   ├── js/
│   │   ├── main.js       # Custom JavaScript
│   │   └── navigation.js
│   └── images/
├── template-parts/
│   ├── content.php       # Default content template
│   ├── content-single.php
│   └── content-none.php
├── inc/
│   ├── customizer.php    # Theme customizer settings
│   ├── template-tags.php # Custom template tags
│   └── widgets.php       # Widget areas
└── languages/            # Translation files
```

## Required Files

### style.css Header
```css
/*
Theme Name: Theme Name
Theme URI: https://example.com/theme
Author: Author Name
Author URI: https://example.com
Description: Theme description
Version: 1.0.0
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: theme-slug
Tags: blog, news, magazine
*/
```

### functions.php Template
```php
<?php
/**
 * Theme Name functions and definitions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Theme setup
function themename_setup() {
    // Add theme support
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ) );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'automatic-feed-links' );

    // Register navigation menus
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'theme-slug' ),
        'footer'  => __( 'Footer Menu', 'theme-slug' ),
    ) );

    // Set content width
    $GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'themename_setup' );

// Enqueue scripts and styles
function themename_scripts() {
    // Styles
    wp_enqueue_style( 'themename-style', get_stylesheet_uri(), array(), '1.0.0' );
    wp_enqueue_style( 'themename-main', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0' );

    // Scripts
    wp_enqueue_script( 'themename-navigation', get_template_directory_uri() . '/assets/js/navigation.js', array(), '1.0.0', true );
    wp_enqueue_script( 'themename-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true );

    // Localize script for AJAX
    wp_localize_script( 'themename-main', 'themeData', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'themename_nonce' )
    ) );
}
add_action( 'wp_enqueue_scripts', 'themename_scripts' );

// Register widget areas
function themename_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'theme-slug' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Add widgets here.', 'theme-slug' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'themename_widgets_init' );
```

## Template Hierarchy

WordPress follows a specific template hierarchy. For a single post:
1. single-{post-type}-{slug}.php
2. single-{post-type}.php
3. single.php
4. singular.php
5. index.php

For archives:
1. category-{slug}.php
2. category-{id}.php
3. category.php
4. archive.php
5. index.php

## Header Template (header.php)
```php
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
    <header id="masthead" class="site-header">
        <div class="container">
            <div class="site-branding">
                <?php
                if ( has_custom_logo() ) {
                    the_custom_logo();
                } else {
                    ?>
                    <h1 class="site-title">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <?php bloginfo( 'name' ); ?>
                        </a>
                    </h1>
                    <?php
                }
                ?>
            </div>
            <nav id="site-navigation" class="main-navigation">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                ) );
                ?>
            </nav>
        </div>
    </header>
```

## Footer Template (footer.php)
```php
    <footer id="colophon" class="site-footer">
        <div class="container">
            <div class="footer-widgets">
                <?php dynamic_sidebar( 'sidebar-1' ); ?>
            </div>
            <div class="site-info">
                <p>&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?></p>
            </div>
        </div>
    </footer>
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
```

## Single Post Template (single.php)
```php
<?php
get_header();
?>

<main id="primary" class="site-main">
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <?php
                if ( has_post_thumbnail() ) {
                    the_post_thumbnail( 'large' );
                }
                the_title( '<h1 class="entry-title">', '</h1>' );
                ?>
                <div class="entry-meta">
                    <span class="posted-on"><?php echo get_the_date(); ?></span>
                    <span class="byline">by <?php the_author(); ?></span>
                    <span class="reading-time"><?php echo reading_time(); ?> min read</span>
                </div>
            </header>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>

            <footer class="entry-footer">
                <?php
                the_tags( '<span class="tags-links">', ', ', '</span>' );
                ?>
            </footer>
        </article>
        <?php
        if ( comments_open() || get_comments_number() ) :
            comments_template();
        endif;
    endwhile;
    ?>
</main>

<?php
get_sidebar();
get_footer();
```

## Custom Post Types

Define custom post types in functions.php:

```php
function themename_register_post_types() {
    // Register Articles post type
    register_post_type( 'article', array(
        'labels' => array(
            'name'          => __( 'Articles', 'theme-slug' ),
            'singular_name' => __( 'Article', 'theme-slug' ),
        ),
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => array( 'slug' => 'articles' ),
        'supports'     => array( 'title', 'editor', 'thumbnail', 'author', 'excerpt', 'comments' ),
        'menu_icon'    => 'dashicons-media-document',
    ) );
}
add_action( 'init', 'themename_register_post_types' );
```

## Custom Taxonomies

```php
function themename_register_taxonomies() {
    register_taxonomy( 'topic', 'post', array(
        'labels' => array(
            'name'          => __( 'Topics', 'theme-slug' ),
            'singular_name' => __( 'Topic', 'theme-slug' ),
        ),
        'hierarchical' => true,
        'rewrite'      => array( 'slug' => 'topic' ),
    ) );
}
add_action( 'init', 'themename_register_taxonomies' );
```

## Theme Customizer

```php
function themename_customize_register( $wp_customize ) {
    // Add section
    $wp_customize->add_section( 'themename_colors', array(
        'title'    => __( 'Theme Colors', 'theme-slug' ),
        'priority' => 30,
    ) );

    // Add setting
    $wp_customize->add_setting( 'primary_color', array(
        'default'   => '#0066cc',
        'transport' => 'refresh',
    ) );

    // Add control
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'primary_color', array(
        'label'    => __( 'Primary Color', 'theme-slug' ),
        'section'  => 'themename_colors',
        'settings' => 'primary_color',
    ) ) );
}
add_action( 'customize_register', 'themename_customize_register' );
```

## Helper Functions

### Reading Time Calculator
```php
function reading_time() {
    $content = get_post_field( 'post_content', get_the_ID() );
    $word_count = str_word_count( strip_tags( $content ) );
    $reading_time = ceil( $word_count / 200 ); // 200 words per minute
    return $reading_time;
}
```

### Custom Excerpt Length
```php
function themename_excerpt_length( $length ) {
    return 30;
}
add_filter( 'excerpt_length', 'themename_excerpt_length' );
```

### Pagination
```php
function themename_pagination() {
    the_posts_pagination( array(
        'mid_size'  => 2,
        'prev_text' => __( '&laquo; Previous', 'theme-slug' ),
        'next_text' => __( 'Next &raquo;', 'theme-slug' ),
    ) );
}
```
