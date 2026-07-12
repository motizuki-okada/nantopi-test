<?php  
/*
 * GakusonTheme Functions
 */

require_once get_template_directory() . '/inc/gakuson-content-helpers.php';

/**
 * テーマのセットアップ
 */
function gakuson_theme_setup() {
    // アイキャッチ画像のサポート
    add_theme_support('post_thumbnails');
    // タイトルタグのサポート（header.phpの<title>タグを置き換えるため）
    add_theme_support('title-tag');
    // HTML5フォーム等のサポート
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'gakuson_theme_setup');

/**
 * ナビゲーションメニューの登録
 */
function gakuson_register_menus() {
    register_nav_menu('footer-nav', 'フッター');
    // header.phpで使用されている 'mainmenu' も登録しておくことを推奨
    register_nav_menu('mainmenu', 'メインメニュー');
}
add_action('init', 'gakuson_register_menus');

/**
 * CSSとJavaScriptの読み込み
 */
function gakuson_enqueue_assets() {
    $uri = get_template_directory_uri();

    // リセットCSS
    wp_enqueue_style('ress', 'https://unpkg.com/ress/dist/ress.min.css', array(), '1.0.0');

    // Google Fonts
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&display=swap', array(), null);

    // メインのスタイルシート（条件分岐）
    if (is_front_page()) {
        wp_enqueue_style('gakuson-style-pc', $uri . '/smacss/main/main-top.css', array('ress'), '1.0.1');
    }elseif (is_page()) {
        wp_enqueue_style('gakuson-style-pc', $uri . '/smacss/main/main-fixed.css', array('ress'), '1.0.1');
    }elseif (is_single()) {
        wp_enqueue_style('gakuson-style-pc', $uri . '/smacss/main/main-post.css', array('ress'), '1.0.1');
    }elseif(is_search() || is_category() || is_tag()) {
        wp_enqueue_style('gakuson-style-pc', $uri . '/smacss/main/main-category-tag.css', array('ress'), '1.0.1');
    }elseif (is_page('newindex')) {
        wp_enqueue_style('gakuson-style-pc', $uri . '/smacss/main/main-category-tag.css', array('ress'), '1.0.1');
    }elseif (is_404()) {
        wp_enqueue_style('gakuson-style-404-pc', $uri . '/smacss/main/main-404.css', array('ress'), '1.0.0');
    }

    // JavaScript
    // jQueryはWordPress同梱のものを使用
    wp_enqueue_script('gakuson-js-animation', $uri . '/js/script.js', array('jquery'), '1.0.0', true);
    wp_localize_script(
        'gakuson-js-animation',
        'gakusonLikeConfig',
        array(
            'ajaxUrl'             => admin_url('admin-ajax.php'),
            'action'              => 'gakuson_like_post',
            'nonce'               => wp_create_nonce('gakuson_like_post'),
            'workingLabel'        => '送信中...',
            'defaultButtonLabel'  => 'いいね！',
            'requestErrorMessage' => 'いいねの送信に失敗しました。時間をおいてもう一度お試しください。',
        )
    );
}
add_action('wp_enqueue_scripts', 'gakuson_enqueue_assets');

/**
 * Keep search results on posts while honoring the header modal filters.
 *
 * @param WP_Query $query Search query instance.
 * @return void
 */
function gakuson_customize_search_query($query) {
    if (is_admin() || ! $query->is_main_query() || ! $query->is_search()) {
        return;
    }

    $query->set('post_type', 'post');
    $query->set('ignore_sticky_posts', true);

    $category_slug = isset($_GET['category_name']) ? sanitize_title(wp_unslash($_GET['category_name'])) : '';
    $tag_slug      = isset($_GET['tag']) ? sanitize_title(wp_unslash($_GET['tag'])) : '';

    // Keep internal-only control tags out of the public search UI and query state.
    if (in_array($tag_slug, gakuson_get_internal_only_tag_slugs(), true)) {
        $tag_slug = '';
    }

    if ('' !== $category_slug) {
        $query->set('category_name', $category_slug);
    }

    if ('' !== $tag_slug) {
        $query->set('tag', $tag_slug);
    }
}
add_action('pre_get_posts', 'gakuson_customize_search_query');


/* 人気記事一覧
---------------------------------------------------------- */
/**
 * Ensure views-based UI can always read a numeric counter without parsing display text.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function gakuson_get_post_view_count($post_id) {
    $post_id = absint($post_id);

    if ($post_id <= 0) {
        return 0;
    }

    $count = get_post_meta($post_id, 'post_views_count', true);

    if ('' === $count) {
        return 0;
    }

    return max(0, (int) $count);
}

/**
 * Keep the legacy admin-column display helper intact while reusing the numeric counter.
 *
 * @param int $postID Post ID.
 * @return string
 */
function gakuson_get_post_views($postID){
    $count_key = 'post_views_count';
    $count     = get_post_meta($postID, $count_key, true);

    if($count==''){
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return "0 View";
    }

    return gakuson_get_post_view_count($postID) . ' Views';
}

/**
 * Count single-post views while keeping the existing `post_views_count` meta key.
 *
 * @param int $postID Post ID.
 * @return void
 */
function gakuson_set_post_views($postID) {
    if (!is_single()) return; // 投稿ページのみでカウント

    $count_key = 'post_views_count';

    $count = get_post_meta($postID, $count_key, true);

    if($count==''){
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '1');
    }else{
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}
// 閲覧数カウントのフック（wp_headで実行されていたものを適切なフックへ。通常はwp_headで良いが、条件分岐が必要）
add_action('wp_head', function() {
    if (is_single()) {
        gakuson_set_post_views(get_the_ID());
    }
});

// ヘッドから隣接する投稿リンクを削除
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

// 管理画面の記事一覧に閲覧数を表示
function gakuson_posts_column_views($defaults){
    $defaults['post_views'] = __('Views');
    return $defaults;
}
add_filter('manage_posts_columns', 'gakuson_posts_column_views');

function gakuson_posts_custom_column_views($column_name, $id){
    if ($column_name === 'post_views') {
        echo gakuson_get_post_views($id);
    }
}
add_action('manage_posts_custom_column', 'gakuson_posts_custom_column_views', 5, 2);

/**
 * ナビゲーションメニューの項目数を制限するフィルタ
 * header.phpから移動
 */
function gakuson_limit_wp_nav_menu_items($items, $args) {
    // 特定のメニュー位置やメニューIDに対してのみ適用する場合の条件を追加することを推奨
    // ここではheader.phpの実装に合わせて、単純に全てのwp_nav_menu_objectsに適用される書き方になっているが、
    // 影響範囲を限定するために、argsのmenu_classなどをチェックするほうが安全。
    // header.phpの呼び出し: 'menu_class' => 'dropdown' の場合のみなど。
    
    if (isset($args->menu_class) && $args->menu_class === 'dropdown') {
         return array_slice($items, 0, 4); // 最初の4つのメニューアイテムのみ取得
    }
    return $items;
}
add_filter('wp_nav_menu_objects', 'gakuson_limit_wp_nav_menu_items', 10, 2);
