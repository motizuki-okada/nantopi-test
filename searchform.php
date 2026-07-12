<?php
/**
 * Shared search form markup.
 */

$search_args = ( isset( $args ) && is_array( $args ) ) ? $args : array();
$context     = isset( $search_args['gakuson_context'] ) ? (string) $search_args['gakuson_context'] : 'default';
$field_prefix = 'header-modal' === $context ? 'header-search' : 'gakuson-search';

$current_category_slug = isset( $_GET['category_name'] ) ? sanitize_title( wp_unslash( $_GET['category_name'] ) ) : '';
$current_tag_slug      = isset( $_GET['tag'] ) ? sanitize_title( wp_unslash( $_GET['tag'] ) ) : '';

if ( in_array( $current_tag_slug, gakuson_get_internal_only_tag_slugs(), true ) ) {
    $current_tag_slug = '';
}

$categories            = get_categories(
    array(
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    )
);
$tags                  = get_tags(
    array(
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'exclude'    => gakuson_get_internal_only_tag_ids(),
    )
);

if ( is_wp_error( $categories ) ) {
    $categories = array();
}

if ( is_wp_error( $tags ) ) {
    $tags = array();
}
?>
<form role="search" method="get" class="searchform gakuson-search-form gakuson-search-form--<?php echo esc_attr( $context ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <?php if ( 'header-modal' === $context ) : ?>
        <div class="header_searchPanelHeader">
            <p class="header_searchPanelTitle" id="header-search-title">記事を検索</p>
            <button class="header_searchClose" type="button" data-search-close>閉じる</button>
        </div>
    <?php endif; ?>

    <div class="header_searchFields">
        <div class="header_searchField">
            <label class="header_searchLabel" for="<?php echo esc_attr( $field_prefix ); ?>-keyword">キーワード</label>
            <input
                class="header_searchInput header_searchInput__modal"
                id="<?php echo esc_attr( $field_prefix ); ?>-keyword"
                type="search"
                name="s"
                value="<?php echo esc_attr( get_search_query() ); ?>"
                placeholder="キーワードを入力"
            >
        </div>

        <div class="header_searchField">
            <label class="header_searchLabel" for="<?php echo esc_attr( $field_prefix ); ?>-category">カテゴリ</label>
            <select class="header_searchSelect" id="<?php echo esc_attr( $field_prefix ); ?>-category" name="category_name">
                <option value="">すべてのカテゴリ</option>
                <?php foreach ( $categories as $category ) : ?>
                    <option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $current_category_slug, $category->slug ); ?>>
                        <?php echo esc_html( $category->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="header_searchField">
            <label class="header_searchLabel" for="<?php echo esc_attr( $field_prefix ); ?>-tag">タグ</label>
            <select class="header_searchSelect" id="<?php echo esc_attr( $field_prefix ); ?>-tag" name="tag">
                <option value="">すべてのタグ</option>
                <?php foreach ( $tags as $tag ) : ?>
                    <option value="<?php echo esc_attr( $tag->slug ); ?>" <?php selected( $current_tag_slug, $tag->slug ); ?>>
                        <?php echo esc_html( $tag->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="header_searchActions">
        <button class="kensakuButton kensakuButton__modal" type="submit">検索する</button>
    </div>
</form>
