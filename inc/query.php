<?php

/**
 * Checks if viewing the forum front page.
 *
 * @since  1.0.0
 * @access public
 * @return bool
 */
function mb_is_forum_front() {
	global $wp;
	return 'board' === $wp->request ? true : false;
}

function mb_is_forum_search() {

	if ( is_search() && $type = get_query_var( 'post_type' ) ) {

		$type = is_array( $type ) ? $type : array( $type );

		return in_array( 'forum_topic', $type ) || in_array( 'forum_reply', $type ) ? true : false;
	}

	return false;
}

/**
 * Checks if viewing one of the Message Board plugin pages.
 *
 * @since  1.0.0
 * @access public
 * @return bool
 */
function mb_is_message_board() {

	//$mb_vars = array( 'mb_user', 'mb_topics', 'mb_replies', 'mb_favorites', 'mb_subscriptions' );

	if ( 1 == get_query_var( 'mb_profile' ) || get_query_var( 'mb_topics' ) || get_query_var( 'mb_replies' ) ||
		get_query_var( 'mb_favorites' ) || get_query_var( 'mb_subscriptions' ) 
		|| mb_is_view() || mb_is_user_view() || mb_is_forum_search() 
		|| mb_is_forum_front() || is_post_type_archive( 'forum_topic' ) || is_singular( 'forum_topic' ) || is_tax( array( 'forum', 'forum_tag' ) ) )
		return true;

	return false;
}

/**
 * Overwrites the main query depending on the situation.
 *
 * @since  1.0.0
 * @access public
 * @param  object  $query
 * @return void
 */
function mb_pre_get_posts( $query ) {

//var_dump( $query );

	if ( !is_admin() && $query->is_main_query() && mb_is_forum_front() ) {
		$query->set( 'post_type', 'forum_topic' );
	}

	elseif ( !is_admin() && $query->is_main_query() && ( is_post_type_archive( 'forum_topic' ) || is_tax( array( 'forum', 'forum_tag' ) ) ) ) {

		$query->set( 'post_type',      'forum_topic'            );
		$query->set( 'posts_per_page', mb_get_topics_per_page() );
		$query->set( 'order',          'DESC'                   );
		$query->set( 'orderby',        'menu_order'             );
	}

	elseif ( !is_admin() && $query->is_main_query() && get_query_var( 'mb_user_view' ) ) {

		if ( 'topics' === get_query_var( 'mb_user_view' ) ) {

			$query->set( 'post_type',      'forum_topic'            );
			$query->set( 'posts_per_page', mb_get_topics_per_page() );
			$query->set( 'order',          'DESC'                   );
			$query->set( 'orderby',        'menu_order'             );

		} elseif ( 'favorites' === get_query_var( 'mb_user_view' ) ) {

			$user      = get_user_by( 'slug', get_query_var( 'author_name' ) );
			$favorites = get_user_meta( $user->ID, '_topic_favorites', true );
			$favs      = wp_parse_id_list( $favorites );

			$query->set( 'post__in',      $favs                     );
			$query->set( 'post_type',     'forum_topic'             );
			$query->set( 'posts_per_page', mb_get_topics_per_page() );
			$query->set( 'order',          'DESC'                   );
			$query->set( 'orderby',        'menu_order'             );

			add_filter( 'posts_where', 'mb_auth_posts_where', 10, 2 );

		} elseif ( 'subscriptions' === get_query_var( 'mb_user_view' ) ) {

			$user = get_user_by( 'slug', get_query_var( 'author_name' ) );
			$subscriptions = get_user_meta( $user->ID, '_topic_subscriptions', true );
			$subs = wp_parse_id_list( $subscriptions );

			$query->set( 'post__in',      $subs                     );
			$query->set( 'post_type',     'forum_topic'             );
			$query->set( 'posts_per_page', mb_get_topics_per_page() );
			$query->set( 'order',          'DESC'                   );
			$query->set( 'orderby',        'menu_order'             );

			add_filter( 'posts_where', 'mb_auth_posts_where', 10, 2 );

		} elseif ( 'replies' === get_query_var( 'mb_user_view' ) ) {

			$query->set( 'post_type',     'forum_reply'              );
			$query->set( 'posts_per_page', mb_get_replies_per_page() );
			$query->set( 'order',          'DESC'                    );
			$query->set( 'orderby',        'date'                    );

		} elseif ( 'activity' === get_query_var( 'mb_user_view' ) ) {

			$query->set( 'post_type',     array( 'forum_reply', 'forum_topic' ) );
			$query->set( 'posts_per_page', mb_get_replies_per_page()            );
			$query->set( 'order',          'DESC'                               );
			$query->set( 'orderby',        'date'                               );
		}
	}

	elseif ( !is_admin() && $query->is_main_query() && mb_is_view() ) {

		// @todo handle stickies for views

		$view = mb_get_view( get_query_var( 'mb_view' ) );

		foreach ( $view['query'] as $arg => $value ) {

			if ( 'post_type' !== $arg )
				$query->set( $arg, $value );
		}

		$query->set( 'post_type', 'forum_topic' );
	}
}

// apply_filters_ref_array( 'posts_where', array( $where, &$this ) );

function mb_auth_posts_where( $where, $query ) {
	global $wpdb;

	$author_id = get_query_var( 'author' );

	$where = str_replace( " AND ({$wpdb->posts}.post_author = {$author_id})", '', $where );

	return $where;
}

// apply_filters_ref_array( 'the_posts', array( $this->posts, &$this ) );

add_filter( 'the_posts', 'mb_the_posts', 10, 2 );

function mb_the_posts( $posts, $query ) {

	if ( !is_admin() && $query->is_main_query() && is_post_type_archive( 'forum_topic' ) || is_tax( 'forum_tag' ) ) {

		$super_stickies = get_option( 'mb_super_sticky_topics', array() );

		$posts = mb_the_posts_stickies( $posts, $super_stickies );
	}

	elseif ( !is_admin() && $query->is_main_query() && is_tax( 'forum' ) ) {

		$super_stickies = get_option( 'mb_super_sticky_topics', array() );
		$topic_stickies = get_option( 'mb_sticky_topics',       array() );

		$posts = mb_the_posts_stickies( $posts, array_merge( $super_stickies, $topic_stickies ) );
	}

	return $posts;
}

function mb_the_posts_stickies( $posts, $sticky_posts ) {

		if ( !is_paged() && !empty( $sticky_posts ) ) {

			$num_posts = count( $posts );

			$sticky_offset = 0;

			// Loop over posts and relocate stickies to the front.
			for ( $i = 0; $i < $num_posts; $i++ ) {

				if ( in_array( $posts[ $i ]->ID, $sticky_posts ) ) {

					$sticky_post = $posts[ $i ];

					// Remove sticky from current position
					array_splice( $posts, $i, 1);

					// Move to front, after other stickies
					array_splice( $posts, $sticky_offset, 0, array( $sticky_post ) );

					// Increment the sticky offset. The next sticky will be placed at this offset.
					$sticky_offset++;

					// Remove post from sticky posts array
					$offset = array_search( $sticky_post->ID, $sticky_posts );

					unset( $sticky_posts[ $offset ] );
				}
			}

			// Fetch sticky posts that weren't in the query results
			if ( !empty( $sticky_posts ) ) {

				$stickies = get_posts(
					array(
						'post__in'    => $sticky_posts,
						'post_type'   => 'forum_topic',
						'post_status' => 'publish',
						'nopaging'    => true
					)
				);

				foreach ( $stickies as $sticky_post ) {
					array_splice( $posts, $sticky_offset, 0, array( $sticky_post ) );
					$sticky_offset++;
				}
			}
		}

	return $posts;
}

function mb_have_topics() {
	return have_posts();
}

function mb_the_topic() {
	return the_post();
}

function mb_has_replies() {
	$mb = message_board();

	$per_page = mb_get_replies_per_page();

	$defaults = array(
		'post_type'           => 'forum_reply',
		'post_parent'         => get_queried_object_id(),
		'posts_per_page'      => $per_page,
		'paged'               => get_query_var( 'paged' ),
		'orderby'             => 'date',
		'order'               => 'ASC',
		'hierarchical'        => false,
		'ignore_sticky_posts' => true,
	);

	$mb->reply_query = new WP_Query( $defaults );

	return $mb->reply_query->have_posts();
}

function mb_replies() {

	$have_posts = message_board()->reply_query->have_posts();

	if ( empty( $have_posts ) )
		wp_reset_postdata();

	return $have_posts;
}

function mb_the_reply() {
	return message_board()->reply_query->the_post();
}

/**
 * Sets `$query->is_404` to `false` right after the query has been parsed when viewing the forum front 
 * page, which WP sets to 404 by default.
 *
 * @since  1.0.0
 * @access public
 * @param  object  $query
 * @return void
 */
function mb_parse_query( $query ) {

	if ( mb_is_forum_front() ) {
		$query->is_404 = false;
		$query->is_home = false;
	} elseif ( mb_is_view() || mb_is_user_view() ) {
		$query->is_home = false;
		$query->is_archive = true;
	}
}

/**
 * Overrides the 404 for the forum front page early on the `template_redirect` hook.
 *
 * @since  1.0.0
 * @access public
 * @return void
 */
function mb_404_override() {
	global $wp_query;

	if ( mb_is_forum_front() ) {
		status_header( 200 );
		$wp_query->is_404 = false;
	}
}
