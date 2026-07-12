<?php
/**
 * Shared helper functions for featured content and taxonomy output.
 */

/**
 * Centralize the featured tag slug so carousel sources stay in one place.
 *
 * @return string
 */
function gakuson_get_featured_tag_slug() {
    return (string) apply_filters('gakuson_featured_tag_slug', 'featured');
}

/**
 * Keep the external preview source tag configurable without leaking it into templates.
 *
 * @return string
 */
function gakuson_get_nantopi_pick_tag_slug() {
    return (string) apply_filters('gakuson_nantopi_pick_tag_slug', 'nantopi-pick');
}

/**
 * Keep internal processing tags out of public UI by default.
 *
 * @return string[]
 */
function gakuson_get_internal_only_tag_slugs() {
    $slugs = apply_filters(
        'gakuson_internal_only_tag_slugs',
        array(
            gakuson_get_featured_tag_slug(),
            gakuson_get_nantopi_pick_tag_slug(),
        )
    );

    $slugs = array_map('sanitize_title', (array) $slugs);
    $slugs = array_values(array_unique(array_filter($slugs)));

    return $slugs;
}

/**
 * Keep selected tags out of article-facing tag chips while allowing API payload usage.
 *
 * @return string[]
 */
function gakuson_get_hidden_article_tag_slugs() {
    $slugs = apply_filters(
        'gakuson_hidden_article_tag_slugs',
        array_merge(
            gakuson_get_internal_only_tag_slugs(),
            array('isKk')
        )
    );

    $slugs = array_map('sanitize_title', (array) $slugs);
    $slugs = array_values(array_unique(array_filter($slugs)));

    return $slugs;
}

/**
 * Resolve internal-only tag IDs once per request for cloud/search exclusions.
 *
 * @return int[]
 */
function gakuson_get_internal_only_tag_ids() {
    static $tag_ids = null;

    if (null !== $tag_ids) {
        return $tag_ids;
    }

    $tag_ids = array();

    foreach (gakuson_get_internal_only_tag_slugs() as $slug) {
        $term = get_term_by('slug', $slug, 'post_tag');

        if ($term instanceof WP_Term) {
            $tag_ids[] = (int) $term->term_id;
        }
    }

    $tag_ids = array_values(array_unique(array_filter($tag_ids)));

    return $tag_ids;
}

/**
 * Keep default footer-page slugs centralized for fallback navigation and setup docs.
 *
 * @return array<int, array{title:string, slug:string}>
 */
function gakuson_get_footer_page_specs() {
    $pages = apply_filters(
        'gakuson_footer_page_specs',
        array(
            array(
                'title' => 'このサイトについて',
                'slug'  => 'about',
            ),
            array(
                'title' => '免責事項',
                'slug'  => 'disclaimer',
            ),
            array(
                'title' => 'お問い合わせ',
                'slug'  => 'contact',
            ),
        )
    );

    $normalized_pages = array();

    foreach ((array) $pages as $page) {
        if (! is_array($page)) {
            continue;
        }

        $title = isset($page['title']) ? trim((string) $page['title']) : '';
        $slug  = isset($page['slug']) ? sanitize_title((string) $page['slug']) : '';

        if ('' === $title || '' === $slug) {
            continue;
        }

        $normalized_pages[] = array(
            'title' => $title,
            'slug'  => $slug,
        );
    }

    return $normalized_pages;
}

/**
 * Build footer fallback links from the shared slug definitions.
 *
 * If the matching page already exists, use its permalink so permalink settings stay authoritative.
 * Otherwise, fall back to the expected top-level slug URL.
 *
 * @return array<int, array{title:string, slug:string, url:string}>
 */
function gakuson_get_footer_fallback_menu_items() {
    $menu_items = array();

    foreach (gakuson_get_footer_page_specs() as $page) {
        $page_object = get_page_by_path($page['slug']);
        $url         = home_url(user_trailingslashit($page['slug']));

        if ($page_object instanceof WP_Post) {
            $url = get_permalink($page_object);
        }

        $menu_items[] = array(
            'title' => $page['title'],
            'slug'  => $page['slug'],
            'url'   => (string) $url,
        );
    }

    return $menu_items;
}

/**
 * Reuse the same public-facing tag list anywhere the theme renders post tags.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @param array            $args Optional filters such as excluded slugs.
 * @return WP_Term[]
 */
function gakuson_get_public_post_tags($post = null, $args = array()) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return array();
    }

    $args = wp_parse_args(
        $args,
        array(
            'exclude_slugs' => gakuson_get_internal_only_tag_slugs(),
        )
    );

    $tags = get_the_tags($post->ID);

    if (empty($tags) || is_wp_error($tags)) {
        return array();
    }

    $excluded_slugs = array_map('sanitize_title', (array) $args['exclude_slugs']);

    if (empty($excluded_slugs)) {
        return array_values($tags);
    }

    $public_tags = array();

    foreach ($tags as $tag) {
        if (in_array($tag->slug, $excluded_slugs, true)) {
            continue;
        }

        $public_tags[] = $tag;
    }

    return array_values($public_tags);
}

/**
 * Give related-post queries the same public tag set used by visible UI.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @param array            $args Optional filters such as excluded slugs.
 * @return int[]
 */
function gakuson_get_public_post_tag_ids($post = null, $args = array()) {
    $tags = gakuson_get_public_post_tags($post, $args);

    if (empty($tags)) {
        return array();
    }

    return array_values(array_map('intval', wp_list_pluck($tags, 'term_id')));
}

/**
 * Keep like-related storage keys centralized for queries, Ajax, and UI helpers.
 *
 * @return string
 */
function gakuson_get_like_count_meta_key() {
    return 'gakuson_like_count';
}

/**
 * Track per-browser like limits through one cookie name.
 *
 * @return string
 */
function gakuson_get_like_cookie_name() {
    return 'gakuson_like_counts';
}

/**
 * Expose the per-visitor like ceiling from one helper.
 *
 * @return int
 */
function gakuson_get_like_max_per_visitor() {
    return (int) apply_filters('gakuson_like_max_per_visitor', 10);
}

/**
 * Keep the browser-side like cookie lifetime configurable from one place.
 *
 * @return int
 */
function gakuson_get_like_cookie_lifetime() {
    return (int) apply_filters('gakuson_like_cookie_lifetime', YEAR_IN_SECONDS);
}

/**
 * Return a numeric like count without forcing templates to parse raw post meta.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return int
 */
function gakuson_get_post_like_count($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return 0;
    }

    $count = get_post_meta($post->ID, gakuson_get_like_count_meta_key(), true);

    if ('' === $count) {
        return 0;
    }

    return max(0, (int) $count);
}

/**
 * Ensure ranking queries can safely sort every post by a numeric like count.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function gakuson_ensure_post_like_meta($post_id) {
    $post_id = absint($post_id);

    if ($post_id <= 0 || 'post' !== get_post_type($post_id)) {
        return;
    }

    if ('' === get_post_meta($post_id, gakuson_get_like_count_meta_key(), true)) {
        add_post_meta($post_id, gakuson_get_like_count_meta_key(), '0', true);
    }
}

/**
 * Parse and clamp browser-side per-post like counters from the visitor cookie.
 *
 * @return array<int, int>
 */
function gakuson_get_like_cookie_counts() {
    $cookie_name = gakuson_get_like_cookie_name();

    if (empty($_COOKIE[$cookie_name])) {
        return array();
    }

    $raw_cookie_value = rawurldecode((string) wp_unslash($_COOKIE[$cookie_name]));
    $decoded_counts   = json_decode($raw_cookie_value, true);

    if (! is_array($decoded_counts)) {
        return array();
    }

    $sanitized_counts = array();
    $max_likes        = gakuson_get_like_max_per_visitor();

    foreach ($decoded_counts as $post_id => $count) {
        $post_id = absint($post_id);

        if ($post_id <= 0) {
            continue;
        }

        $sanitized_counts[$post_id] = min($max_likes, max(0, (int) $count));
    }

    return $sanitized_counts;
}

/**
 * Read the current visitor's like count for a single post.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return int
 */
function gakuson_get_visitor_like_count($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return 0;
    }

    $cookie_counts = gakuson_get_like_cookie_counts();

    if (! isset($cookie_counts[$post->ID])) {
        return 0;
    }

    return max(0, (int) $cookie_counts[$post->ID]);
}

/**
 * Keep the remaining-like message consistent between initial render and Ajax updates.
 *
 * @param int $remaining_likes Remaining likes for the current visitor on a post.
 * @return string
 */
function gakuson_get_like_remaining_message($remaining_likes) {
    $remaining_likes = max(0, (int) $remaining_likes);

    if ($remaining_likes <= 0) {
        return 'この端末では上限に達しました';
    }

    return sprintf('この端末ではあと%d回いいねできます', $remaining_likes);
}

/**
 * Return the remaining likes allowed for the current visitor on a post.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return int
 */
function gakuson_get_remaining_like_count($post = null) {
    $max_likes = gakuson_get_like_max_per_visitor();

    return max(0, $max_likes - gakuson_get_visitor_like_count($post));
}

/**
 * Persist the sanitized per-post like counters back to the browser cookie.
 *
 * @param array<int, int> $counts Like counts keyed by post ID.
 * @return bool
 */
function gakuson_set_like_cookie_counts($counts) {
    if (headers_sent()) {
        return false;
    }

    $sanitized_counts = array();
    $max_likes        = gakuson_get_like_max_per_visitor();

    foreach ((array) $counts as $post_id => $count) {
        $post_id = absint($post_id);

        if ($post_id <= 0) {
            continue;
        }

        $sanitized_counts[$post_id] = min($max_likes, max(0, (int) $count));
    }

    $cookie_name  = gakuson_get_like_cookie_name();
    $cookie_value = rawurlencode(wp_json_encode($sanitized_counts));
    $expiration   = time() + gakuson_get_like_cookie_lifetime();
    $cookie_set   = setcookie(
        $cookie_name,
        $cookie_value,
        array(
            'expires'  => $expiration,
            'path'     => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
            'domain'   => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        )
    );

    if ($cookie_set) {
        $_COOKIE[$cookie_name] = $cookie_value;
    }

    return $cookie_set;
}

/**
 * Increment a post's stored like counter and return the updated total.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return int
 */
function gakuson_increment_post_like_count($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return 0;
    }

    gakuson_ensure_post_like_meta($post->ID);

    $updated_count = gakuson_get_post_like_count($post) + 1;
    update_post_meta($post->ID, gakuson_get_like_count_meta_key(), (string) $updated_count);

    return $updated_count;
}

/**
 * Reuse one ranking query baseline wherever the theme shows "popular" posts.
 *
 * @param array $overrides Optional query overrides.
 * @return array
 */
function gakuson_get_like_ranking_query_args($overrides = array()) {
    $defaults = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'meta_key'            => gakuson_get_like_count_meta_key(),
        'orderby'             => array(
            'meta_value_num' => 'DESC',
            'date'           => 'DESC',
        ),
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    );

    return wp_parse_args($overrides, $defaults);
}

/**
 * Keep the featured post limit aligned across templates and endpoint payloads.
 *
 * @return int
 */
function gakuson_get_featured_post_limit() {
    return (int) apply_filters('gakuson_featured_post_limit', 5);
}

/**
 * Keep the external preview endpoint focused on the newest tagged post.
 *
 * @return int
 */
function gakuson_get_picks_post_limit() {
    return (int) apply_filters('gakuson_picks_post_limit', 1);
}

/**
 * Use one transient key for every carousel payload consumer.
 *
 * @return string
 */
function gakuson_get_carousel_cache_key() {
    return (string) apply_filters('gakuson_carousel_cache_key', 'gakuson_carousel_payload_v1');
}

/**
 * Expose cache TTL from one helper until the final value is agreed.
 *
 * @return int
 */
function gakuson_get_carousel_cache_ttl() {
    return (int) apply_filters('gakuson_carousel_cache_ttl', 5 * MINUTE_IN_SECONDS);
}

/**
 * Use a dedicated transient key so picks payloads do not collide with the carousel cache.
 *
 * @return string
 */
function gakuson_get_picks_cache_key() {
    return (string) apply_filters('gakuson_picks_cache_key', 'gakuson_picks_payload_v1');
}

/**
 * Read the picks cache TTL from wp-config when available, with a safe default for local work.
 *
 * @return int
 */
function gakuson_get_picks_cache_ttl() {
    $default_ttl = 5 * MINUTE_IN_SECONDS;

    if (defined('GAKUSON_PICKS_CACHE_TTL')) {
        $configured_ttl = (int) GAKUSON_PICKS_CACHE_TTL;

        if ($configured_ttl >= 0) {
            $default_ttl = $configured_ttl;
        }
    }

    return (int) apply_filters('gakuson_picks_cache_ttl', $default_ttl);
}

/**
 * Reuse the same featured-post query for front-page rendering and the REST layer.
 *
 * @param array $args Optional query overrides.
 * @return WP_Post[]
 */
function gakuson_get_featured_posts($args = array()) {
    $defaults = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => gakuson_get_featured_post_limit(),
        'tag_slug__in'        => array(gakuson_get_featured_tag_slug()),
        'ignore_sticky_posts' => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
    );

    return get_posts(wp_parse_args($args, $defaults));
}

/**
 * Query the tagged post used by the external preview endpoint.
 *
 * @param array $args Optional query overrides.
 * @return WP_Post[]
 */
function gakuson_get_nantopi_pick_posts($args = array()) {
    $defaults = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => gakuson_get_picks_post_limit(),
        'tag_slug__in'        => array(gakuson_get_nantopi_pick_tag_slug()),
        'ignore_sticky_posts' => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
    );

    return get_posts(wp_parse_args($args, $defaults));
}

/**
 * Keep the excerpt rule shared between the WP theme and future JSON output.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return string
 */
function gakuson_get_carousel_excerpt($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return '';
    }

    $manual_excerpt = trim((string) $post->post_excerpt);

    if ('' !== $manual_excerpt) {
        return wp_strip_all_tags($manual_excerpt);
    }

    $length  = (int) apply_filters('gakuson_carousel_excerpt_length', 110, $post);
    $content = wp_strip_all_tags(strip_shortcodes((string) $post->post_content));

    if ('' === $content) {
        return '';
    }

    return wp_html_excerpt($content, $length, '...');
}

/**
 * Use the first assigned category as the shared carousel category label.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return string
 */
function gakuson_get_post_primary_category_name($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return '';
    }

    $categories = get_the_category($post->ID);

    if (empty($categories) || is_wp_error($categories)) {
        return '';
    }

    return (string) $categories[0]->name;
}

/**
 * Flatten tag objects to plain text for templates and JSON payloads.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @param array            $args Optional filters such as excluded slugs.
 * @return string[]
 */
function gakuson_get_post_tag_names($post = null, $args = array()) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return array();
    }

    $args = wp_parse_args(
        $args,
        array(
            'exclude_slugs' => array(),
        )
    );

    if (empty($args['exclude_slugs'])) {
        $args['exclude_slugs'] = gakuson_get_internal_only_tag_slugs();
    }

    $tags      = gakuson_get_public_post_tags($post, $args);
    $tag_names = array();

    foreach ($tags as $tag) {
        $tag_names[] = (string) $tag->name;
    }

    return array_values(array_filter($tag_names));
}

/**
 * Render public tag links without exposing internal-only processing tags.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @param array            $args Optional separator or excluded slugs.
 * @return string
 */
function gakuson_get_post_tag_links_markup($post = null, $args = array()) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return '';
    }

    $args = wp_parse_args(
        $args,
        array(
            'separator'     => ' ',
            'exclude_slugs' => gakuson_get_hidden_article_tag_slugs(),
        )
    );

    $tags = gakuson_get_public_post_tags($post, $args);

    if (empty($tags)) {
        return '';
    }

    $links = array();

    foreach ($tags as $tag) {
        $tag_link = get_term_link($tag);

        if (is_wp_error($tag_link)) {
            continue;
        }

        $links[] = sprintf(
            '<a href="%1$s" rel="tag">%2$s</a>',
            esc_url($tag_link),
            esc_html($tag->name)
        );
    }

    return implode((string) $args['separator'], $links);
}

/**
 * Keep the top-page taxonomy chips consistent without forcing a shared card partial.
 *
 * @param WP_Post|int|null $post    Optional post object or ID.
 * @param string           $context Optional context suffix such as pc or sp.
 * @return string
 */
function gakuson_get_article_taxonomy_markup($post = null, $context = '') {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return '';
    }

    $wrapper_classes = array('article_taxonomy');

    if ('' !== $context) {
        $wrapper_classes[] = 'article_taxonomy__' . sanitize_html_class($context);
    }

    $category_name = gakuson_get_post_primary_category_name($post);
    $tag_names     = gakuson_get_post_tag_names(
        $post,
        array(
            'exclude_slugs' => gakuson_get_hidden_article_tag_slugs(),
        )
    );

    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
        <div class="article_taxonomyInner">
            <div class="article_taxonomyItem">
                <span class="article_taxonomyItemText">
                    <?php echo '' !== $category_name ? esc_html('#' . $category_name) : esc_html('カテゴリなし'); ?>
                </span>
            </div>
            <div class="article_taxonomyItem article_taxonomyItem__category">
                <?php if (empty($tag_names)) : ?>
                    <span class="article_taxonomyItemText article_taxonomyItemText__category"><?php echo esc_html('タグなし'); ?></span>
                <?php else : ?>
                    <?php foreach ($tag_names as $tag_name) : ?>
                        <span class="article_taxonomyItemText article_taxonomyItemText__category"><?php echo esc_html($tag_name); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php

    return trim(ob_get_clean());
}

/**
 * Keep thumbnail lookup in one place so fallback behavior can change later.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @param string           $size Image size name.
 * @return string
 */
function gakuson_get_post_thumbnail_url($post = null, $size = 'full') {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return '';
    }

    $thumbnail_url = get_the_post_thumbnail_url($post, $size);

    return $thumbnail_url ? (string) $thumbnail_url : '';
}

/**
 * Build the minimal item payload shared by the future endpoint and front-page UI.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return array<string, mixed>
 */
function gakuson_build_carousel_item_response($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return array();
    }

    $item = array(
        'id'           => (int) $post->ID,
        'title'        => html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES, get_bloginfo('charset')),
        'url'          => (string) get_permalink($post),
        'category'     => gakuson_get_post_primary_category_name($post),
        'tags'         => gakuson_get_post_tag_names($post),
        'thumbnailUrl' => gakuson_get_post_thumbnail_url($post),
        'excerpt'      => gakuson_get_carousel_excerpt($post),
        'updatedAt'    => (string) get_post_modified_time(DATE_ATOM, true, $post),
    );

    return apply_filters('gakuson_carousel_item_response', $item, $post);
}

/**
 * Keep the endpoint response shape defined once before the route is added.
 *
 * @param WP_Post[]|null $posts Optional posts to shape.
 * @return array<string, mixed>
 */
function gakuson_build_carousel_response($posts = null) {
    if (null === $posts) {
        $posts = gakuson_get_featured_posts();
    }

    $items = array();

    foreach ($posts as $post) {
        $item = gakuson_build_carousel_item_response($post);

        if (! empty($item)) {
            $items[] = $item;
        }
    }

    $response = array(
        'items' => $items,
    );

    return apply_filters('gakuson_carousel_response', $response, $posts);
}

/**
 * Read the cached response so route code does not duplicate transient logic later.
 *
 * @return array<string, mixed>|null
 */
function gakuson_get_cached_carousel_response() {
    $cached_response = get_transient(gakuson_get_carousel_cache_key());

    return is_array($cached_response) ? $cached_response : null;
}

/**
 * Write carousel payloads through one cache helper so TTL changes stay centralized.
 *
 * @param array $response Response data.
 * @return bool
 */
function gakuson_set_cached_carousel_response($response) {
    if (! is_array($response)) {
        return false;
    }

    $ttl = gakuson_get_carousel_cache_ttl();

    if ($ttl <= 0) {
        return false;
    }

    return set_transient(gakuson_get_carousel_cache_key(), $response, $ttl);
}

/**
 * Clear the shared carousel cache when content changes.
 *
 * @return bool
 */
function gakuson_delete_cached_carousel_response() {
    return delete_transient(gakuson_get_carousel_cache_key());
}

/**
 * Give later endpoint code a single entrypoint for building or refreshing payloads.
 *
 * @param bool $force_refresh Whether to bypass transient cache.
 * @return array<string, mixed>
 */
function gakuson_get_carousel_response($force_refresh = false) {
    if (! $force_refresh) {
        $cached_response = gakuson_get_cached_carousel_response();

        if (is_array($cached_response)) {
            return $cached_response;
        }
    }

    $response = gakuson_build_carousel_response();
    gakuson_set_cached_carousel_response($response);

    return $response;
}

/**
 * Build the smallest payload needed by the external preview consumer.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return array<string, mixed>
 */
function gakuson_build_picks_item_response($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return array();
    }

    $item = array(
        'title' => html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES, get_bloginfo('charset')),
        'tags'  => gakuson_get_post_tag_names(
            $post,
            array(
                'exclude_slugs' => array(gakuson_get_nantopi_pick_tag_slug()),
            )
        ),
        'image' => gakuson_get_post_thumbnail_url($post),
        'url'   => (string) get_permalink($post),
    );

    return apply_filters('gakuson_picks_item_response', $item, $post);
}

/**
 * Keep the external preview response shape defined in one place.
 *
 * @param WP_Post[]|null $posts Optional posts to shape.
 * @return array<string, mixed>
 */
function gakuson_build_picks_response($posts = null) {
    if (null === $posts) {
        $posts = gakuson_get_nantopi_pick_posts();
    }

    $items = array();

    foreach ($posts as $post) {
        $item = gakuson_build_picks_item_response($post);

        if (! empty($item)) {
            $items[] = $item;
        }
    }

    $response = array(
        'items' => $items,
    );

    return apply_filters('gakuson_picks_response', $response, $posts);
}

/**
 * Read the cached picks response so the route callback stays thin.
 *
 * @return array<string, mixed>|null
 */
function gakuson_get_cached_picks_response() {
    $cached_response = get_transient(gakuson_get_picks_cache_key());

    return is_array($cached_response) ? $cached_response : null;
}

/**
 * Write picks payloads through one cache helper to keep TTL handling consistent.
 *
 * @param array $response Response data.
 * @return bool
 */
function gakuson_set_cached_picks_response($response) {
    if (! is_array($response)) {
        return false;
    }

    $ttl = gakuson_get_picks_cache_ttl();

    if ($ttl <= 0) {
        return false;
    }

    return set_transient(gakuson_get_picks_cache_key(), $response, $ttl);
}

/**
 * Clear the picks cache whenever source content changes.
 *
 * @return bool
 */
function gakuson_delete_cached_picks_response() {
    return delete_transient(gakuson_get_picks_cache_key());
}

/**
 * Give the route callback one entrypoint for building or refreshing picks payloads.
 *
 * @param bool $force_refresh Whether to bypass transient cache.
 * @return array<string, mixed>
 */
function gakuson_get_picks_response($force_refresh = false) {
    if (! $force_refresh) {
        $cached_response = gakuson_get_cached_picks_response();

        if (is_array($cached_response)) {
            return $cached_response;
        }
    }

    $response = gakuson_build_picks_response();
    gakuson_set_cached_picks_response($response);

    return $response;
}

/**
 * Normalize origin strings so wp-config values and request headers compare reliably.
 *
 * @param string $origin Raw origin string.
 * @return string
 */
function gakuson_normalize_origin($origin) {
    $origin = trim((string) $origin);

    if ('' === $origin) {
        return '';
    }

    $parts = wp_parse_url($origin);

    if (empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    $normalized_origin = strtolower((string) $parts['scheme']) . '://' . strtolower((string) $parts['host']);

    if (isset($parts['port'])) {
        $normalized_origin .= ':' . (int) $parts['port'];
    }

    return $normalized_origin;
}

/**
 * Read the picks CORS allowlist from wp-config, with project defaults for production and local preview.
 *
 * @return string[]
 */
function gakuson_get_picks_allowed_origins() {
    $origins = array();

    if (defined('GAKUSON_PICKS_ALLOWED_ORIGINS')) {
        $configured_origins = GAKUSON_PICKS_ALLOWED_ORIGINS;

        if (is_string($configured_origins)) {
            $configured_origins = preg_split('/[\s,]+/', trim($configured_origins));
        }

        if (is_array($configured_origins)) {
            $origins = $configured_origins;
        }
    }

    if (empty($origins)) {
        $origins = array(
            'https://gakuson.com',
            'https://gakuson.xsrv.jp',
            'http://127.0.0.1:5500',
        );
    }

    $normalized_origins = array();

    foreach ((array) $origins as $origin) {
        $normalized_origin = gakuson_normalize_origin($origin);

        if ('' !== $normalized_origin) {
            $normalized_origins[] = $normalized_origin;
        }
    }

    return array_values(array_unique(apply_filters('gakuson_picks_allowed_origins', $normalized_origins)));
}

/**
 * Check the browser origin against the picks-specific allowlist.
 *
 * @param string $origin Request origin.
 * @return bool
 */
function gakuson_is_allowed_picks_origin($origin) {
    $origin = gakuson_normalize_origin($origin);

    if ('' === $origin) {
        return false;
    }

    return in_array($origin, gakuson_get_picks_allowed_origins(), true);
}

/**
 * Keep the picks REST namespace centralized for header checks and registration.
 *
 * @return string
 */
function gakuson_get_picks_rest_namespace() {
    return 'gakuson/v1';
}

/**
 * Keep the picks REST route path centralized so the route and CORS filter stay aligned.
 *
 * @return string
 */
function gakuson_get_picks_rest_route() {
    return '/picks';
}

/**
 * Build the full REST path used by request-route checks.
 *
 * @return string
 */
function gakuson_get_picks_rest_path() {
    return '/' . trim(gakuson_get_picks_rest_namespace(), '/') . gakuson_get_picks_rest_route();
}

/**
 * Register the public picks endpoint for the external preview consumer.
 *
 * @return void
 */
function gakuson_register_picks_rest_route() {
    register_rest_route(
        gakuson_get_picks_rest_namespace(),
        gakuson_get_picks_rest_route(),
        array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'gakuson_handle_picks_rest_request',
                'permission_callback' => '__return_true',
            ),
        )
    );
}
add_action('rest_api_init', 'gakuson_register_picks_rest_route');

/**
 * Serve the picks payload through the shared cache helper.
 *
 * @param WP_REST_Request $request REST request instance.
 * @return WP_REST_Response
 */
function gakuson_handle_picks_rest_request($request) {
    return rest_ensure_response(gakuson_get_picks_response());
}

/**
 * Limit cross-origin access for the picks route to the configured consumer origins.
 *
 * @param bool             $served  Whether the request has already been served.
 * @param WP_HTTP_Response $result  Result to send to the client.
 * @param WP_REST_Request  $request Request used to generate the response.
 * @param WP_REST_Server   $server  Server instance.
 * @return bool
 */
function gakuson_send_picks_cors_headers($served, $result, $request, $server) {
    if (! $request instanceof WP_REST_Request) {
        return $served;
    }

    if (gakuson_get_picks_rest_path() !== (string) $request->get_route()) {
        return $served;
    }

    $origin = function_exists('get_http_origin') ? get_http_origin() : '';

    if (! gakuson_is_allowed_picks_origin($origin)) {
        if (function_exists('header_remove')) {
            header_remove('Access-Control-Allow-Origin');
            header_remove('Access-Control-Allow-Methods');
            header_remove('Access-Control-Allow-Credentials');
            header_remove('Access-Control-Allow-Headers');
        }

        $server->send_header('Vary', 'Origin');

        return $served;
    }

    $normalized_origin = gakuson_normalize_origin($origin);

    if ('' === $normalized_origin) {
        return $served;
    }

    $server->send_header('Access-Control-Allow-Origin', $normalized_origin);
    $server->send_header('Access-Control-Allow-Methods', 'GET, OPTIONS');
    $server->send_header('Vary', 'Origin');

    return $served;
}
add_filter('rest_pre_serve_request', 'gakuson_send_picks_cors_headers', 15, 4);

/**
 * Keep the picks JSON readable for external consumers by disabling Unicode and slash escaping.
 *
 * @param int             $options Encoding options used by the REST server.
 * @param WP_REST_Request $request Request used to generate the response.
 * @return int
 */
function gakuson_get_picks_json_encode_options($options, $request) {
    if (! $request instanceof WP_REST_Request) {
        return $options;
    }

    if (gakuson_get_picks_rest_path() !== (string) $request->get_route()) {
        return $options;
    }

    return $options | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
}
add_filter('rest_json_encode_options', 'gakuson_get_picks_json_encode_options', 10, 2);

/**
 * Normalize tag cloud defaults so later template cleanup can call one helper.
 *
 * @param array $overrides Optional argument overrides.
 * @return array
 */
function gakuson_get_tag_cloud_args($overrides = array()) {
    $overrides   = is_array($overrides) ? $overrides : array();
    $exclude_ids = gakuson_get_internal_only_tag_ids();

    if (isset($overrides['exclude'])) {
        $exclude_ids = array_merge($exclude_ids, wp_parse_id_list($overrides['exclude']));
        unset($overrides['exclude']);
    }

    $defaults = array(
        'format'   => 'list',
        'smallest' => 1,
        'largest'  => 1,
        'unit'     => 'em',
        'orderby'  => 'count',
        'order'    => 'DESC',
        'number'   => 0,
    );

    if (! empty($exclude_ids)) {
        $defaults['exclude'] = array_values(array_unique(array_filter($exclude_ids)));
    }

    return wp_parse_args($overrides, $defaults);
}

/**
 * Turn class strings or arrays into a sanitized, unique class-name list.
 *
 * @param string|string[] $classes Raw classes.
 * @return string[]
 */
function gakuson_parse_class_names($classes) {
    if (is_string($classes)) {
        $classes = preg_split('/\s+/', trim($classes));
    }

    if (! is_array($classes)) {
        return array();
    }

    $normalized_classes = array();

    foreach ($classes as $class_name) {
        $class_name = sanitize_html_class((string) $class_name);

        if ('' !== $class_name) {
            $normalized_classes[] = $class_name;
        }
    }

    return array_values(array_unique($normalized_classes));
}

/**
 * Serialize a small set of HTML attributes without forcing templates to concatenate strings.
 *
 * @param array $attributes Attribute map.
 * @return string
 */
function gakuson_format_html_attributes($attributes) {
    if (! is_array($attributes) || empty($attributes)) {
        return '';
    }

    $formatted_attributes = array();

    foreach ($attributes as $attribute_name => $attribute_value) {
        $attribute_name = preg_replace('/[^a-zA-Z0-9_:\-]/', '', (string) $attribute_name);

        if ('' === $attribute_name || false === $attribute_value || null === $attribute_value) {
            continue;
        }

        if (true === $attribute_value) {
            $formatted_attributes[] = esc_attr($attribute_name);
            continue;
        }

        if (is_array($attribute_value)) {
            $attribute_value = implode(' ', array_map('strval', $attribute_value));
        }

        $formatted_attributes[] = sprintf(
            '%1$s="%2$s"',
            esc_attr($attribute_name),
            esc_attr((string) $attribute_value)
        );
    }

    return implode(' ', $formatted_attributes);
}

/**
 * Update an HTML class attribute without rewriting unrelated element attributes.
 *
 * @param string          $attributes       Raw attribute string.
 * @param string|string[] $classes_to_add   Classes to add.
 * @param bool            $replace_existing Whether to replace the existing class list.
 * @return string
 */
function gakuson_update_html_class_attribute($attributes, $classes_to_add, $replace_existing = false) {
    $attributes  = (string) $attributes;
    $class_names = gakuson_parse_class_names($classes_to_add);

    if (preg_match('/\sclass="([^"]*)"/i', $attributes, $matches)) {
        if (! $replace_existing) {
            $class_names = array_merge(gakuson_parse_class_names($matches[1]), $class_names);
            $class_names = array_values(array_unique($class_names));
        }

        if (empty($class_names)) {
            return preg_replace('/\sclass="([^"]*)"/i', '', $attributes, 1);
        }

        return preg_replace(
            '/\sclass="([^"]*)"/i',
            ' class="' . esc_attr(implode(' ', $class_names)) . '"',
            $attributes,
            1
        );
    }

    if (empty($class_names)) {
        return $attributes;
    }

    return rtrim($attributes) . ' class="' . esc_attr(implode(' ', $class_names)) . '"';
}

/**
 * Keep wp_tag_cloud() markup transforms in one place for later template cleanup.
 *
 * @param string $tag_string Tag cloud markup.
 * @param array  $options    Formatting options.
 * @return string
 */
function gakuson_format_tag_cloud_markup($tag_string, $options = array()) {
    $defaults = array(
        'list_class'       => '',
        'item_class'       => '',
        'item_classes'     => array(),
        'link_class'       => '',
        'strip_link_class' => false,
    );

    $options = wp_parse_args($options, $defaults);

    if ('' !== $options['list_class']) {
        $tag_string = preg_replace_callback(
            '/<ul\b([^>]*)>/',
            function ($matches) use ($options) {
                return '<ul' . gakuson_update_html_class_attribute($matches[1], $options['list_class']) . '>';
            },
            $tag_string,
            1
        );
    }

    if ('' !== $options['item_class'] || ! empty($options['item_classes'])) {
        $item_classes = is_array($options['item_classes']) ? array_values($options['item_classes']) : array();
        $item_count   = count($item_classes);
        $index        = 0;

        $tag_string = preg_replace_callback(
            '/<li\b([^>]*)>/',
            function ($matches) use ($options, $item_classes, $item_count, &$index) {
                $class_names = $options['item_class'];

                if ($item_count > 0) {
                    $class_names = array_filter(
                        array(
                            $class_names,
                            $item_classes[$index % $item_count],
                        )
                    );
                }

                $index++;

                return '<li' . gakuson_update_html_class_attribute($matches[1], $class_names) . '>';
            },
            $tag_string
        );
    }

    if ($options['strip_link_class'] || '' !== $options['link_class']) {
        $tag_string = preg_replace_callback(
            '/<a\b([^>]*)>/',
            function ($matches) use ($options) {
                return '<a' . gakuson_update_html_class_attribute(
                    $matches[1],
                    $options['link_class'],
                    (bool) $options['strip_link_class']
                ) . '>';
            },
            $tag_string
        );
    }

    return $tag_string;
}

/**
 * Reuse the top-page section heading pattern on lower templates without new template-local functions.
 *
 * @param string $title     Visible section title.
 * @param string $icon_path Theme-relative icon path.
 * @param array  $args      Optional wrapper, icon, and heading settings.
 * @return string
 */
function gakuson_get_section_title_markup($title, $icon_path, $args = array()) {
    $args = wp_parse_args(
        $args,
        array(
            'heading_tag'    => 'h2',
            'wrapper_class'  => '',
            'title_class'    => '',
            'icon_class'     => '',
            'title_id'       => '',
            'icon_alt'       => '',
        )
    );

    $heading_tag = strtolower((string) $args['heading_tag']);

    if (! in_array($heading_tag, array('h1', 'h2', 'h3', 'p'), true)) {
        $heading_tag = 'h2';
    }

    $wrapper_classes = array_merge(array('section_TitleConteiner'), gakuson_parse_class_names($args['wrapper_class']));
    $title_classes   = array_merge(array('section_title'), gakuson_parse_class_names($args['title_class']));
    $icon_classes    = array_merge(array('section_titleIcon'), gakuson_parse_class_names($args['icon_class']));
    $icon_url        = trailingslashit(get_template_directory_uri()) . ltrim((string) $icon_path, '/');
    $title_id        = '' !== $args['title_id'] ? ' id="' . esc_attr($args['title_id']) . '"' : '';

    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', array_unique($wrapper_classes))); ?>">
        <img
            class="<?php echo esc_attr(implode(' ', array_unique($icon_classes))); ?>"
            src="<?php echo esc_url($icon_url); ?>"
            alt="<?php echo esc_attr($args['icon_alt']); ?>"
        >
        <<?php echo esc_html($heading_tag); ?> class="<?php echo esc_attr(implode(' ', array_unique($title_classes))); ?>"<?php echo $title_id; ?>>
            <?php echo esc_html($title); ?>
        </<?php echo esc_html($heading_tag); ?>>
    </div>
    <?php

    return trim(ob_get_clean());
}

/**
 * Reuse one heart icon so cards and the single-post reaction button stay visually aligned.
 *
 * @param array $args Optional class overrides.
 * @return string
 */
function gakuson_get_heart_icon_markup($args = array()) {
    $args = wp_parse_args(
        $args,
        array(
            'class' => '',
        )
    );

    $classes = array_merge(array('postHeartIcon'), gakuson_parse_class_names($args['class']));

    ob_start();
    ?>
    <svg class="<?php echo esc_attr(implode(' ', array_unique($classes))); ?>" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="M12 21.35 10.55 20.03C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54Z"/>
    </svg>
    <?php

    return trim(ob_get_clean());
}

/**
 * Render compact crown icons for the top ranks so ranking badges feel less mechanical.
 *
 * @param int $ranking Ranking position.
 * @return string
 */
function gakuson_get_ranking_crown_icon_markup($ranking) {
    $ranking = (int) $ranking;

    if ($ranking < 1 || $ranking > 3) {
        return '';
    }

    $variant_classes = array(
        1 => 'articleCrownIcon--gold',
        2 => 'articleCrownIcon--silver',
        3 => 'articleCrownIcon--bronze',
    );

    ob_start();
    ?>
    <svg class="articleCrownIcon <?php echo esc_attr($variant_classes[$ranking]); ?>" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="M4 7.5c.42 0 .8.19 1.05.48l2.77 3.36 3.36-4.8c.18-.25.47-.4.82-.4.34 0 .64.15.81.4l3.37 4.8 2.77-3.36c.25-.29.62-.48 1.04-.48.75 0 1.35.61 1.35 1.35 0 .15-.03.3-.08.45L19.2 17.5c-.15.39-.53.65-.95.65H5.75c-.43 0-.81-.26-.96-.65L2.73 9.3a1.3 1.3 0 0 1-.08-.45c0-.74.6-1.35 1.35-1.35Z"/>
        <rect x="5.2" y="18.35" width="13.6" height="2.1" rx="1.05" fill="currentColor"/>
    </svg>
    <?php

    return trim(ob_get_clean());
}

/**
 * Keep compact like counts consistent across cards and the single-page reaction box.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @param array            $args Optional wrapper class modifiers.
 * @return string
 */
function gakuson_get_post_stats_markup($post = null, $args = array()) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return '';
    }

    $args = wp_parse_args(
        $args,
        array(
            'wrapper_class' => '',
        )
    );

    $wrapper_classes = array_merge(array('postStats'), gakuson_parse_class_names($args['wrapper_class']));
    $like_count      = gakuson_get_post_like_count($post);

    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', array_unique($wrapper_classes))); ?>" data-post-stats data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
        <span class="postStats_item postStats_item--likes" aria-label="いいね数">
            <span class="postStats_icon" aria-hidden="true">
                <?php echo gakuson_get_heart_icon_markup(array('class' => 'postHeartIcon--stats')); ?>
            </span>
            <span class="postStats_value" data-post-like-count data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
                <?php echo esc_html('+' . number_format_i18n($like_count)); ?>
            </span>
        </span>
    </div>
    <?php

    return trim(ob_get_clean());
}

/**
 * Render the single-post reaction box while keeping the interaction logic outside templates.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @return string
 */
function gakuson_get_like_panel_markup($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post || 'post' !== $post->post_type) {
        return '';
    }

    $remaining_likes = gakuson_get_remaining_like_count($post);
    $like_count      = gakuson_get_post_like_count($post);
    $button_disabled = $remaining_likes <= 0;
    $counter_label   = sprintf(
        'この端末では最大10回いいねできます。現在の合計いいね数は%s回です。',
        number_format_i18n($like_count)
    );

    ob_start();
    ?>
    <section class="postReaction" data-like-block data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
        <div class="postReaction_header">
            <p class="postReaction_note" data-like-remaining>
                <?php echo esc_html(gakuson_get_like_remaining_message($remaining_likes)); ?>
            </p>
            <h2 class="postReaction_title">ハートでいいね！</h2>
        </div>
        <div class="postReaction_body">
            <div class="postReaction_buttonShell">
                <button
                    type="button"
                    class="postReaction_button<?php echo $button_disabled ? ' is-disabled' : ''; ?>"
                    data-like-button
                    data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
                    <?php disabled($button_disabled); ?>
                    aria-disabled="<?php echo $button_disabled ? 'true' : 'false'; ?>"
                >
                    <span class="postReaction_buttonFace">
                        <span class="postReaction_buttonIcon" aria-hidden="true">
                            <?php echo gakuson_get_heart_icon_markup(array('class' => 'postHeartIcon--button')); ?>
                        </span>
                        <span class="postReaction_buttonLabel">いいね！</span>
                    </span>
                </button>
            </div>
            <div class="postReaction_counter" aria-label="<?php echo esc_attr($counter_label); ?>">
                <span class="postReaction_counterTotalLabel">合計</span>
                <span class="postReaction_counterIcon" aria-hidden="true">
                    <?php echo gakuson_get_heart_icon_markup(array('class' => 'postHeartIcon--stats')); ?>
                </span>
                <span class="postReaction_counterTotalValue" data-post-like-total data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
                    <?php echo esc_html(number_format_i18n($like_count)); ?>
                </span>
                <span class="postReaction_counterTotalSuffix">回</span>
            </div>
        </div>
        <p class="postReaction_status" data-like-status aria-live="polite"></p>
        <noscript>
            <p class="postReaction_noscript">いいね機能はJavaScriptを有効にすると使えます。</p>
        </noscript>
    </section>
    <?php

    return trim(ob_get_clean());
}

/**
 * Keep article cards consistent across archives, related-post lists, and future side rails.
 *
 * @param WP_Post|int|null $post Optional post object or ID.
 * @param array            $args Optional card settings.
 * @return string
 */
function gakuson_get_article_card_markup($post = null, $args = array()) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return '';
    }

    $args = wp_parse_args(
        $args,
        array(
            'card_classes'  => array(),
            'title_tag'     => 'h3',
            'show_taxonomy' => true,
            'show_stats'    => true,
            'ranking'       => 0,
            'image_size'    => 'post_thumbnails',
            'link_url'      => '',
            'attributes'    => array(),
        )
    );

    $title_tag = strtolower((string) $args['title_tag']);

    if (! in_array($title_tag, array('h2', 'h3', 'p'), true)) {
        $title_tag = 'h3';
    }

    $ranking      = max(0, (int) $args['ranking']);
    $card_classes = array_merge(array('article_item'), gakuson_parse_class_names($args['card_classes']));

    if ($ranking > 0) {
        $card_classes[] = 'article_item__popu';
    }

    $title            = get_the_title($post);
    $permalink        = '' !== $args['link_url'] ? (string) $args['link_url'] : get_permalink($post);
    $author_name      = get_the_author_meta('display_name', (int) $post->post_author);
    $stats_markup     = $args['show_stats'] ? gakuson_get_post_stats_markup($post, array('wrapper_class' => 'postStats--card')) : '';
    $taxonomy_pc      = $args['show_taxonomy'] ? gakuson_get_article_taxonomy_markup($post, 'pc') : '';
    $taxonomy_sp      = $args['show_taxonomy'] ? gakuson_get_article_taxonomy_markup($post, 'sp') : '';
    $attribute_markup = gakuson_format_html_attributes($args['attributes']);
    $thumbnail_markup = has_post_thumbnail($post)
        ? get_the_post_thumbnail($post->ID, $args['image_size'])
        : sprintf(
            '<img src="%1$s" alt="%2$s">',
            esc_url(get_template_directory_uri() . '/img/no-image.png'),
            esc_attr($title)
        );
    $ranking_badge_classes = array('article_num');

    if (1 === $ranking) {
        $ranking_badge_classes[] = 'article_num__1st';
    } elseif (2 === $ranking) {
        $ranking_badge_classes[] = 'article_num__2nd';
    } elseif (3 === $ranking) {
        $ranking_badge_classes[] = 'article_num__3rd';
    }

    ob_start();
    ?>
    <a href="<?php echo esc_url($permalink); ?>" class="<?php echo esc_attr(implode(' ', get_post_class($card_classes, $post->ID))); ?>"<?php echo '' !== $attribute_markup ? ' ' . $attribute_markup : ''; ?>>
        <?php if ($ranking > 0) : ?>
            <p class="<?php echo esc_attr(implode(' ', $ranking_badge_classes)); ?>">
                <?php if ($ranking <= 3) : ?>
                    <span class="article_numIcon" aria-hidden="true">
                        <?php echo gakuson_get_ranking_crown_icon_markup($ranking); ?>
                    </span>
                <?php else : ?>
                    <span class="article_numPrefix" aria-hidden="true">#</span>
                <?php endif; ?>
                <span class="article_numValue"><?php echo esc_html((string) $ranking); ?></span>
            </p>
        <?php endif; ?>
        <div class="article_main">
            <div class="article_itemThumbnail">
                <?php echo $thumbnail_markup; ?>
            </div>
            <div class="article_text">
                <<?php echo esc_html($title_tag); ?> class="article_title"><?php echo esc_html($title); ?></<?php echo esc_html($title_tag); ?>>
                <div class="article_desc">
                    <p class="article_date"><?php echo esc_html(get_the_date('', $post)); ?></p>
                    <p class="article_author"><?php echo esc_html($author_name); ?></p>
                </div>
                <?php if ('' !== $stats_markup) : ?>
                    <?php echo $stats_markup; ?>
                <?php endif; ?>
                <?php if ('' !== $taxonomy_pc) : ?>
                    <?php echo $taxonomy_pc; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if ('' !== $taxonomy_sp) : ?>
            <?php echo $taxonomy_sp; ?>
        <?php endif; ?>
    </a>
    <?php

    return trim(ob_get_clean());
}

/**
 * Reuse the top-page tag directory markup on lower templates while keeping the featured tag hidden.
 *
 * @param array $args Optional wrapper and heading settings.
 * @return string
 */
function gakuson_get_tag_directory_markup($args = array()) {
    $args = wp_parse_args(
        $args,
        array(
            'section_class' => 'l-tag',
            'title'         => 'タグ一覧',
            'heading_tag'   => 'h2',
        )
    );

    $tag_cloud_markup = wp_tag_cloud(
        gakuson_get_tag_cloud_args(
            array(
                'echo' => false,
            )
        )
    );

    if ('' === trim((string) $tag_cloud_markup)) {
        return '';
    }

    $formatted_tag_cloud = gakuson_format_tag_cloud_markup(
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

    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', array_unique(array_merge(array('l-tag'), gakuson_parse_class_names($args['section_class']))))); ?>">
        <?php
        echo gakuson_get_section_title_markup(
            $args['title'],
            'icon/tagIcon.png',
            array(
                'heading_tag' => $args['heading_tag'],
            )
        );
        ?>
        <?php echo $formatted_tag_cloud; ?>
    </section>
    <?php

    return trim(ob_get_clean());
}

/**
 * Invalidate featured-content caches when post content changes.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function gakuson_invalidate_carousel_cache_on_post_save($post_id) {
    if (wp_is_post_revision($post_id) || 'post' !== get_post_type($post_id)) {
        return;
    }

    gakuson_delete_cached_carousel_response();
    gakuson_delete_cached_picks_response();
}
add_action('save_post_post', 'gakuson_invalidate_carousel_cache_on_post_save');

/**
 * Invalidate featured-content caches when terms affecting tagged source content change.
 *
 * @param int|int[] $object_ids Object IDs being updated.
 * @param int[]     $terms      Term IDs.
 * @param int[]     $tt_ids     Term taxonomy IDs.
 * @param string    $taxonomy   Taxonomy slug.
 * @return void
 */
function gakuson_invalidate_carousel_cache_on_term_change($object_ids, $terms, $tt_ids, $taxonomy) {
    if ('post_tag' !== $taxonomy && 'category' !== $taxonomy) {
        return;
    }

    gakuson_delete_cached_carousel_response();
    gakuson_delete_cached_picks_response();
}
add_action('set_object_terms', 'gakuson_invalidate_carousel_cache_on_term_change', 10, 4);

/**
 * Invalidate featured-content caches before a post is deleted.
 *
 * @param int $post_id Deleted post ID.
 * @return void
 */
function gakuson_invalidate_carousel_cache_on_post_delete($post_id) {
    if ('post' !== get_post_type($post_id)) {
        return;
    }

    gakuson_delete_cached_carousel_response();
    gakuson_delete_cached_picks_response();
}
add_action('before_delete_post', 'gakuson_invalidate_carousel_cache_on_post_delete');

/**
 * Seed zero-value like meta once so ranking queries can sort existing posts safely.
 *
 * @return void
 */
function gakuson_seed_like_meta_for_existing_posts() {
    static $has_run = false;

    if ($has_run || get_option('gakuson_like_meta_seeded', false)) {
        return;
    }

    $has_run = true;

    $post_ids = get_posts(
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array(
                array(
                    'key'     => gakuson_get_like_count_meta_key(),
                    'compare' => 'NOT EXISTS',
                ),
            ),
        )
    );

    foreach ($post_ids as $post_id) {
        gakuson_ensure_post_like_meta($post_id);
    }

    update_option('gakuson_like_meta_seeded', 1, false);
}
add_action('init', 'gakuson_seed_like_meta_for_existing_posts', 20);

/**
 * Give newly saved posts the same zero-like default as seeded older content.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function gakuson_ensure_post_like_meta_on_save($post_id) {
    if (wp_is_post_revision($post_id) || 'post' !== get_post_type($post_id)) {
        return;
    }

    gakuson_ensure_post_like_meta($post_id);
}
add_action('save_post_post', 'gakuson_ensure_post_like_meta_on_save');

/**
 * Handle public like requests through WordPress Ajax without introducing a custom table.
 *
 * @return void
 */
function gakuson_handle_like_post_ajax() {
    check_ajax_referer('gakuson_like_post', 'nonce');

    $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
    $post    = get_post($post_id);

    if (! $post instanceof WP_Post || 'post' !== $post->post_type || 'publish' !== $post->post_status) {
        wp_send_json_error(
            array(
                'message' => '対象の記事が見つかりません。',
            ),
            404
        );
    }

    $max_likes          = gakuson_get_like_max_per_visitor();
    $cookie_like_counts = gakuson_get_like_cookie_counts();
    $visitor_like_count = isset($cookie_like_counts[$post_id]) ? max(0, (int) $cookie_like_counts[$post_id]) : 0;

    if ($visitor_like_count >= $max_likes) {
        wp_send_json_success(
            array(
                'likeCount'        => gakuson_get_post_like_count($post),
                'viewCount'        => function_exists('gakuson_get_post_view_count') ? gakuson_get_post_view_count($post_id) : 0,
                'visitorLikeCount' => $visitor_like_count,
                'remainingLikes'   => 0,
                'reachedLimit'     => true,
                'message'          => gakuson_get_like_remaining_message(0),
            )
        );
    }

    $cookie_like_counts[$post_id] = $visitor_like_count + 1;
    gakuson_set_like_cookie_counts($cookie_like_counts);

    $updated_like_count        = gakuson_increment_post_like_count($post);
    $updated_visitor_like_count = min($max_likes, $visitor_like_count + 1);
    $remaining_likes           = max(0, $max_likes - $updated_visitor_like_count);

    wp_send_json_success(
        array(
            'likeCount'        => $updated_like_count,
            'viewCount'        => function_exists('gakuson_get_post_view_count') ? gakuson_get_post_view_count($post_id) : 0,
            'visitorLikeCount' => $updated_visitor_like_count,
            'remainingLikes'   => $remaining_likes,
            'reachedLimit'     => 0 === $remaining_likes,
            'message'          => 0 === $remaining_likes ? gakuson_get_like_remaining_message(0) : 'いいねしました。',
        )
    );
}
add_action('wp_ajax_gakuson_like_post', 'gakuson_handle_like_post_ajax');
add_action('wp_ajax_nopriv_gakuson_like_post', 'gakuson_handle_like_post_ajax');
