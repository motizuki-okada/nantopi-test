<?php get_header();?>
        <div class="l-empty"></div>
        <main id="main" class="l-main">
            <div class="backBoard">
                <div class="backBoard_item backBoard_item__1"></div>
                <div class="backBoard_item backBoard_item__2"></div>
                <div class="backBoard_item backBoard_item__3"></div>
            </div>
            <div class="l-mainContent">
                <div class="l-mainBody">
                    <article <?php post_class('l-article'); ?>>
                        <div class="post-header">
                            <div class="post-header-img">
                                <?php if (has_post_thumbnail()) {
                                    the_post_thumbnail('full');
                                } ?>
                            </div>
                            <!-- カテゴリ・タグ -->
                            <div class="post-taxonomy">
                                <div class="post-category">
                                    <?php the_category(' '); ?>
                                </div>
                                <div class="post-tags">
                                    <?php echo wp_kses_post(gakuson_get_post_tag_links_markup(get_the_ID())); ?>
                                </div>
                            </div>
                            <h1 class="post-title"><?php the_title(); ?></h1>
                            <div class="post-meta">
                                <time datetime="<?php echo get_the_date('Y-m-d'); ?>"><?php echo get_the_date(); ?></time>
                                <span class="post-author">
                                    <img class="post-author-icon" src="<?php echo get_template_directory_uri(); ?>/img/GakusonLogo.png" alt="author">
                                    <?php the_author(); ?>
                                </span>
                            </div>
                            <?php if (has_excerpt()): ?>
                                <div class="post-summary">
                                    <h3 class="post-summary-title">記事の概要</h3>
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="content">
                            <?php the_content(); ?>
                            <?php wp_link_pages(); // ページ分割用ページャー ?>
                        </div>
                        <?php echo gakuson_get_like_panel_markup(get_the_ID()); ?>
                    </article>
                    <?php get_sidebar();?>
                </div>

                <section class="l-wantToRead contentRail">
                    <?php
                    echo gakuson_get_section_title_markup(
                        'あわせて読みたい記事',
                        'icon/watchIcon.png'
                    );
                    ?>
                    <?php
                        // 裏側専用の featured タグは関連記事の関連キーからも外す。
                        $tag_ids = gakuson_get_public_post_tag_ids(get_the_ID());

                        if (! empty($tag_ids)) {
                        // タグに関連する記事を取得
                        $args = array(
                            'tag__in' => $tag_ids, // 現在のタグに一致する記事
                            'post__not_in' => array(get_the_ID()), // 現在の投稿を除外
                            'posts_per_page' => 3, // 最大3件
                            'orderby' => 'date', // 日付順
                            'order' => 'DESC', // 新しい順
                        );
                        } else {
                        // タグがない場合は新着順で取得
                            $args = array(
                                'post__not_in' => array(get_the_ID()), // 現在の投稿を除外
                                'posts_per_page' => 3, // 最大3件
                                'orderby' => 'date', // 日付順
                                'order' => 'DESC', // 新しい順
                            );
                        }
                        $query = new WP_Query($args);
                    ?>
                    <div class="article_content article_content--secondary">
                        <?php if ($query->have_posts()): ?>
                            <?php while ($query->have_posts()): $query->the_post(); ?>
                                <?php echo gakuson_get_article_card_markup(get_the_ID()); ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="article_emptyMessage">投稿がありません</p>
                        <?php endif; ?>
                    </div>
                    <?php wp_reset_postdata(); // クエリをリセット ?>
                </section>

                <?php echo gakuson_get_tag_directory_markup(array('section_class' => 'l-tag l-tag--secondary')); ?>
            </div>
        </main>
        <?php get_footer();?>
