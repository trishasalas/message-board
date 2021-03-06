<?php

/* ====== Slugs ====== */

function mb_get_root_slug() {
	return apply_filters( 'mb_root_slug', 'board' );
}

function mb_maybe_get_root_slug() {
	return true == apply_filters( 'mb_maybe_get_root_slug', true ) ? trailingslashit( mb_get_root_slug() ) : '';
}

function mb_get_topic_slug() {
	return apply_filters( 'mb_topic_slug', mb_maybe_get_root_slug() . 'topics' );
}

function mb_get_forum_slug() {
	return apply_filters( 'mb_forum_slug', mb_maybe_get_root_slug() . 'forums' );
}

function mb_get_tag_slug() {
	return apply_filters( 'mb_tag_slug', mb_maybe_get_root_slug() . 'tags' );
}

function mb_get_view_slug() {
	return apply_filters( 'mb_view_slug', mb_maybe_get_root_slug() . 'views' );
}

function mb_get_user_slug() {
	return apply_filters( 'mb_get_user_slug', mb_maybe_get_root_slug() . 'users' );
}

/**
 * Sets up custom rewrite rules for pages that aren't handled by the CPT and CT APIs but are needed by 
 * the plugin.
 *
 * @since  1.0.0
 * @access public
 * @return void
 */
function mb_rewrite_rules() {

	/* Slugs and query vars. */

	$root_slug      = mb_get_root_slug();
	$user_slug      = mb_get_user_slug();
	$view_slug      = mb_get_view_slug();

	$profile_query_var = 'mb_profile';
	$user_query_var    = 'mb_user_view';
	$view_query_var    = 'mb_view';

	/* Rewrite tags. */

	add_rewrite_tag( '%' . $profile_query_var . '%', '([^/]+)' );
	add_rewrite_tag( '%' . $user_query_var    . '%', '([^/]+)' );
	add_rewrite_tag( '%' . $view_query_var    . '%', '([^/]+)' );

	add_rewrite_rule( $user_slug . '/([^/]+)/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?author_name=$matches[1]&' . $user_query_var . '=$matches[2]&paged=$matches[3]', 'top' );
	add_rewrite_rule( $user_slug . '/([^/]+)/([^/]+)/feed/?$',              'index.php?author_name=$matches[1]&' . $user_query_var . '=$matches[2]&feed=$matches[3]',  'top' );
	add_rewrite_rule( $user_slug . '/([^/]+)/([^/]+)/?$',                   'index.php?author_name=$matches[1]&' . $user_query_var . '=$matches[2]',                   'top' );
	add_rewrite_rule( $user_slug . '/([^/]+)/?$',                           'index.php?author_name=$matches[1]&mb_profile=1',                                          'top' );

	add_rewrite_rule( $view_slug . '/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?' . $view_query_var . '=$matches[1]&paged=$matches[2]', 'top' );
	add_rewrite_rule( $view_slug . '/([^/]+)/feed/?$',              'index.php?' . $view_query_var . '=$matches[1]&feed=$matches[2]',  'top' );
	add_rewrite_rule( $view_slug . '/([^/]+)/?$',                   'index.php?' . $view_query_var . '=$matches[1]',                   'top' );

	/* Forum front page. */
	add_rewrite_rule( '^' . $root_slug . '$', 'index.php', 'top' );
}

/**
 * Overwrites the rewrite rules for the `forum_topic` post type.  In particular, we need to handle the 
 * pagination on singular topics because the `forum_reply` post type is paginated on this page.
 *
 * @todo See if this can be simplified where we're only taking care of the things we need.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $rules
 * @return array
 */
function mb_forum_topic_rewrite_rules( $rules ) {

	$topic_slug = mb_get_topic_slug();

	$rules = array(
		$topic_slug . '/[^/]+/attachment/([^/]+)/?$'                               => 'index.php?attachment=$matches[1]',
		$topic_slug . '/[^/]+/attachment/([^/]+)/trackback/?$'                     => 'index.php?attachment=$matches[1]&tb=1',
		$topic_slug . '/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?attachment=$matches[1]&feed=$matches[2]',
		$topic_slug . '/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$'      => 'index.php?attachment=$matches[1]&feed=$matches[2]',
		$topic_slug . '/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$'      => 'index.php?attachment=$matches[1]&cpage=$matches[2]',
		$topic_slug . '/([^/]+)/trackback/?$'                                      => 'index.php?forum_topic=$matches[1]&tb=1',
		$topic_slug . '/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$'                  => 'index.php?forum_topic=$matches[1]&feed=$matches[2]',
		$topic_slug . '/([^/]+)/(feed|rdf|rss|rss2|atom)/?$'                       => 'index.php?forum_topic=$matches[1]&feed=$matches[2]',
		$topic_slug . '/page/?([0-9]{1,})/?$'                                      => 'index.php?post_type=forum_topic&paged=$matches[1]',
		$topic_slug . '/([^/]+)/page/([0-9]{1,})/?$'                               => 'index.php?forum_topic=$matches[1]&paged=$matches[2]',
		$topic_slug . '/([^/]+)(/[0-9]+)?/?$'                                      => 'index.php?forum_topic=$matches[1]&page=$matches[2]',
		$topic_slug . '/[^/]+/([^/]+)/?$'                                          => 'index.php?attachment=$matches[1]',
		$topic_slug . '/[^/]+/([^/]+)/trackback/?$'                                => 'index.php?attachment=$matches[1]&tb=1',
		$topic_slug . '/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$'            => 'index.php?attachment=$matches[1]&feed=$matches[2]',
		$topic_slug . '/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$'                 => 'index.php?attachment=$matches[1]&feed=$matches[2]',
		$topic_slug . '/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$'                 => 'index.php?attachment=$matches[1]&cpage=$matches[2]'
	);

	return $rules;
}

/**
 * Makes sure any paged redirects are corrected.
 *
 * @since  1.0.0
 * @access public
 * @param  string  $redirect_url
 * @param  string  $requested_url
 * @return string
 */
function mb_redirect_canonical( $redirect_url, $requested_url ) {

	$topic_slug = mb_get_topic_slug();

	if ( preg_match( "#{$topic_slug}/([^/]+)/page/([0-9]{1,})/?$#i", $requested_url ) )
		return false;

	return $redirect_url;
}
