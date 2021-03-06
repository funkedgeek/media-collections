<?php

if( !class_exists( 'CED_Playlist_Type' )) :
class CED_Playlist_Type extends CED_Post_Type {
	public $post_type = 'playlist';	
	
	function __construct(){
		parent::__construct();
		add_action( 'wp_loaded', array($this, 'register_connections'), 100);
		add_filter( 'the_posts', array($this, 'process_posts'), 10, 2 );
	}
	
	function setup_post_type() {
			$labels = array(
			'name'                => _x( 'Playlists', 'Post Type General Name', 'cedmc' ),
			'singular_name'       => _x( 'Playlist', 'Post Type Singular Name', 'cedmc' ),
			'menu_name'           => __( 'Playlists', 'cedmc' ),
			'parent_item_colon'   => __( 'Parent Playlist:', 'cedmc' ),
			'all_items'           => __( 'All Playlists', 'cedmc' ),
			'view_item'           => __( 'View Playlist', 'cedmc' ),
			'add_new_item'        => __( 'Add New Playlist', 'cedmc' ),
			'add_new'             => __( 'Add New', 'cedmc' ),
			'edit_item'           => __( 'Edit Playlist', 'cedmc' ),
			'update_item'         => __( 'Update Playlist', 'cedmc' ),
			'search_items'        => __( 'Search Playlist', 'cedmc' ),
			'not_found'           => __( 'Not found', 'cedmc' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'cedmc' ),
		);
		$rewrite = array(
			'slug'                => 'playlist',
			'with_front'          => true,
			'pages'               => true,
			'feeds'               => true,
		);
		$args = array(
			'label'               => __( 'playlist', 'cedmc' ),
			'description'         => __( 'A playlist post', 'cedmc' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'thumbnail', 'comments', 'trackbacks', ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'query_var'           => 'playlist',
			'rewrite'             => false,
			'capability_type'     => 'post',
			'menu_icon'           => 'dashicons-video-alt3'
		);
		register_post_type( $this->post_type, $args );	
	}
	
	function setup_taxonomies() {
		$labels = array(
			'name'                       => _x( 'Playlist Types', 'Taxonomy General Name', 'cedmc' ),
			'singular_name'              => _x( 'Playlist Type', 'Taxonomy Singular Name', 'cedmc' ),
			'menu_name'                  => __( 'Playlist Types', 'cedmc' ),
			'all_items'                  => __( 'All Playlist Types', 'cedmc' ),
			'parent_item'                => __( 'Parent Playlist Type', 'cedmc' ),
			'parent_item_colon'          => __( 'Parent Playlist Type:', 'cedmc' ),
			'new_item_name'              => __( 'New Playlist Type Name', 'cedmc' ),
			'add_new_item'               => __( 'Add New Playlist Type', 'cedmc' ),
			'edit_item'                  => __( 'Edit Playlist Type', 'cedmc' ),
			'update_item'                => __( 'Update Playlist Type', 'cedmc' ),
			'separate_items_with_commas' => __( 'Separate playlist types with commas', 'cedmc' ),
			'search_items'               => __( 'Search Playlist Types', 'cedmc' ),
			'add_or_remove_items'        => __( 'Add or remove playlist types', 'cedmc' ),
			'choose_from_most_used'      => __( 'Choose from the most used playlist types', 'cedmc' ),
			'not_found'                  => __( 'Not Found', 'cedmc' ),
		);
		$rewrite = false;
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => false,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
			'query_var'                  => 'playlist_type',
			'rewrite'                    => $rewrite,
		);
		register_taxonomy( 'playlist_type', array( $this->post_type ), $args );	
	}
	
	function populate_taxonomy() {
		wp_insert_term( 'Audio Playlists', 'playlist_type', array( 'slug' => 'audio' ) );	
		wp_insert_term( 'Video Playlists', 'playlist_type', array( 'slug' => 'video' ) );		
	}
	
	function setup_rewrite_api() {
		//Query vars/tags	
		add_rewrite_tag( '%media_in_playlist%', '([^/]+)' );
		add_rewrite_tag( '%playlist_type%', '(audio|video)' );	
		
		$structs = array(
			'playlist/%playlist_type%/',
			'playlist/'
			
		);
		
		$endpoints = array(
			'%year%/%monthnum%/%day%/' ,
			'%year%/%monthnum%/',
			'%year%/',
		);
		
		foreach( $structs as $struct ) {
			foreach( $endpoints as $endpoint ) {
				$this->struct_to_rewrite( $struct . $endpoint );	
			}
			$this->struct_to_rewrite( $struct );	
		}
		$this->struct_to_rewrite( 'playlist/%playlist_type%/%postname%/', false, false );
		add_permastruct( 'playlist', 'playlist/%playlist_type%/%postname%/', array( 'walk_dirs' => false, 'endpoints'=>false ) );
	}
	
	function create_permalink( $permalink, $post, $leavename, $sample ) {		
		$permalink = parent::create_permalink( $permalink, $post, $leavename, $sample );
		$rewritecode = array(
			'%playlist_type%',
		);
		
		if ( '' != $permalink && !in_array( $post->post_status, array('draft', 'pending', 'auto-draft') ) ) {
			$type = 'audio';
			if( strpos($permalink, '%playlist_type%') !== false ) {
				$type = get_playlist_type( $post );	
			}
			
			$rewritereplace = array(
				$type
			);
			$permalink = str_replace( $rewritecode, $rewritereplace, $permalink );
		}
		return $permalink;		
	}
	
	function parse_query( $query ) {
		if( $playlist = $query->get( 'media_in_playlist' ) ) {
			$playlist = is_numeric( $playlist ) ? $playlist : $this->slug_to_id( $playlist, 'playlist' );
			$query->playlist = get_post( $playlist );
			$type = get_playlist_type( $query->playlist->ID );
			$query->set( 'connected_type', 'playlist_to_media' );
			$query->set( 'connected_items', $query->playlist );
			$query->set( 'post_mime_type', $type );
		}
	}
	
	function process_posts( $posts, $query ) {
		remove_filter( 'the_posts', array( $this, 'process_posts' ) );
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'playlist_to_media' )  ) { 
			$items =& $posts;
			$playlists = array(
				'audio' =>array(),
				'video' => array()
			);				

			foreach( $items as $item ) {
				if( 'playlist' === $item->post_type ) {
					$type = get_playlist_type( $item->ID );
					$playlists[$type][] = $item; 
				}
			}
			$this->add_connected( $playlists['audio'], 'playlist_to_media', 'media',  array( 'post_mime_type'=>'audio' ), true );
			$this->add_connected( $playlists['video'], 'playlist_to_media', 'media',  array( 'post_mime_type'=>'video' ), true );
		}
		add_filter( 'the_posts', array( $this, 'process_posts' ), 10, 2 );
		return $posts; 			
	}
	
	function register_connections(){
		if ( !function_exists( 'p2p_register_connection_type' ) )
			return;

		p2p_register_connection_type( array(
			'name' => 'playlist_to_media',
			'from' => 'playlist',
			'to' => 'attachment',
			'cardinality' => 'many-to-many',
			'prevent_duplicates' => true,
			'admin_box' => false,
			'to_query_vars' => array( 
				'nopaging' => true,
				'post_status' => 'inherit',
				'connected_orderby' => 'media_order',
				'connected_order' => 'asc',
				'connected_order_num' => true,
			)							
		) );			
	}
}
endif;

function playlist_content( $content ) {
	$playlist = get_post();
	if( 'playlist' !== $playlist->post_type )	
		return $content;
	
	$meta = get_playlist_meta( $playlist->ID );
	$meta['ids'] = get_playlist_media_ids( $playlist->ID );
	$meta['type'] = get_playlist_type( $playlist );
	return wp_playlist_shortcode( $meta ) . $content;
}

add_filter( 'the_content', 'playlist_content' );