<aside class="l-popular">
    <section class="sidebarSection sidebarSection--popular">
        <?php
        echo gakuson_get_section_title_markup(
            '人気記事',
            'icon/graphIcon.png'
        );
        ?>
        <div class="feature-wrapper feature-wrapper__sidebar">
            <?php
            // サイドバーは順位表現を残しつつ、トップに寄せた見出しトーンで出す。
            $popular_posts = new WP_Query(
                gakuson_get_like_ranking_query_args(
                    array(
                        'posts_per_page' => 3,
                    )
                )
            );
            $count         = 1;

            if ($popular_posts->have_posts()) :
                while ($popular_posts->have_posts()) :
                    $popular_posts->the_post();

                    $popular_title_classes = array('feature_popularTitle', 'feature_popularTitle__sidebar');
                    $popular_label         = $count . 'th.TIPS';

                    if (1 === $count) {
                        $popular_title_classes[] = 'feature_popularTitle__st';
                        $popular_label           = '1st.TIPS';
                    } elseif (2 === $count) {
                        $popular_title_classes[] = 'feature_popularTitle__nd';
                        $popular_label           = '2nd.TIPS';
                    } elseif (3 === $count) {
                        $popular_title_classes[] = 'feature_popularTitle__rd';
                        $popular_label           = '3rd.TIPS';
                    }
                    ?>
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="<?php echo esc_attr(implode(' ', get_post_class(array('feature', 'feature__sidebar')))); ?>">
                        <h3 class="<?php echo esc_attr(implode(' ', $popular_title_classes)); ?>"><?php echo esc_html($popular_label); ?></h3>
                        <div class="Thumbnail Thumbnail__sidebar">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('post_thumbnails'); ?>
                            <?php else : ?>
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/img/no-image.png'); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                            <?php endif; ?>
                        </div>
                        <h3 class="feature_text feature_text__sidebar"><?php the_title(); ?></h3>
                        <div class="feature_text__small feature_text__small__sidebar">
                            <p><?php echo esc_html(get_the_date()); ?></p>
                            <div class="feature_textAcount feature_textAcount__sidebar">
                                <img class="feature_textIcon feature_textIcon__sidebar" src="<?php echo esc_url(get_template_directory_uri() . '/img/GakusonLogo.png'); ?>" alt="">
                                <p><?php echo esc_html(get_the_author()); ?></p>
                            </div>
                        </div>
                        <?php echo gakuson_get_post_stats_markup(get_the_ID(), array('wrapper_class' => 'postStats--sidebar')); ?>
                    </a>
                    <?php
                    $count++;
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </section>

    <section class="sidebarSection sidebarSection--links">
        <?php
        echo gakuson_get_section_title_markup(
            '公式リンク',
            'icon/watchIcon.png'
        );
        ?>
        <div class="advertisement">
            <a href="<?php echo esc_url('https://x.com/nanzan_gakuson'); ?>" class="advertisement_content">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/poster/gakuson.png'); ?>" class="advertisement_contentImg" alt="がくそん公式Xへのリンク">
            </a>
            <a href="<?php echo esc_url('https://www.instagram.com/activate_nrl/'); ?>" class="advertisement_content">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/poster/nrl.png'); ?>" class="advertisement_contentImg" alt="NRL公式Instagramへのリンク">
            </a>
        </div>
    </section>
</aside>
