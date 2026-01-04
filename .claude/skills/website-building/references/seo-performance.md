# SEO and Performance Optimization for WordPress

## Table of Contents
- SEO Best Practices
- Meta Tags and Structured Data
- Performance Optimization
- Image Optimization
- Caching Strategies
- Core Web Vitals

## SEO Best Practices

### Essential SEO Elements

```php
// Add to functions.php
function site_seo_setup() {
    // Add title tag support
    add_theme_support( 'title-tag' );

    // Add meta description
    add_action( 'wp_head', 'add_meta_description' );

    // Add Open Graph tags
    add_action( 'wp_head', 'add_open_graph_tags' );

    // Add Twitter Card tags
    add_action( 'wp_head', 'add_twitter_card_tags' );
}
add_action( 'after_setup_theme', 'site_seo_setup' );

function add_meta_description() {
    if ( is_single() || is_page() ) {
        global $post;
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words( strip_tags( $post->post_content ), 30 );
        echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    } elseif ( is_home() || is_front_page() ) {
        echo '<meta name="description" content="' . esc_attr( get_bloginfo( 'description' ) ) . '">' . "\n";
    }
}

function add_open_graph_tags() {
    if ( is_single() || is_page() ) {
        global $post;
        $title = get_the_title();
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words( strip_tags( $post->post_content ), 30 );
        $image = has_post_thumbnail() ? get_the_post_thumbnail_url( $post->ID, 'large' ) : '';
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

function add_twitter_card_tags() {
    if ( is_single() || is_page() ) {
        global $post;
        $title = get_the_title();
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words( strip_tags( $post->post_content ), 30 );
        $image = has_post_thumbnail() ? get_the_post_thumbnail_url( $post->ID, 'large' ) : '';

        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
        if ( $image ) {
            echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
        }
    }
}
```

### Structured Data (Schema.org)

```php
function add_schema_markup() {
    if ( is_single() ) {
        global $post;

        $schema = array(
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => get_the_title(),
            'description'   => get_the_excerpt(),
            'image'         => get_the_post_thumbnail_url( $post->ID, 'large' ),
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
add_action( 'wp_head', 'add_schema_markup' );
```

### XML Sitemap Generation

```php
function generate_simple_sitemap() {
    header( 'Content-Type: application/xml; charset=utf-8' );
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // Homepage
    echo '<url>';
    echo '<loc>' . esc_url( home_url( '/' ) ) . '</loc>';
    echo '<lastmod>' . date( 'Y-m-d' ) . '</lastmod>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>1.0</priority>';
    echo '</url>' . "\n";

    // Posts
    $posts = get_posts( array(
        'numberposts' => -1,
        'post_status' => 'publish',
    ) );

    foreach ( $posts as $post ) {
        echo '<url>';
        echo '<loc>' . esc_url( get_permalink( $post->ID ) ) . '</loc>';
        echo '<lastmod>' . date( 'Y-m-d', strtotime( $post->post_modified ) ) . '</lastmod>';
        echo '<changefreq>monthly</changefreq>';
        echo '<priority>0.8</priority>';
        echo '</url>' . "\n";
    }

    // Pages
    $pages = get_pages();
    foreach ( $pages as $page ) {
        echo '<url>';
        echo '<loc>' . esc_url( get_permalink( $page->ID ) ) . '</loc>';
        echo '<lastmod>' . date( 'Y-m-d', strtotime( $page->post_modified ) ) . '</lastmod>';
        echo '<changefreq>weekly</changefreq>';
        echo '<priority>0.6</priority>';
        echo '</url>' . "\n";
    }

    echo '</urlset>';
    exit;
}

// Add rewrite rule for sitemap
function sitemap_rewrite_rules() {
    add_rewrite_rule( '^sitemap\.xml$', 'index.php?sitemap=1', 'top' );
}
add_action( 'init', 'sitemap_rewrite_rules' );

function sitemap_query_vars( $vars ) {
    $vars[] = 'sitemap';
    return $vars;
}
add_filter( 'query_vars', 'sitemap_query_vars' );

function sitemap_template_redirect() {
    if ( get_query_var( 'sitemap' ) == 1 ) {
        generate_simple_sitemap();
    }
}
add_action( 'template_redirect', 'sitemap_template_redirect' );
```

## Performance Optimization

### Lazy Load Images

```php
// Add loading="lazy" to images
function add_lazy_loading( $content ) {
    $content = preg_replace( '/<img(.*?)>/', '<img$1 loading="lazy">', $content );
    return $content;
}
add_filter( 'the_content', 'add_lazy_loading' );

// Native WordPress lazy loading
add_filter( 'wp_lazy_loading_enabled', '__return_true' );
```

### Defer JavaScript

```php
function defer_javascript( $tag, $handle, $src ) {
    // Skip jQuery (many plugins depend on it loading first)
    if ( 'jquery' === $handle || 'jquery-core' === $handle ) {
        return $tag;
    }

    // Add defer attribute
    return str_replace( ' src=', ' defer src=', $tag );
}
add_filter( 'script_loader_tag', 'defer_javascript', 10, 3 );
```

### Minify HTML Output

```php
function minify_html( $html ) {
    // Remove comments
    $html = preg_replace( '/<!--(?!<!)[^\[>].*?-->/s', '', $html );

    // Remove whitespace
    $html = preg_replace( '/\s+/', ' ', $html );

    // Remove whitespace around tags
    $html = preg_replace( '/>\s+</', '><', $html );

    return trim( $html );
}

function start_html_minification() {
    ob_start( 'minify_html' );
}
add_action( 'wp_loaded', 'start_html_minification' );
```

### Optimize Database Queries

```php
// Remove unnecessary queries
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

// Disable embeds
function disable_embeds() {
    wp_deregister_script( 'wp-embed' );
}
add_action( 'wp_footer', 'disable_embeds' );

// Limit post revisions
define( 'WP_POST_REVISIONS', 3 );

// Increase autosave interval
define( 'AUTOSAVE_INTERVAL', 300 ); // 5 minutes
```

### Enable GZIP Compression

Add to `.htaccess`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json
</IfModule>
```

### Browser Caching

Add to `.htaccess`:
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>
```

## Image Optimization

### Responsive Images

```php
// Add custom image sizes
function register_image_sizes() {
    add_image_size( 'featured-large', 1200, 675, true );  // 16:9
    add_image_size( 'featured-medium', 800, 450, true );
    add_image_size( 'featured-small', 400, 225, true );
    add_image_size( 'thumbnail-large', 600, 600, true );
}
add_action( 'after_setup_theme', 'register_image_sizes' );

// Output responsive images
function responsive_image( $attachment_id, $sizes = 'full', $class = '' ) {
    $image_srcset = wp_get_attachment_image_srcset( $attachment_id, $sizes );
    $image_sizes = wp_get_attachment_image_sizes( $attachment_id, $sizes );
    $image_src = wp_get_attachment_image_url( $attachment_id, $sizes );
    $image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

    return sprintf(
        '<img src="%s" srcset="%s" sizes="%s" alt="%s" class="%s" loading="lazy">',
        esc_url( $image_src ),
        esc_attr( $image_srcset ),
        esc_attr( $image_sizes ),
        esc_attr( $image_alt ),
        esc_attr( $class )
    );
}
```

### WebP Support

```php
function add_webp_support( $mimes ) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter( 'mime_types', 'add_webp_support' );

function webp_upload_preview( $result, $path ) {
    if ( $result === false ) {
        $displayable_image_types = array( IMAGETYPE_WEBP );
        $info = @getimagesize( $path );

        if ( empty( $info ) ) {
            $result = false;
        } elseif ( ! in_array( $info[2], $displayable_image_types ) ) {
            $result = false;
        } else {
            $result = true;
        }
    }

    return $result;
}
add_filter( 'file_is_displayable_image', 'webp_upload_preview', 10, 2 );
```

## Caching Strategies

### Object Caching

```php
// Cache expensive queries
function get_popular_posts( $count = 5 ) {
    $cache_key = 'popular_posts_' . $count;
    $posts = wp_cache_get( $cache_key );

    if ( false === $posts ) {
        $posts = new WP_Query( array(
            'posts_per_page' => $count,
            'meta_key'       => 'post_views',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ) );

        wp_cache_set( $cache_key, $posts, '', 3600 ); // Cache for 1 hour
    }

    return $posts;
}

// Flush cache on post update
function flush_popular_posts_cache( $post_id ) {
    wp_cache_delete( 'popular_posts_5' );
    wp_cache_delete( 'popular_posts_10' );
}
add_action( 'save_post', 'flush_popular_posts_cache' );
```

### Fragment Caching

```php
function cached_sidebar() {
    $cache_key = 'sidebar_content';
    $content = get_transient( $cache_key );

    if ( false === $content ) {
        ob_start();
        dynamic_sidebar( 'sidebar-1' );
        $content = ob_get_clean();

        set_transient( $cache_key, $content, 12 * HOUR_IN_SECONDS );
    }

    echo $content;
}
```

## Core Web Vitals

### Largest Contentful Paint (LCP)

```php
// Preload largest image
function preload_lcp_image() {
    if ( is_singular() && has_post_thumbnail() ) {
        $image_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
        echo '<link rel="preload" as="image" href="' . esc_url( $image_url ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'preload_lcp_image', 1 );
```

### Cumulative Layout Shift (CLS)

```css
/* Reserve space for images */
img {
    max-width: 100%;
    height: auto;
}

.article-card img {
    aspect-ratio: 16/9;
    object-fit: cover;
}

/* Prevent layout shift from ads */
.ad-container {
    min-height: 250px;
}
```

### First Input Delay (FID)

```javascript
// Optimize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Use passive listeners
    document.addEventListener('scroll', handleScroll, { passive: true });
    document.addEventListener('touchstart', handleTouch, { passive: true });
});

// Debounce expensive operations
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const debouncedResize = debounce(function() {
    // Expensive resize operations
}, 250);

window.addEventListener('resize', debouncedResize);
```

### Performance Monitoring

```php
function log_performance_metrics() {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $queries = get_num_queries();
        $timer = timer_stop( 0, 3 );
        $memory = size_format( memory_get_peak_usage() );

        error_log( sprintf(
            'Performance: %s queries in %s seconds using %s memory',
            $queries,
            $timer,
            $memory
        ) );
    }
}
add_action( 'wp_footer', 'log_performance_metrics' );
```

## Content Delivery Network (CDN)

```php
function cdn_rewrite_urls( $content ) {
    $cdn_url = 'https://cdn.yourdomain.com';
    $site_url = get_site_url();

    // Replace URLs for static assets
    $content = str_replace( $site_url . '/wp-content/uploads/', $cdn_url . '/wp-content/uploads/', $content );
    $content = str_replace( $site_url . '/wp-includes/', $cdn_url . '/wp-includes/', $content );

    return $content;
}
add_filter( 'the_content', 'cdn_rewrite_urls' );
add_filter( 'wp_get_attachment_url', function( $url ) {
    $cdn_url = 'https://cdn.yourdomain.com';
    $site_url = get_site_url();
    return str_replace( $site_url, $cdn_url, $url );
} );
```

## Security Headers

Add to functions.php:
```php
function add_security_headers() {
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'X-XSS-Protection: 1; mode=block' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
}
add_action( 'send_headers', 'add_security_headers' );
```
