<?php
get_header();

global $wp_query;

$tag_name        = single_tag_title('', false);
$tag_description = tag_description();
?>
<div class="l-empty"></div>
<main id="main" class="l-main">
    <div class="backBoard">
        <div class="backBoard_item backBoard_item__1"></div>
        <div class="backBoard_item backBoard_item__2"></div>
        <div class="backBoard_item backBoard_item__3"></div>
    </div>
    <div class="l-mainContent">
        <div class="l-mainBody">
            <article class="l-article archivePage archivePage--tag">
                <?php
                echo gakuson_get_section_title_markup(
                    sprintf('タグ: 「%s」', $tag_name),
                    'icon/tagIcon.png',
                    array(
                        'heading_tag' => 'h1',
                    )
                );
                ?>

                <?php if ('' !== trim(wp_strip_all_tags($tag_description))) : ?>
                    <div class="archivePage_intro">
                        <?php echo wp_kses_post(wpautop($tag_description)); ?>
                    </div>
                <?php endif; ?>

                <p class="archivePage_count">
                    <?php echo esc_html(number_format_i18n((int) $wp_query->found_posts)); ?>件の記事があります
                </p>

                <?php if (have_posts()) : ?>
                    <div class="article_content article_content--archive">
                        <?php while (have_posts()) : the_post(); ?>
                            <?php echo gakuson_get_article_card_markup(get_the_ID(), array('title_tag' => 'h2')); ?>
                        <?php endwhile; ?>
                    </div>
                <?php else : ?>
                    <div class="archivePage_empty">
                        <p class="archivePage_emptyText">投稿がありません</p>
                    </div>
                <?php endif; ?>

                <?php if ($wp_query->max_num_pages > 1) : ?>
                    <div class="archivePagination">
                        <?php
                        the_posts_pagination(
                            array(
                                'mid_size'           => 1,
                                'prev_text'          => '前へ',
                                'next_text'          => '次へ',
                                'screen_reader_text' => 'タグ記事のページ移動',
                            )
                        );
                        ?>
                    </div>
                <?php endif; ?>
            </article>
            <?php get_sidebar();?>
        </div>
        <?php echo gakuson_get_tag_directory_markup(array('section_class' => 'l-tag l-tag--secondary')); ?>
    </div>
</main>
<?php get_footer();?>
