<?php
/**
 * The template for displaying single posts
 *
 * @package TechPub
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php
                    if ( has_post_thumbnail() ) :
                        ?>
                        <div class="featured-image">
                            <?php the_post_thumbnail( 'featured-large' ); ?>
                        </div>
                        <?php
                    endif;

                    the_title( '<h1 class="entry-title">', '</h1>' );
                    ?>

                    <div class="entry-meta">
                        <span class="author">
                            <?php
                            echo get_avatar( get_the_author_meta( 'ID' ), 32, '', '', array( 'class' => 'author-avatar' ) );
                            ?>
                            <span class="author-name"><?php the_author_posts_link(); ?></span>
                        </span>
                        <span class="meta-separator">·</span>
                        <span class="posted-on">
                            <time datetime="<?php echo get_the_date( 'c' ); ?>">
                                <?php echo get_the_date(); ?>
                            </time>
                        </span>
                        <span class="meta-separator">·</span>
                        <span class="reading-time">
                            <?php echo techpub_reading_time(); ?> <?php esc_html_e( 'min read', 'techpub' ); ?>
                        </span>
                        <?php if ( get_the_category() ) : ?>
                            <span class="meta-separator">·</span>
                            <span class="categories">
                                <?php the_category( ', ' ); ?>
                            </span>
                        <?php endif; ?>
                    </div><!-- .entry-meta -->
                </header><!-- .entry-header -->

                <div class="entry-content">
                    <?php
                    the_content(
                        sprintf(
                            wp_kses(
                                /* translators: %s: Name of current post */
                                __( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'techpub' ),
                                array(
                                    'span' => array(
                                        'class' => array(),
                                    ),
                                )
                            ),
                            wp_kses_post( get_the_title() )
                        )
                    );

                    wp_link_pages(
                        array(
                            'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'techpub' ),
                            'after'  => '</div>',
                        )
                    );
                    ?>
                </div><!-- .entry-content -->

                <footer class="entry-footer">
                    <?php
                    if ( get_the_tags() ) :
                        ?>
                        <div class="tags-links">
                            <strong><?php esc_html_e( 'Tags:', 'techpub' ); ?></strong>
                            <?php the_tags( '', ', ', '' ); ?>
                        </div>
                        <?php
                    endif;
                    ?>
                </footer><!-- .entry-footer -->
            </article><!-- #post-<?php the_ID(); ?> -->

            <?php
            // Author bio
            if ( get_the_author_meta( 'description' ) ) :
                ?>
                <div class="author-bio">
                    <div class="author-avatar">
                        <?php echo get_avatar( get_the_author_meta( 'ID' ), 80 ); ?>
                    </div>
                    <div class="author-info">
                        <h3 class="author-title"><?php the_author(); ?></h3>
                        <p class="author-description"><?php the_author_meta( 'description' ); ?></p>
                        <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" class="author-link">
                            <?php esc_html_e( 'View all posts', 'techpub' ); ?>
                        </a>
                    </div>
                </div>
                <?php
            endif;

            // Comments
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;

        endwhile; // End of the loop.
        ?>
    </div><!-- .container -->
</main><!-- #primary -->

<?php
get_footer();
