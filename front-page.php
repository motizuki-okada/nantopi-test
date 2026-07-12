<?php get_header();?>
    <div class="l-empty"></div>
    <main id="main" class="l-main">
        <?php
        $featured_posts       = gakuson_get_featured_posts();
        $featured_post_count  = count($featured_posts);
        $featured_is_slider   = $featured_post_count > 1;
        $featured_viewport_id = 'featured-carousel-viewport';
        $featured_start_index = $featured_post_count > 2 ? 1 : 0;
        $front_page_list_step = 5;
        ?>
        <?php if ($featured_post_count > 0) : ?>
            <section
                class="featuredCarousel<?php echo $featured_is_slider ? ' featuredCarousel--slider' : ' featuredCarousel--static'; ?>"
                data-featured-carousel
                data-carousel-start-index="<?php echo esc_attr((string) $featured_start_index); ?>"
                aria-label="<?php echo esc_attr__('注目記事', 'gakuson'); ?>"
            >
                <div id="<?php echo esc_attr($featured_viewport_id); ?>" class="featuredCarousel_viewport" data-carousel-viewport>
                    <div class="featuredCarousel_track" data-carousel-track>
                    <?php foreach ($featured_posts as $featured_index => $featured_post) : ?>
                        <?php
                        $featured_title        = get_the_title($featured_post);
                        $featured_permalink    = get_permalink($featured_post);
                        $featured_thumbnail    = gakuson_get_post_thumbnail_url($featured_post, 'large');
                        $featured_author_name  = get_the_author_meta('display_name', (int) $featured_post->post_author);
                        $featured_date         = get_the_date('Y年n月j日', $featured_post);
                        $featured_category     = gakuson_get_post_primary_category_name($featured_post);
                        $featured_slide_id     = 'featured-slide-' . $featured_post->ID;
                        $featured_is_active    = $featured_index === $featured_start_index;
                        $featured_slide_class = implode(
                            ' ',
                            get_post_class(
                                array_filter(
                                    array(
                                        'featuredCarousel_slide',
                                        $featured_is_active ? 'is-active' : '',
                                    )
                                ),
                                $featured_post->ID
                            )
                        );

                        if ('' === $featured_thumbnail) {
                            $featured_thumbnail = get_template_directory_uri() . '/img/no-image.png';
                        }
                        ?>
                        <article
                            id="<?php echo esc_attr($featured_slide_id); ?>"
                            class="<?php echo esc_attr($featured_slide_class); ?>"
                            data-carousel-slide
                            data-slide-index="<?php echo esc_attr((string) $featured_index); ?>"
                            aria-current="<?php echo $featured_is_active ? 'true' : 'false'; ?>"
                        >
                            <a class="featuredCarousel_card" href="<?php echo esc_url($featured_permalink); ?>">
                                <div class="featuredCarousel_visual">
                                    <img
                                        class="featuredCarousel_image"
                                        src="<?php echo esc_url($featured_thumbnail); ?>"
                                        alt="<?php echo esc_attr($featured_title); ?>"
                                    >
                                    <div class="featuredCarousel_scrim" aria-hidden="true"></div>
                                    <div class="featuredCarousel_caption">
                                        <?php if ('' !== $featured_category) : ?>
                                            <span class="featuredCarousel_categoryBadge">
                                                <?php echo esc_html($featured_category); ?>
                                            </span>
                                        <?php endif; ?>
                                        <h3 class="featuredCarousel_slideTitle"><?php echo esc_html($featured_title); ?></h3>
                                        <div class="featuredCarousel_meta">
                                            <span class="featuredCarousel_author">
                                                <?php echo '' !== $featured_author_name ? esc_html($featured_author_name) : esc_html__('がくそん編集部', 'gakuson'); ?>
                                            </span>
                                            <time class="featuredCarousel_date" datetime="<?php echo esc_attr(get_the_date(DATE_W3C, $featured_post)); ?>">
                                                <?php echo esc_html($featured_date); ?>
                                            </time>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                    </div>
                    <?php if ($featured_is_slider) : ?>
                        <button
                            type="button"
                            class="featuredCarousel_navButton featuredCarousel_navButton--prev"
                            data-carousel-prev
                            aria-controls="<?php echo esc_attr($featured_viewport_id); ?>"
                            aria-label="<?php echo esc_attr__('前の注目記事を表示', 'gakuson'); ?>"
                        >
                            <span aria-hidden="true">&lt;</span>
                        </button>
                        <button
                            type="button"
                            class="featuredCarousel_navButton featuredCarousel_navButton--next"
                            data-carousel-next
                            aria-controls="<?php echo esc_attr($featured_viewport_id); ?>"
                            aria-label="<?php echo esc_attr__('次の注目記事を表示', 'gakuson'); ?>"
                        >
                            <span aria-hidden="true">&gt;</span>
                        </button>
                    <?php endif; ?>
                </div>
                <?php if ($featured_is_slider) : ?>
                    <div class="featuredCarousel_dots" aria-label="<?php echo esc_attr__('注目記事の切り替え', 'gakuson'); ?>">
                        <?php foreach ($featured_posts as $featured_index => $featured_post) : ?>
                            <?php $featured_slide_id = 'featured-slide-' . $featured_post->ID; ?>
                            <button
                                type="button"
                                class="featuredCarousel_dot<?php echo $featured_index === $featured_start_index ? ' is-active' : ''; ?>"
                                data-carousel-dot
                                data-slide-index="<?php echo esc_attr((string) $featured_index); ?>"
                                aria-controls="<?php echo esc_attr($featured_slide_id); ?>"
                                aria-current="<?php echo $featured_index === $featured_start_index ? 'true' : 'false'; ?>"
                                aria-label="<?php echo esc_attr(sprintf('注目記事 %d を表示', $featured_index + 1)); ?>"
                            ></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <section class="l-article">
            <div class="section_TitleConteiner">
                <img class="section_titleIcon article_titleIcon__latest" src="<?php echo get_template_directory_uri();?>/icon/watchIcon.png">
                <h2 class="section_title">新着記事</h2>
            </div>
            <div id="front-page-latest-list" class="article_content" data-load-more-list data-load-more-initial="<?php echo esc_attr((string) $front_page_list_step); ?>">
                <?php
                    // 初期表示件数を超えるカードは描画時点で隠し、JS前のちらつきを防ぐ
                    $args = array(
                        'posts_per_page' => -1,
                        'no_found_rows'  => true,
                    );
                    $query = new WP_Query($args);
                    $latest_index = 0;
                    ?>
                    <?php if ($query->have_posts()): ?>
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <?php
                            $post_id = get_the_ID();
                            $is_disabled_article = in_array($post_id, array(555, 553, 551), true);
                            $href                = $is_disabled_article ? '' : get_permalink();
                            ?>
                            <a
                                href="<?php echo esc_url($href); ?>"
                                <?php post_class('article_item'); ?>
                                data-load-more-item
                                <?php if ($latest_index >= $front_page_list_step) : ?>hidden<?php endif; ?>
                                <?php if ($is_disabled_article) : ?>style="pointer-events: none;"<?php endif; ?>
                            >
                                <div class="article_main">
                                    <div class="article_itemThumbnail">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('post_thumbnails'); ?>
                                        <?php else: ?>
                                            <img src="<?php echo get_template_directory_uri(); ?>/img/no-image.png" alt="No Image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="article_text">
                                        <h3 class="article_title"><?php the_title(); ?></h3>
                                        <div class="article_desc">
                                            <p class="article_date"><?php echo get_the_date(); ?></p>
                                            <p class="article_author"><?php echo get_the_author(); ?></p>
                                        </div>
                                        <?php echo gakuson_get_post_stats_markup($post_id, array('wrapper_class' => 'postStats--card')); ?>
                                        <?php echo gakuson_get_article_taxonomy_markup($post_id, 'pc'); ?>
                                    </div>
                                </div>
                                <?php echo gakuson_get_article_taxonomy_markup($post_id, 'sp'); ?>
                            </a>
                            <?php $latest_index++; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>投稿がありません</p>
                    <?php endif; ?>
                <?php wp_reset_postdata(); // クエリをリセット ?>
                <?php if ($query->post_count > $front_page_list_step) : ?>
                    <button
                        type="button"
                        class="article_moreLink"
                        data-load-more-button
                        aria-controls="front-page-latest-list"
                        aria-expanded="false"
                        hidden
                    >
                        もっと見る
                    </button>
                <?php endif; ?>
            </div>
        </section>
        <section class="l-article">
            <div class=section_TitleConteiner>
                <img class="section_titleIcon article_titleIcon__popu" src="<?php echo get_template_directory_uri();?>/icon/graphIcon.png">
                <h2 class="section_title">人気記事</h2>
            </div>
            <div id="front-page-popular-list" class="article_content" data-load-more-list data-load-more-initial="<?php echo esc_attr((string) $front_page_list_step); ?>">
                <?php
                    $popular_posts = new WP_Query(
                        gakuson_get_like_ranking_query_args(
                            array(
                                'posts_per_page' => -1,
                            )
                        )
                    );
                    $count = 1;
                    if ($popular_posts->have_posts()): ?>
                        <?php while ($popular_posts->have_posts()): $popular_posts->the_post(); ?>
                            <?php
                            echo gakuson_get_article_card_markup(
                                get_the_ID(),
                                array(
                                    'ranking'    => $count,
                                    'attributes' => array(
                                        'data-load-more-item' => true,
                                        'hidden'              => $count > $front_page_list_step,
                                    ),
                                )
                            );
                            ?>
                           <?php $count++;?>
                        <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?> <!-- WP_Query のデータをリセット -->
                <?php endif; ?>
                <?php if ($popular_posts->post_count > $front_page_list_step) : ?>
                    <button
                        type="button"
                        class="article_moreLink"
                        data-load-more-button
                        aria-controls="front-page-popular-list"
                        aria-expanded="false"
                        hidden
                    >
                        もっと見る
                    </button>
                <?php endif; ?>
            </div>
        </section>
        <section class="l-tag">
            <div class="section_TitleConteiner">
                <img class="section_titleIcon article_titleIcon__tag" src="<?php echo get_template_directory_uri();?>/icon/tagIcon.png">
                <h2 class="section_title">タグ一覧</h2>
            </div>
            <?php
            $tag_cloud_markup = wp_tag_cloud(
                gakuson_get_tag_cloud_args(
                    array(
                        'echo' => false,
                    )
                )
            );

            echo gakuson_format_tag_cloud_markup(
                $tag_cloud_markup,
                array(
                    'list_class'   => 'tag_list',
                    'item_class'   => 'tag_listItem',
                    'item_classes' => array(
                        'tag_listItem__blue',
                        'tag_listItem__yellow',
                    ),
                    'link_class'   => 'tag_itemLink',
                )
            );
            ?>
        </section>
    </main>
<?php get_footer();?>
