<footer class="l-footer" id="footer">
    <div class="footer_inner">
        <h2 class="footer_title">Nanzan Topics!</h2>
        <div class="footer_main">
            <nav class="footer_nav" aria-label="Footer navigation">
                <?php
                if ( has_nav_menu( 'footer-nav' ) ) {
                    wp_nav_menu(
                        array(
                            'theme_location' => 'footer-nav',
                            'container'      => '',
                            'menu_class'     => 'footer_menu',
                            'menu_id'        => '',
                            'depth'          => 1,
                            'fallback_cb'    => false,
                        )
                    );
                } else {
                    $footer_fallback_menu_items = gakuson_get_footer_fallback_menu_items();
                    ?>
                    <ul class="footer_menu">
                        <?php foreach ( $footer_fallback_menu_items as $footer_menu_item ) : ?>
                            <li class="menu-item">
                                <a href="<?php echo esc_url( $footer_menu_item['url'] ); ?>">
                                    <?php echo esc_html( $footer_menu_item['title'] ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php
                }
                ?>
            </nav>
            <ul class="footer_list" aria-label="Nanzan Topics social links">
                <li class="footer_item">
                    <a class="footer_itemLink footer_itemLink__instagram" href="#" aria-label="Instagram">
                        <img class="footer_itemIcon" src="<?php echo esc_url( get_template_directory_uri() . '/icon/InstagramIcon.png' ); ?>" alt="" aria-hidden="true">
                    </a>
                </li>
                <li class="footer_item">
                    <a class="footer_itemLink footer_itemLink__x" href="#" aria-label="X">
                        <img class="footer_itemIcon" src="<?php echo esc_url( get_template_directory_uri() . '/icon/xIcon.png' ); ?>" alt="" aria-hidden="true">
                    </a>
                </li>
                <li class="footer_item footer_item__gakuson">
                    <a class="footer_itemLink footer_itemLink__gakuson" href="<?php echo esc_url( 'https://gakuson.com/' ); ?>" aria-label="がくそん公式サイト">
                        <img class="footer_itemIcon footer_itemIcon__gakuson" src="<?php echo esc_url( get_template_directory_uri() . '/img/gakuson_simple.png' ); ?>" alt="" aria-hidden="true">
                    </a>
                </li>
            </ul>
        </div>
        <div class="footer_copyRight">
            <small>&copy;<?php echo esc_html( wp_date( 'Y' ) ); ?> Nanzan Topics!</small>
        </div>
    </div>
</footer>        
<?php wp_footer();?>
</body>
</html>
