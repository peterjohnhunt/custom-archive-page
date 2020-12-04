<?php
/*
Plugin Name: Custom Archive Page
Plugin URI: https://peterjohnhunt.com/
Description: Attach any page to a post type as a custom archive page
Version: 1.0.0
Author: PeterJohnHunt
Author URI: https://peterjohnhunt.com/
License: GPLv2 or later
Text Domain: custom-archive-page
*/


//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
// ✅ Get Custom Archive Pages
//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
/**
 * Retrieve the post types and/or taxonomies that are
 * currently assigned to pages
 * 
 * @param array $include
 *  List of custom page object types to retrieve
 * 
 * @return array
 */
function get_custom_archive_pages( $include = ['post_types'] ) {
    $objects = get_post_types( ['_builtin' => false], 'objects' );

    $objects = array_filter($objects, function($object) {
        return isset( $object->rewrite['custom_page'] );
    });

    return wp_list_pluck( $objects, 'rewrite', 'label' );
}


//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
// ✅ Set Post Type Args
//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
/**
 * Set page field post type args
 * 
 * @param array $args
 *  List of arguments for post type
 * @param string $name
 *  Name of post type
 * 
 * @return array
 */
function cap_set_page_field_post_type_args($args, $name) {
    $archive = isset($args['has_archive']) ? $args['has_archive'] : false;

    if ( $archive === 'custom_page' ) {
        if (( $page_id = get_option( "page_for_{$name}s" )) && get_post( $page_id )) {
            $args['has_archive'] = get_page_uri($page_id);
        } else {
            $args['has_archive'] = $name;
        }
        
        $args['rewrite']['custom_page'] = "page_for_{$name}s";
        $args['rewrite']['post_type']   = $name;
    }

    return $args;
}
add_filter( 'register_post_type_args', 'cap_set_page_field_post_type_args', 10, 2 );

//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
// ✅ Add Page Field to Settings
//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
/**
 * Add '{Post Type} Page' Field to Settings
 * 
 * This method adds a 'Page For' field to the admin
 * 'Reading Settings' page for each registered custom
 * post type and taxonomy. Setting a page for each of
 * these fields provides the same type of functionality
 * as setting the homepage or posts page.
 */
function cap_add_page_field_to_settings() {
    $custom_pages = get_custom_archive_pages();

    if ( !$custom_pages ) return;

    foreach ($custom_pages as $label => $rewrite) {
        
        $option = $rewrite['custom_page'];

        register_setting('reading', $option, [
            'show_in_rest' => true,
            'type'         => 'integer',
            'description'  => __( $label ),
            'default'      => 10,
        ]);

        add_settings_field(
            $option,
            "{$label} Page",
            'cap_render_page_field',
            'reading',
            'default',
            $option
        );
    }
}
add_action( 'admin_init', 'cap_add_page_field_to_settings' );


//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
// ✅ Render Page Field
//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
/**
 * Function to render new page field
 * 
 * @param string $option
 *  Name of option selected
 */
function cap_render_page_field($option) {
    echo wp_dropdown_pages([
        'name'              => $option,
        'echo'              => 0,
        'show_option_none'  => __( '&mdash; Select &mdash;' ),
        'option_none_value' => '0',
        'selected'          => get_option( $option )
    ]);
}




//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
// ✅ Display Page Field Status
//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
/**
 * Display page's page field status
 * 
 * This method displays that a page has been set as a
 * post type or taxonomy's designated archive page.
 * 
 * @return array
 */
function cap_display_page_field_status( $post_states, $post ) {
    $custom_pages = get_custom_archive_pages();

    if (!$custom_pages) return $post_states;
    
    foreach ($custom_pages as $label => $rewrite) {
        $option = $rewrite['custom_page'];
        if ( intval( get_option( $option ) ) === $post->ID ) {
            $post_states[$option] = __( "{$label} Page" );
        }
    }

    return $post_states;
}
add_filter( 'display_post_states', 'cap_display_page_field_status', 10, 2 );



//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
// ✅ Setup Page Field Postdata
//≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡≡
/**
 * Setup postdata for Page Field in Reading Settings
 * 
 * This method sets up the 'page_for_{$post_type}s'
 * field data, similarly to 'page_for_posts'.
 */
function cap_setup_page_field_postdata() {
    if ( is_search() || !(is_archive() || is_home() || is_category() || is_tax()) ) return;

    global $post;

    if ( !$post ) return;

    $post_id = get_option("page_for_{$post->post_typ}s");

    if ( !$post_id ) return;
    
    $_post = get_post($post_id);

    if ( !$_post ) return;

    setup_postdata($_post);
}
add_action( 'wp_head', 'cap_setup_page_field_postdata' );