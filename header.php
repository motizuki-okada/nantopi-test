<!--           
    | |       \ \  []    / /    [_______]        / /
[___ _____ ]   \ \  []  / /           / /       / /
    | |  | |    \ \    / /           / /       / /
    | |  | |     \_\  / /    [___________]    / /
    | |  | |          \ \           / /      /   /\ \
    | |  | |           \ \         / /      /  /   \ \　  / /
    |_|  | |            \ \        \ \     /  /     \ \　/ /
       [___]             \_\        \ \   /_/        \____/ 
                                     \_\
-->
<!doctype html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <?php wp_head(); ?>
    </head>
    <body <?php body_class();?>>  
        <header id="header" class="l-header">
            <?php $gakuson_search_icon_url = esc_url( get_template_directory_uri() . '/icon/searchIcon.png' ); ?>
            <div class="header_main">
                <a class="header_logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <img class="header_logoImg" src="<?php echo get_template_directory_uri();?>/img/nantopiTitleLogo.png" alt="NanTopiのバナーロゴ">
                </a>
                <div class="header_side">
                    <nav class="header_menu">
                        <?php
                            wp_nav_menu(
                            array(
                                'menu' => 'mainmenu',
                                'container' => '',
                                'menu_class' => 'header_list',
                                'menu_id' => '',
                                'fallback_cb' => ''
                            )
                            );
                        ?>
                    </nav>
                    <div class="header_search">
                        <button
                            class="header_searchTrigger js-header-search-toggle"
                            type="button"
                            aria-label="検索フォームを開く"
                            aria-controls="header-search-panel"
                            aria-expanded="false"
                        >
                            <span class="header_searchTriggerText">検索</span>
                            <img class="header_searchTriggerIcon" src="<?php echo $gakuson_search_icon_url; ?>" alt="">
                        </button>
                    </div>
                    <div class="header_spMenu">
                        <button
                            class="header_searchButton js-header-search-toggle"
                            type="button"
                            aria-label="検索フォームを開く"
                            aria-controls="header-search-panel"
                            aria-expanded="false"
                        >
                            <span class="header_searchButtonInner">
                                <svg class="header_searchButtonIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <circle cx="11" cy="11" r="6.5"></circle>
                                    <path d="M16 16L21 21"></path>
                                </svg>
                                <span class="header_searchButtonText">検索</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <div id="header-search-panel" class="slideInput" aria-hidden="true" aria-labelledby="header-search-title" hidden>
                <?php get_search_form( array( 'gakuson_context' => 'header-modal' ) ); ?>
            </div>
        </header>
