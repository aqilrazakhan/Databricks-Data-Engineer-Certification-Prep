<?php
/**
 * The main template file
 *
 * @package TechPub
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <?php if ( have_posts() ) : ?>

            <header class="page-header">
                <?php
                if ( is_home() && ! is_front_page() ) :
                    ?>
                    <h1 class="page-title"><?php single_post_title(); ?></h1>
                    <?php
                else :
                    ?>
                    <h1 class="page-title"><?php esc_html_e( 'Latest Articles', 'techpub' ); ?></h1>
                    <?php
                endif;
                ?>
            </header><!-- .page-header -->

            <div class="articles-grid grid grid-3-col">
                <?php
                /* Start the Loop */
                while ( have_posts() ) :
                    the_post();
                    get_template_part( 'template-parts/content', 'card' );
                endwhile;
                ?>
            </div><!-- .articles-grid -->

            <?php
            techpub_pagination();

        else :
            get_template_part( 'template-parts/content', 'none' );
        endif;
        ?>
    </div><!-- .container -->
</main><!-- #primary -->

<?php
get_footer();
