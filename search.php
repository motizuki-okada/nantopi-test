<?php
get_header();

global $wp_query;

$current_keyword       = get_search_query();
$current_category_slug = isset($_GET['category_name']) ? sanitize_title(wp_unslash($_GET['category_name'])) : (string) get_query_var('category_name');
$current_tag_slug      = isset($_GET['tag']) ? sanitize_title(wp_unslash($_GET['tag'])) : (string) get_query_var('tag');

if (in_array($current_tag_slug, gakuson_get_internal_only_tag_slugs(), true)) {
    $current_tag_slug = '';
}

$current_category = '' !== $current_category_slug ? get_category_by_slug($current_category_slug) : null;
$current_tag      = '' !== $current_tag_slug ? get_term_by('slug', $current_tag_slug, 'post_tag') : null;
$active_filters   = array();

if ('' !== $current_keyword) {
    $active_filters[] = array(
        'label' => 'キーワード',
        'value' => $current_keyword,
    );
}

if ($current_category instanceof WP_Term) {
    $active_filters[] = array(
        'label' => 'カテゴリ',
        'value' => $current_category->name,
    );
}

if ($current_tag instanceof WP_Term) {
    $active_filters[] = array(
        'label' => 'タグ',
        'value' => $current_tag->name,
    );
}
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
            <article class="l-article archivePage archivePage--search">
                <?php
                echo gakuson_get_section_title_markup(
                    '検索結果',
                    'icon/searchIcon.png',
                    array(
                        'heading_tag' => 'h1',
                    )
                );
                ?>

                <p class="search_resultsCount">
                    <?php echo esc_html(number_format_i18n((int) $wp_query->found_posts)); ?>件の記事が見つかりました
                </p>

                <section class="search_resultsSummary" aria-labelledby="search-current-filters-title">
                    <h2 class="search_resultsSectionTitle" id="search-current-filters-title">現在の条件</h2>
                    <?php if (! empty($active_filters)) : ?>
                        <ul class="search_resultsConditions">
                            <?php foreach ($active_filters as $active_filter) : ?>
                                <li class="search_resultsCondition">
                                    <span class="search_resultsConditionLabel"><?php echo esc_html($active_filter['label']); ?></span>
                                    <span class="search_resultsConditionValue"><?php echo esc_html($active_filter['value']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="search_resultsConditionFallback">キーワード・カテゴリ・タグの指定なしで検索しています。</p>
                    <?php endif; ?>
                </section>

                <section class="search_resultsSummary search_resultsRefine" aria-labelledby="search-refine-title">
                    <h2 class="search_resultsSectionTitle" id="search-refine-title">条件を変えて検索</h2>
                    <?php get_search_form(); ?>
                </section>

                <?php if (have_posts()) : ?>
                    <div class="article_content article_content--archive">
                        <?php while (have_posts()) : the_post(); ?>
                            <?php echo gakuson_get_article_card_markup(get_the_ID(), array('title_tag' => 'h2')); ?>
                        <?php endwhile; ?>
                    </div>
                <?php else : ?>
                    <div class="search_resultsEmpty">
                        <p class="search_resultsEmptyText">条件に一致する記事は見つかりませんでした。</p>
                        <p class="search_resultsEmptyNote">キーワードやカテゴリ、タグを変更してもう一度検索してください。</p>
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
                                'screen_reader_text' => '検索結果のページ移動',
                            )
                        );
                        ?>
                    </div>
                <?php endif; ?>
            </article>
            <?php get_sidebar(); ?>
        </div>
        <?php echo gakuson_get_tag_directory_markup(array('section_class' => 'l-tag l-tag--secondary')); ?>
    </div>
</main>
<?php get_footer(); ?>
