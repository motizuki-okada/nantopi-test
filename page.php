<?php get_header();?>
        <div class="l-empty"></div>
        <main id="main" class="l-main">
            <div class="backBoard">
                <div class="backBoard_item backBoard_item__1"></div>
            </div>
            <div class="l-mainContent">
                <article <?php post_class('l-article'); ?>>
                    <div class="content">
                        <?php the_content(); wp_link_pages();?>
                    </div>
                </article>
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
