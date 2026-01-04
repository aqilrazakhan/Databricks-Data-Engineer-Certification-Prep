<?php
/**
 * Template part for displaying article cards
 *
 * @package TechPub
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'article-card' ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
        <div class="card-image">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail( 'card-thumbnail' ); ?>
            </a>
            <?php
            $categories = get_the_category();
            if ( ! empty( $categories ) ) :
                ?>
                <span class="category-badge"><?php echo esc_html( $categories[0]->name ); ?></span>
                <?php
            endif;
            ?>
        </div><!-- .card-image -->
    <?php endif; ?>

    <div class="card-content">
        <?php the_title( '<h3 class="card-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h3>' ); ?>

        <p class="card-excerpt">
            <?php echo wp_trim_words( get_the_excerpt(), 20, '...' ); ?>
        </p>

        <div class="card-meta">
            <?php echo get_avatar( get_the_author_meta( 'ID' ), 32, '', '', array( 'class' => 'author-avatar' ) ); ?>
            <div class="meta-info">
                <span class="author-name"><?php the_author(); ?></span>
                <span class="meta-separator">·</span>
                <span class="publish-date"><?php echo get_the_date(); ?></span>
                <span class="meta-separator">·</span>
                <span class="reading-time"><?php echo techpub_reading_time(); ?> min read</span>
            </div>
        </div><!-- .card-meta -->
    </div><!-- .card-content -->
</article><!-- .article-card -->
