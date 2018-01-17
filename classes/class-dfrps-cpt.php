<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'Dfrps_Cpt' ) ) {

/**
 * Add Product Set Custom Post Type.
 */
class Dfrps_Cpt {
	
	public function __construct() {
		add_action( 'save_post', 												array( $this, 'save_post' ), 10, 1 );		
		add_action( 'admin_head-post-new.php', 									array( $this, 'posttype_admin_head_scripts' ) );	
		add_action( 'admin_head-post.php', 										array( $this, 'posttype_admin_head_scripts' ) );
		add_action( 'admin_menu', 												array( $this, 'remove_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', 									array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', 												array( $this, 'meta_boxes' ) );
		add_action( 'manage_' . DFRPS_CPT . '_posts_custom_column',  			array( $this, 'column_fields'), 10, 2 );
		add_action( 'restrict_manage_posts', 									array( $this, 'restrict_manage_posts_by_type' ) );
		add_action( 'admin_head', 												array( $this, 'hide_view_button' ) );
		add_action( 'wp_before_admin_bar_render', 								array( $this, 'remove_view_button_admin_bar' ) );
		add_action( 'admin_menu', 												array( $this, 'add_custom_meta_boxes' ) );
		add_action( 'admin_menu', 												array( $this, 'admin_menu' ) );
		add_action( 'wp_trash_post', 											array( $this, 'wp_trash_product_set' ) );
		add_action( 'transition_post_status', 									array( $this, 'update_next_update_time_on_publish' ), 10, 3 );
		add_action( 'transition_post_status', 									array( $this, 'update_next_update_time_on_trash_to_publish' ), 10, 3 );
		add_action( 'transition_post_status', 									array( $this, 'update_next_update_time_on_unpublish' ), 10, 3 );
		add_action( 'transition_post_status', 									array( $this, 'set_type_on_publish' ), 10, 3 );
		add_action( 'add_meta_boxes', 											array( $this, 'remove_wpseo_meta_box' ), 99999999999999999 );
		
		add_filter( 'manage_edit-' . DFRPS_CPT . '_columns', 					array( $this, 'column_headers' ) );
		add_filter( 'manage_edit-' . DFRPS_CPT . '_sortable_columns', 			array( $this, 'column_sorts' ) );
		add_filter( 'request', 													array( $this, 'column_orderby' ) );
		add_filter( 'postbox_classes_'.DFRPS_CPT.'_div_dfrps_tabs', 			array( $this, 'remove_postbox_classes' ) );
		add_filter( 'postbox_classes_'.DFRPS_CPT.'_div_dfrps_tab_search', 		array( $this, 'remove_postbox_classes' ) );
		add_filter( 'postbox_classes_'.DFRPS_CPT.'_div_dfrps_tab_saved_search', array( $this, 'remove_postbox_classes' ) );
		add_filter( 'postbox_classes_'.DFRPS_CPT.'_div_dfrps_tab_saved_search', array( $this, 'hide_meta_box' ) );
		add_filter( 'postbox_classes_'.DFRPS_CPT.'_div_dfrps_tab_included', 	array( $this, 'hide_meta_box' ) );
		add_filter( 'postbox_classes_'.DFRPS_CPT.'_div_dfrps_tab_blocked', 		array( $this, 'hide_meta_box' ) );
		add_filter( 'enter_title_here', 										array( $this, 'enter_title_here' ) );
		add_filter( 'admin_body_class', 										array( $this, 'admin_body_class' ), 99999 );
		add_filter( 'page_row_actions', 										array( $this, 'remove_view_row_action' ), 10, 1 );		
		add_filter( 'wpseo_use_page_analysis', 									array( $this, 'remove_all_wpseo_stuff' ) );
		add_filter( 'post_updated_messages', 									array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', 								array( $this, 'bulk_post_updated_messages' ), 10, 2 );
		add_filter( 'parse_query', 												array( $this, 'sort_posts_by_next_update' ), 10 );
		//add_filter( 'parse_query', 												array( $this, 'display_only_active_types' ), 11 );
		add_filter( 'parse_query', 												array( $this, 'filter_sets_by_type' ), 12 );
	}

	/**
	 * Sort Product Sets by next update time ASC (or the set that's next scheduled to update first).
	 */
	function sort_posts_by_next_update( $query ) {
		global $pagenow;
		if ( is_admin() && $query->query['post_type'] == DFRPS_CPT && $pagenow == 'edit.php' ) {
			if ( !isset( $_GET['orderby'] ) && !isset( $_GET['post_status'] ) ) {
				$query->query_vars['post_status'] = 'publish';
				$query->query_vars['order'] = 'asc';
				$query->query_vars['orderby'] = 'meta_value';
				$query->query_vars['meta_key'] = '_dfrps_cpt_next_update_time';
			}
		}
	}
	
	/**
	 * Filters Product Sets by type (ie. product, coupon, etc...).
	 * 
	 * @link http://wordpress.stackexchange.com/questions/137168/programmatically-set-meta-query-for-filter
	 * @link http://www.smashingmagazine.com/2013/12/05/modifying-admin-post-lists-in-wordpress/
	 */
	function filter_sets_by_type( $query ) {
		global $pagenow;
		if ( is_admin() && $query->query['post_type'] == DFRPS_CPT && $pagenow == 'edit.php' ) {
			if ( isset( $_GET['_dfrps_cpt_type'] ) && !empty( $_GET['_dfrps_cpt_type'] ) ) {
				$qv = &$query->query_vars;
				$qv['meta_query'] = array();
				$qv['meta_query'][] = array(
					'field' 	=> '_dfrps_cpt_type',
					'value' 	=> $_GET['_dfrps_cpt_type'],
					'compare' 	=> '=',
					'type' 		=> 'CHAR',
				);
			}
		}
	}
	
	/**
	 * Don't display Product Sets if its importer plugin has been deactivated.
	 * 
	 * Example, if the Datafeedr WooCommerce Importer plugin is deactivated, then
	 * the _dfrps_cpt_type (registered types) of 'product' will be removed from the 
	 * $regsitered_cpts. We must then hide Product Sets which are set to import into 
	 * the 'product' post type.
	 *
	 * Leave this in as we might use it later.
	 * 
	 * @link http://wordpress.stackexchange.com/a/160018/34155
	 */
	function display_only_active_types( $query ) {
		global $pagenow;
    	if ( is_admin() && $query->query['post_type'] == DFRPS_CPT && $pagenow == 'edit.php' ) {
			$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
			$cpts = array_keys( $registered_cpts );
			$types = implode( "','", $cpts );			
			$qv = &$query->query_vars;
			$qv['meta_query'] = array();
			$qv['meta_query'][] = array(
				'field' 	=> '_dfrps_cpt_type',
				'value' 	=> $cpts,
				'compare' 	=> 'IN',
				'type' 		=> 'CHAR',
			);
		}	
	}
	
	/**
	 * Set _dfrps_cpt_type when Set is published.
	 */
	function set_type_on_publish( $new_status, $old_status, $post ) {
	
		// Make sure this is of type 'datafeedr-productset'.
		$post_type = get_post_type( $post );
		if ( $post_type != DFRPS_CPT ) {
			return;
		}
		
		if ( $new_status == 'publish' ) {
			update_post_meta( $post->ID, '_dfrps_has_been_published', TRUE );
			$configuration = (array) get_option( DFRPS_PREFIX.'_configuration' );
			$default_cpt = $configuration['default_cpt'];
			add_post_meta( $post->ID, '_dfrps_cpt_type', $default_cpt, TRUE );			
		}		
	}
	
	/**
	 * If $old_status is: 'pending', 'draft', 'auto-draft', 'private'
	 * And $new_status is: 'publish'
	 * Then change "_dfrps_cpt_next_update_time" to NOW.
	 */
	function update_next_update_time_on_publish( $new_status, $old_status, $post ) {
		$valid_new_statuses = array( 'publish', 'future' );
		$valid_old_statuses = array( 'pending', 'draft', 'auto-draft', 'private' );
		if ( in_array( $old_status, $valid_old_statuses ) && in_array( $new_status, $valid_new_statuses ) && $post->post_type == DFRPS_CPT ) {
			update_post_meta( $post->ID, '_dfrps_cpt_next_update_time', date_i18n( 'U' ) );
		}
	}
	
	/**
	 * If $old_status is: 'trash'
	 * And $new_status is: 'publish'
	 * Then change "_dfrps_cpt_next_update_time" to NOW + 5 minutes.
	 */
	function update_next_update_time_on_trash_to_publish( $new_status, $old_status, $post ) {
		$valid_new_statuses = array( 'publish', 'future' );
		$valid_old_statuses = array( 'trash' );
		if ( in_array( $old_status, $valid_old_statuses ) && in_array( $new_status, $valid_new_statuses ) && $post->post_type == DFRPS_CPT ) {
			update_post_meta( $post->ID, '_dfrps_cpt_next_update_time', ( date_i18n( 'U' ) + 300 ) );
		}
	}
	
	/**
	 * If $old_status is: 'publish'
	 * And $new_status is: 'pending', 'draft', 'private'
	 * Then change "_dfrps_cpt_next_update_time" to FAR FUTURE
	 */
	function update_next_update_time_on_unpublish( $new_status, $old_status, $post ) {
		$valid_new_statuses = array( 'pending', 'draft', 'private' );
		if ( in_array( $new_status, $valid_new_statuses ) && ( $old_status == 'publish' ) && ( $post->post_type == DFRPS_CPT ) ) {
			update_post_meta( $post->ID, '_dfrps_cpt_next_update_time', 3314430671 );
		}
	}

	
	function post_updated_messages( $messages ) {
		// Copied from here: ~/wp-admin/edit-form-advanced.php
		global $post;
		if ($post->post_status == 'future') {
			$future_time = get_post_time( 'M j, Y @ G:i', false, $post );
		} else {
			$future_time = false;
		}
		$messages[DFRPS_CPT] = array(
			 0 => '', // Unused. Messages start at index 1.
			 1 => __( 'Product set updated', DFRPS_DOMAIN ),
			 2 => __( 'Custom field updated.', DFRPS_DOMAIN ),
			 3 => __( 'Custom field deleted.', DFRPS_DOMAIN ),
			 4 => __( 'Product set updated', DFRPS_DOMAIN ),
			 5 => isset($_GET['revision']) ? sprintf( __( 'Product set restored to revision from %s', DFRPS_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			 6 => __( 'Product set published.', DFRPS_DOMAIN ),
			 7 => __( 'Product set saved.', DFRPS_DOMAIN ),
			 8 => __( 'Product set submitted.', DFRPS_DOMAIN ),
			 9 => __( 'Product set scheduled for: ', DFRPS_DOMAIN ) . '<strong>' . $future_time . '</strong>',
			10 => __( 'Product set draft updated.', DFRPS_DOMAIN ),
		);
		return $messages;
	}
	
	function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
		// Copied from here: ~/wp-admin/edit.php
		$bulk_messages[DFRPS_CPT] = array(
			'updated'   => _n( '%s Product Set updated.', '%s Product Sets updated.', $bulk_counts['updated'] ),
			'locked'    => _n( '%s Product Set not updated, somebody is editing it.', '%s Product Sets not updated, somebody is editing them.', $bulk_counts['locked'] ),
			'deleted'   => _n( '%s Product Set permanently deleted.', '%s Product Sets permanently deleted.', $bulk_counts['deleted'] ),
			'trashed'   => _n( '%s Product Set moved to the Trash. Its products will be deleted from your store in about 5 minutes.', '%s Product Sets moved to the Trash. Their products will be deleted from your store in about 5 minutes.', $bulk_counts['trashed'] ),
			'untrashed' => _n( '%s Product Set restored from the Trash.', '%s Product Sets restored from the Trash.', $bulk_counts['untrashed'] ),
		);
		return $bulk_messages;
	}	
	
	/**
	 * Get rid of WordPress SEO metabox - adapted from http://wordpress.stackexchange.com/a/91184/2015
	 */
	function remove_wpseo_meta_box() {
		remove_meta_box( 'wpseo_meta', DFRPS_CPT, 'normal' );
	}
	
	function remove_all_wpseo_stuff() {
	
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == DFRPS_CPT ) {
			return '__return_false';
		}
		
		if ( isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
			$post_type = get_post_type( $_GET['post'] );
			if ( $post_type == DFRPS_CPT ) {
				return '__return_false';
			}
		}
		
		return true;
	}
	
	function admin_menu() {
		add_submenu_page(
			'dfrps',
			__( 'Add a Product Set', DFRPS_DOMAIN ), 
			__( 'Add Product Set', DFRPS_DOMAIN ), 
			'edit_product_sets', 
			'post-new.php?post_type=' . DFRPS_CPT, 
			'' 
		);
	}
		
	/**
	 * Hides the 'view' button in the post edit page
	 * Hide "view" links on CPT page: https://gist.github.com/grappler/6046201
	 */
	function hide_view_button() {
		$current_screen = get_current_screen();
		if ( $current_screen->post_type === DFRPS_CPT ) {
			echo '<style>#edit-slug-box{ display: none; }</style>';
		}
		return;
	}
 
	/**
	 * Removes the 'view' link in the admin bar
	 * Hide "view" links on CPT page: https://gist.github.com/grappler/6046201
	 */
	function remove_view_button_admin_bar() {
		global $wp_admin_bar;
		if ( get_post_type() === DFRPS_CPT ) {
			$wp_admin_bar->remove_menu( 'view' );
		}
	}
 
	/**
	 * Removes the 'view' button in the posts list page
	 * Hide "view" links on CPT page: https://gist.github.com/grappler/6046201
	 *
	 * @param $actions
	 */
	function remove_view_row_action( $actions ) {
		if ( get_post_type() === DFRPS_CPT ) {
			unset( $actions['view'] );
		}
		return $actions;
	}
		
	/**
	 * This adds our metaboxes for selecting CPTs 
	 * as well as choosing categories.
	 * 
	 * ~/wp-admin/edit-form-advanced.php (Line #129)
	 * @http://sixtyonedesigns.com/using-ajax-in-wordpress-admin-meta-boxes/
	 */
	function add_custom_meta_boxes() {
	
		// Show Dashboard
		add_meta_box(
			'dfrps_cpt_dashboard_metabox', 
			_x( 'Dashboard', DFRPS_DOMAIN ), 
			array( $this, 'cpt_dashboard_metabox' ), 
			DFRPS_CPT, 
			'side', 
			'high', 
			array()
		);
			
		// Show CPT picker (or message if no registered CPTs exist).
		add_meta_box(
			'dfrps_cpt_picker_metabox', 
			_x( 'Import Into', DFRPS_DOMAIN ), 
			array( $this, 'cpt_picker_metabox' ), 
			DFRPS_CPT, 
			'side', 
			'default', 
			array()
		);
		
		$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
		
		if ( !empty( $registered_cpts ) ) {
			
			foreach ( $registered_cpts as $cpt => $value ) { 
				
				$metabox_id = 'dfrps_' . $registered_cpts[$cpt]['post_type'] . '_' . $registered_cpts[$cpt]['taxonomy'] . '_category_chooser';
				
				add_meta_box(
					$metabox_id, 
					$registered_cpts[$cpt]['tax_name'], 
					array( $this, 'category_metabox' ), 
					DFRPS_CPT, 
					'side', 
					'default', 
					array( 'cpt' => $registered_cpts[$cpt] )
				);
				
				add_filter( 'postbox_classes_' . DFRPS_CPT . '_' . $metabox_id, array( $this, 'add_metabox_class_for_categories' ) );
			}
		}	
	}
	
	function add_metabox_class_for_categories( $classes=array() ) {
		if( !in_array( 'dfrps_category_metabox', $classes ) ) {
        	$classes[] = 'dfrps_category_metabox';
        }
    	return $classes;
	}
	
	function cpt_dashboard_metabox( $post, $box ) {
		echo '
		<div id="dfrps_dynamic_dashboard_area">
			<div id="dfrps_db_loading"></div>
		</div>
		<div id="dfrps_dashboard_actions">
			<p><span class="dashicons dashicons-visibility"></span> <a href="' . add_query_arg( array( 'post_status' => 'publish', 'post_type' => DFRPS_CPT, 'orderby' => 'next_update', 'order' => 'asc' ), admin_url( 'edit.php') ) . '">' . __( 'View the update queue', DFRPS_DOMAIN ) . '</a></p>
			<p><span class="dashicons dashicons-plus"></span> <a href="' . add_query_arg( array( 'post_type' => DFRPS_CPT ), admin_url( 'post-new.php') ) . '">' . __( 'Add a new Product Set', DFRPS_DOMAIN ) . '</a></p>
		</div>
		';
	}
		
	function cpt_picker_metabox( $post, $box ) {
		
		// Get registered CPTs.
		$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
		$num_registered_cpts = count( $registered_cpts );
		
		if ( $num_registered_cpts == 0 ) {
		
			// There are no registered CPTs, so just return.
		
			echo '<p class="dfrps_warning">';
			_e( 'Uh-oh! You haven\'t registered any Custom Post Types to use with the Datafeedr Product Sets plugin.', DFRPS_DOMAIN );
			echo '</p><p class="dfrps_warning">';
			_e( 'Without a registered Custom Post Type we don\'t know how to import your product information.', DFRPS_DOMAIN );
			echo '</p><p class="dfrps_warning">';
			_e( 'Get an Importer Plugin ', DFRPS_DOMAIN );
			echo '<a href="' . admin_url('plugins.php') . '" target="_blank">';
			_e( ' here', DFRPS_DOMAIN );
			echo '</a>.</p>';
		
		} elseif ( $num_registered_cpts == 1 ) {
		
			// Set _dfrps_cpt_type
			dfrps_set_cpt_type_to_default( $post->ID );
		
			// There's just 1 registered CPT so make that the "default".
			$post_type = array_keys( $registered_cpts );
			$cpt = $post_type[0];
			echo '<label><input type="radio" name="_dfrps_cpt_type" value="' . $cpt . '" checked="checked" /> ' . $registered_cpts[$cpt]['name'].'</label>';
			
		} else {
		
			// There's more than 1 registered CPT, so "check" the default and allow user to check others.
			$configuration = (array) get_option( DFRPS_PREFIX.'_configuration' );
			$default_cpt = $configuration['default_cpt'];
			
			$import_into = get_post_meta( $post->ID, '_dfrps_cpt_type', TRUE );
			$has_been_published = get_post_meta( $post->ID, '_dfrps_has_been_published', TRUE );
		
			// Loop through registered CPTs.
			foreach ( $registered_cpts as $cpt => $values ) {
				echo '<label>';
				if ( empty( $import_into ) ) {		
					dfrps_set_cpt_type_to_default( $post->ID ); // Set _dfrps_cpt_type				
					echo '<input class="dfrps_cpt_picker" id="dfrps_'.$cpt.'_'.$registered_cpts[$cpt]['taxonomy'].'_category" type="radio" name="_dfrps_cpt_type" value="' . $cpt . '" '.checked( $default_cpt, $cpt, false ).' /> ' . $registered_cpts[$cpt]['name'] . '<br />';
				} else {
					if ( $cpt == $import_into ) {
						echo '<input class="dfrps_cpt_picker" id="dfrps_'.$cpt.'_'.$registered_cpts[$cpt]['taxonomy'].'_category" type="radio" name="_dfrps_cpt_type" value="' . $cpt . '" checked="checked" /> ' . $registered_cpts[$cpt]['name'] . '<br />';
					} else {
						if ( $has_been_published ) {
							echo '<input disabled="disabled" class="dfrps_cpt_picker" id="dfrps_'.$cpt.'_'.$registered_cpts[$cpt]['taxonomy'].'_category" type="radio" name="_dfrps_cpt_type" value="' . $cpt . '" /> ' . $registered_cpts[$cpt]['name'] . ' <span class="dfrps_type_disabled">(' . __( 'disabled', DFRPS_DOMAIN ) . ')</span><br />';
						} else {
							echo '<input class="dfrps_cpt_picker" id="dfrps_'.$cpt.'_'.$registered_cpts[$cpt]['taxonomy'].'_category" type="radio" name="_dfrps_cpt_type" value="' . $cpt . '" /> ' . $registered_cpts[$cpt]['name'] . '<br />';
						}
					}
				}
				echo '</label>';
			}
				
			if ( $num_registered_cpts > 1 ) {
				echo '<div class="dfrps_type_disabled_explanation"><small>';
				_e( 'The "Import Into" field cannot be modified after a Product Set has been published.', DFRPS_DOMAIN ); 
				echo '</small></div>';
			}
		}		
	}	

	/**
	 * Display post categories form fields.
	 *
	 * @since 2.6.0
	 *
	 * @param object $post
	 */
	 // ~/wp-admin/includes/meta-boxes.php (Line #375)
	function category_metabox( $post, $box ) {

        $cpt_name = $box['args']['cpt']['name'];
        $cpt_type = $box['args']['cpt']['post_type'];
        $taxonomy = $box['args']['cpt']['taxonomy'];
        $tax_name = $box['args']['cpt']['tax_name'];
        $tax_instructions = $box['args']['cpt']['tax_instructions'];
		$tax = get_taxonomy( $taxonomy );
		$name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
		
		$selected_cats = dfrps_get_cpt_terms( $post->ID ); // Ticket: 9167
		
		?>
	
		<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
			
			<div class="dfrps_tax_instructions"><?php echo $tax_instructions; ?></div>
			
			<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel dfrps_category_selection_panel">
				<div class="dfrps_saving_taxonomy"><?php _e( 'Saving&hellip;', DFRPS_DOMAIN ); ?></div>
				<?php echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.?>
				<ul id="<?php echo $taxonomy; ?>checklist" data-wp-lists="list:<?php echo $taxonomy?>" class="categorychecklist form-no-clear" cpt="<?php echo $cpt_type; ?>">
					<?php wp_terms_checklist(false, array( 'selected_cats' => $selected_cats, 'taxonomy' => $taxonomy ) ) ?>
				</ul>
			</div>
		
			<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
				<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
					<h4><a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js">
						<?php printf( __( '+ %s' ), $tax->labels->add_new_item ); ?>
					</a></h4>
					<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
						<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" aria-required="true"/>
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent"><?php echo $tax->labels->parent_item_colon; ?></label>
						<?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;' ) ); ?>
						<input type="button" id="<?php echo $taxonomy; ?>-add-submit" data-wp-lists="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" />
						<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
						<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
	<?php
	}
	
	/**
	 * Adds "datafeedr-productset_admin" CSS class to all CPT pages.
	 */
	function admin_body_class( $classes ) {
		global $post;
		if ( is_object( $post ) ) {
			$post_type = get_post_type( $post->ID );
			$post_class = $post_type . '_admin';
			if ( $post_type == DFRPS_CPT ) {
				if ( is_array($classes) ) {
					$classes[] = $post_class;
				} else {
					$classes .= " {$post_class}";
				}
			}
		}
		return $classes;
	}

	/**
	 * Adds the table headers on this page: edit.php?post_type=datafeedr-productset
	 	Title
		Created
		Modified
		Status
		Next Update
		Started
		Completed
		Products
		API Requests
	 * 
	 * Unset any WPSEO stuff, too.
	    [wpseo-score] => SEO
		[wpseo-title] => SEO Title
		[wpseo-metadesc] => Meta Desc.
		[wpseo-focuskw] => Focus KW

	 */
	function column_headers( $columns ) {
	
		unset( $columns['date'] );
		unset( $columns['wpseo-score'] );
		unset( $columns['wpseo-title'] );
		unset( $columns['wpseo-metadesc'] );
		unset( $columns['wpseo-focuskw'] );

		$columns['title'] = __( 'Set Name', DFRPS_DOMAIN );
		$columns['created'] = __( 'Created', DFRPS_DOMAIN );
		$columns['modified'] = __( 'Modified', DFRPS_DOMAIN );
		$columns['post_status'] = __( 'Status', DFRPS_DOMAIN );
		$columns['_dfrps_cpt_next_update_time'] = __( 'Next Update', DFRPS_DOMAIN );
		$columns['_dfrps_cpt_last_update_time_started'] = __( 'Started', DFRPS_DOMAIN );
		$columns['_dfrps_cpt_last_update_time_completed'] = __( 'Completed', DFRPS_DOMAIN );
		$columns['_dfrps_cpt_last_update_num_products_added'] = __( 'Added', DFRPS_DOMAIN );
		$columns['_dfrps_cpt_last_update_num_products_deleted'] = __( 'Deleted', DFRPS_DOMAIN );
		$columns['_dfrps_cpt_last_update_num_api_requests'] = __( 'API Requests', DFRPS_DOMAIN );

		return $columns;
	}

	/**
	 * This determines what to show in each column on this page: edit.php?post_type=datafeedr-productset
	 */
	function column_fields( $column, $post_id ) {
		
		$post 				= $GLOBALS['post'];
		$meta 				= get_post_custom( $post_id );
		
		$update_phase 		= intval( $meta['_dfrps_cpt_update_phase'][0] );
		$next_update_time 	= ( isset( $meta['_dfrps_cpt_next_update_time'][0] ) ) ? $meta['_dfrps_cpt_next_update_time'][0] : 0;
		$started 			= ( isset( $meta['_dfrps_cpt_last_update_time_started'][0] ) ) ? $meta['_dfrps_cpt_last_update_time_started'][0] : 0;
		$completed 			= ( isset( $meta['_dfrps_cpt_last_update_time_completed'][0] ) ) ? $meta['_dfrps_cpt_last_update_time_completed'][0] : 0;
		$products_added 	= ( isset( $meta['_dfrps_cpt_last_update_num_products_added'][0] ) ) ? number_format( intval( $meta['_dfrps_cpt_last_update_num_products_added'][0] ) ) : 0;
		$api_requests 		= ( isset( $meta['_dfrps_cpt_last_update_num_api_requests'][0] ) ) ? number_format( intval( $meta['_dfrps_cpt_last_update_num_api_requests'][0] ) ) : 0;
		$products_deleted 	= ( isset( $meta['_dfrps_cpt_last_update_num_products_deleted'][0] ) ) ? number_format( intval( $meta['_dfrps_cpt_last_update_num_products_deleted'][0] ) ) : 0;
		$update_errors 		= ( isset( $meta['_dfrps_cpt_errors'][0] ) ) ? unserialize( $meta['_dfrps_cpt_errors'][0] ) : '';
		$registered_cpts    = get_option( 'dfrps_registered_cpts', array() );

		$type = ( isset( $registered_cpts[$meta['_dfrps_cpt_type'][0]]['name'] ) ) ? $registered_cpts[$meta['_dfrps_cpt_type'][0]]['name'] : $meta['_dfrps_cpt_type'][0];
		$type = '<div class="dfrps_cpt_type" title="This Product Set imports into the ' . esc_attr( '"' . $meta['_dfrps_cpt_type'][0] . '"' ) . ' post type.">' . $type . '</div>';

		// Display 'inactive' message and CSS class for any type that is no longer registered.
		$active_status = ( dfrps_set_is_active( $meta['_dfrps_cpt_type'][0] ) )
			? '<div class="dfrps_cpt_active" title="This Product Set is active and regularly performing product imports/updates.">Active</div>'
			: '<div class="dfrps_cpt_inactive" title="This Product Set is no longer importing/updating products.">Inactive</div>';
		
		switch ( $column ) {

			case 'created':
				$post_date = dfrps_date_in_two_rows( $post->post_date );
				echo '<abbr title="' . __( 'This Product Set was created on ', DFRPS_DOMAIN ) . $post->post_date . '">' . $post_date . '</abbr>';
				break;

			case 'modified':
				$post_modified = dfrps_date_in_two_rows( $post->post_modified );
				echo '<abbr title="' . __( 'This Product Set was modified on ', DFRPS_DOMAIN ) . $post->post_modified . '">' . $post_modified . '</abbr>';
				break;

			case 'post_status':
				// Most of this code taken from:
				// ~/wp-admin/includes/class-wp-posts-list-table.php
				if ( '0000-00-00 00:00:00' == $post->post_date ) {
					$time_diff = 0;
				} else {
					$time = get_post_time( 'G', true, $post );
					$time_diff = time() - $time;
				}

				echo '<div class="dfrps_post_status">';
				if ( 'publish' == $post->post_status ) {
					_e( 'Published', DFRPS_DOMAIN );
				} elseif ( 'future' == $post->post_status ) {
					if ( $time_diff > 0 )
						echo '<strong class="attention">' . __( 'Missed schedule', DFRPS_DOMAIN ) . '</strong>';
					else
						_e( 'Scheduled', DFRPS_DOMAIN );
				} else {
					_e( 'Unpublished', DFRPS_DOMAIN );
				}
				_e( '</div>');

				echo $type;
				echo $active_status;

				break;

			case '_dfrps_cpt_next_update_time':
				if ( $post->post_status == 'publish' ) {
				
					if ( $next_update_time == 0 ) {
						echo '<abbr title="' . __( 'This Product Set will update as soon as possible.', DFRPS_DOMAIN ) . '">ASAP</abbr>';
					} else {
						//echo date_i18n( 'M d, g:ia', $next_update_time );
						//$next_update_time = dfrps_date_in_two_rows( $next_update_time );
						echo '<abbr title="' . __( 'This Product Set will update on (or after) ', DFRPS_DOMAIN ) . date_i18n( 'Y-m-d G:i:s', $next_update_time ) . '">' . dfrps_date_in_two_rows( $next_update_time ) . '</abbr>';
					}
				} else {
					echo '&mdash;';
				}
				break;

			case '_dfrps_cpt_last_update_time_started':
				if ( $started > 0 ) {
					echo '<abbr title="' . __( 'This Product Set\'s last update started on ', DFRPS_DOMAIN ) . date_i18n( 'Y-m-d G:i:s', $started ) . '">' . dfrps_date_in_two_rows( $started ) . '</abbr>';
				} else {
					_e( 'Never', DFRPS_DOMAIN );
				}
				break;

			case '_dfrps_cpt_last_update_time_completed':
				if ( $update_errors != '' ) {
					echo dfrapi_output_api_error( $update_errors );
				} else {
					if ( $update_phase == 0 ) {
						if ( $completed > 0 ) {
							echo '<abbr title="' . __( 'This Product Set\'s last update completed on ', DFRPS_DOMAIN ) . date_i18n( 'Y-m-d G:i:s', $completed ) . '">' . dfrps_date_in_two_rows( $completed ) . '</abbr>';			
						} else {
							_e( 'Never', DFRPS_DOMAIN );
						}
					} else {
						$percent_complete = dfrps_percent_complete( $post_id );
						echo '<span class="dfrps_currently_updating">' . __( 'Updating&hellip;', DFRPS_DOMAIN ) . '</span>';
						if ( $percent_complete ) {
							echo dfrps_progress_bar( $percent_complete );
						}
					}
				}
				break;

			case '_dfrps_cpt_last_update_num_products_added':
				echo '<div class="dfrps_label dfrps_label-success" title="' . $products_added . __( ' products were added during the last update of this Product Set.', DFRPS_DOMAIN ) . '">' . $products_added . '</div>';
				break;

			case '_dfrps_cpt_last_update_num_products_deleted':
				echo '<div class="dfrps_label dfrps_label-danger" title="' . $products_deleted . __( '  products were moved to the Trash during the last update of this Product Set.', DFRPS_DOMAIN ) . '">' . $products_deleted . '</div>';
				break;

			case '_dfrps_cpt_last_update_num_api_requests':
				echo '<div class="dfrps_label dfrps_label-warning" title="' . $api_requests . __( ' API requests were required during the last update of this Product Set.', DFRPS_DOMAIN ) . '">' . $api_requests . '</div>';
				break;
		}
	}
	
	/**
 	 * Register the column as sortable
 	 * @http://scribu.net/wordpress/custom-sortable-columns.html
 	 */
	function column_sorts( $columns ) {
		$columns['created'] = 'created';
		$columns['modified'] = 'modified';
		$columns['post_status'] = 'status';
		$columns['_dfrps_cpt_next_update_time'] = 'next_update';
		$columns['_dfrps_cpt_last_update_time_started'] = 'started';
		$columns['_dfrps_cpt_last_update_time_completed'] = 'last_update';
		$columns['_dfrps_cpt_last_update_num_products_added'] = 'num_products';
		$columns['_dfrps_cpt_last_update_num_api_requests'] = 'api_requests';
		$columns['_dfrps_cpt_last_update_num_products_deleted'] = 'products_deleted';
		return $columns;
	}
	
	/**
	 * Modify query to handle sorts.
	 */
	function column_orderby( $vars ) {
		
		// Next Update Column
		if ( isset( $vars['orderby'] ) && 'next_update' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => '_dfrps_cpt_next_update_time',
				'orderby' => 'meta_value_num'
			) );
		}
		
		// Started Column
		if ( isset( $vars['orderby'] ) && 'started' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => '_dfrps_cpt_last_update_time_started',
				'orderby' => 'meta_value_num'
			) );
		}
		
		// Last Update Column
		if ( isset( $vars['orderby'] ) && 'last_update' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => '_dfrps_cpt_last_update_time_completed',
				'orderby' => 'meta_value_num'
			) );
		}
		
		// Number of Products
		if ( isset( $vars['orderby'] ) && 'num_products' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => '_dfrps_cpt_last_update_num_products_added',
				'orderby' => 'meta_value_num'
			) );
		}
		
		// Number of API Requests
		if ( isset( $vars['orderby'] ) && 'api_requests' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => '_dfrps_cpt_last_update_num_api_requests',
				'orderby' => 'meta_value_num'
			) );
		}
		
		// Number of Deleted Products
		if ( isset( $vars['orderby'] ) && 'products_deleted' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => '_dfrps_cpt_last_update_num_products_deleted',
				'orderby' => 'meta_value_num'
			) );
		}
 
		return $vars;
	}
	
	/**
	 * Add filter to list of Product Sets to filter by what the Product Set is importing into.
	 * 
	 * http://en.bainternet.info/2013/add-taxonomy-filter-to-custom-post-type
	 */
	function restrict_manage_posts_by_type() {

   		if ( !is_admin() ) { return ''; }
   		if ( !isset( $_GET['post_type'] ) ) { return ''; }
    	if ( $_GET['post_type'] != DFRPS_CPT ) { return ''; }

		// Are there any registered CPTs?
		$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
		if ( empty( $registered_cpts ) ) { return ''; }

		// Do we have more than 1 CPT?
		$cpts = array_keys( $registered_cpts );

		if ( isset( $_GET['_dfrps_cpt_type'] ) && !empty( $_GET['_dfrps_cpt_type'] ) ) { 
			$type = sanitize_key( $_GET['_dfrps_cpt_type'] );
			$type = substr( $type, 0, 20 );
		} else {
			$type = '';
		}
				
		echo "<select name='_dfrps_cpt_type' id='dfrps_type_filter' class='postform'>";
		echo "<option value=''>" . __( 'Show all types', DFRPS_DOMAIN ) . "</option>";
		foreach ( $cpts as $cpt ) {
			echo '<option value="'. $cpt . '" ' . selected( $cpt, $type ) . '>' . $registered_cpts[$cpt]['name'] .'</option>';
		}
		echo "</select>";
	}
	
	/**
	 * Set the next update time to 5 minutes in the future so a user has time Restore
	 * this product set from the Trash.
	 * 
	 * Also, reset update phase back to 0. This prevents the set from being ignored if
	 * the phase is greater than 2, as the delete function only has 2 phases. (#8705)
	 * 
	 * Also, delete first_pass information.
	 */
	function wp_trash_product_set( $post_id ) {
		$this->trashed_set_id = $post_id;
		update_post_meta( $post_id, '_dfrps_cpt_next_update_time', ( date_i18n( 'U' ) + 300 ) );
		dfrps_reset_product_set_update( $post_id );
	}
	
	/**
	 * This saves the data added in meta/custom fields and saves it to the database.
	 * @http://codex.wordpress.org/Function_Reference/add_meta_box
	 * @http://codex.wordpress.org/Plugin_API/Action_Reference/save_post
	 */
	function save_post( $post_id ) {
	
		// Make sure this is of type 'datafeedr-productset'.
		$post_type = get_post_type( $post_id );
		if ( $post_type != DFRPS_CPT ) {
			return;
		}
				
		add_post_meta( $post_id, '_dfrps_cpt_update_phase', 0, true );
				
		if ( isset( $_POST['post_status'] ) && $_POST['post_status'] != 'publish' && $_POST['post_status'] != 'future' ) {
			//add_post_meta( $post_id, '_dfrps_cpt_next_update_time', 3314430671, true );
		} else {
			if ( isset( $_POST['ID'] ) ) {
				$publish_time = get_the_time( 'U', $_POST['ID'] );
				//add_post_meta( $post_id, '_dfrps_cpt_next_update_time', $publish_time, true );
			}	
		}
		
		add_post_meta( $post_id, '_dfrps_cpt_last_update_time_started', 0, true );
		add_post_meta( $post_id, '_dfrps_cpt_last_update_time_completed', 0, true );
		add_post_meta( $post_id, '_dfrps_cpt_last_update_num_api_requests', 0, true );
		add_post_meta( $post_id, '_dfrps_cpt_last_update_num_products_added', 0, true );
		
	}
	
	/**
	* This changes the input title which appears when the title field is blank.
	* @http://wp-snippets.com/change-enter-title-here-text-for-custom-post-type/
	*/
	function enter_title_here( $title ){
		if  ( DFRPS_CPT == get_post_type() ) {
			 $title = __( 'Enter product set title here', DFRPS_DOMAIN );
		}
		return $title;
	}

	/**
	 * Remove "slug" option from Screen Options area.
	 * @http://wordpress.stackexchange.com/a/2281
	 */	
	function remove_meta_boxes() {
		remove_meta_box( 'slugdiv', DFRPS_CPT, 'core' );
	}

	/**
	* This hides a few things on the admin add/edit pages
	* - slug box
	* - the link to "preview" post when post is updated
	* - visibility link in "publish" meta box.
	* - preview and view buttons.
	* 
	* PLUS some other random styling stuff...
	* 
	* @http://wpsnipp.com/index.php/functions-php/hide-post-view-and-post-preview-admin-buttons/
	*/
	function posttype_admin_head_scripts() {
		if  ( DFRPS_CPT != get_post_type() ) { echo ''; return; }
		require_once ( DFRPS_PATH . 'functions/cpt-ajax.php' );
	}
	
	/**
	* This changes the "Publish" button to "Save Product Set" on add/edit post page.
	* @http://wordpress.stackexchange.com/a/36115/34155
	*/
	function change_publish_button( $translation, $text ) {
		if  ( DFRPS_CPT == get_post_type() ) {
			if ( $text == 'Publish' ) {
				return __( 'Import Products', DFRPS_DOMAIN );
			} elseif ( $text == 'Update' ) {
				return __( 'Update Status', DFRPS_DOMAIN );
			}
		}
		return $translation;
	}
	
	/**
	* Prevent autosave on specific content type.
	* @http://ryansechrest.com/2012/09/prevent-autosave-on-a-custom-post-type-in-wordpress/
	*/
	function admin_enqueue_scripts() {
		if  ( DFRPS_CPT == get_post_type() ) {
			//wp_dequeue_script( 'autosave' );
			//wp_register_style( 'dfrps_cpt_js', DFRPS_URL . 'css/searchform.css', false, DFRPS_VERSION );
			//wp_enqueue_style( 'dfrapi_searchform' );
		}
	}
	
	/**
	 * Adds the tabs to the layout just under the title field.
	 * Adds the search form.
	 * Adds the search results area.
	 */
	function meta_boxes() {		
		
		// Add the update status meta box if it's not a new set.
		if ( !preg_match( "/post-new.php/i", $_SERVER['SCRIPT_NAME'] ) ) {
			add_meta_box(
				'div_dfrps_last_update_status', 
				_x( 'Last Update...', DFRPS_DOMAIN ),
				array( $this, 'last_update_status' ), 
				DFRPS_CPT, 
				'normal', 
				'default', 
				array()
			);
		}
	
		// Add the tabs meta box.
		add_meta_box(
			'div_dfrps_tabs', 
			'These are tabs', 
			array( $this, 'tabs' ), 
			DFRPS_CPT, 
			'normal', 
			'default', 
			array()
		);
		
		// Add the search form meta box.
		add_meta_box(
			'div_dfrps_tab_search', 
			'Product Search Form', 
			array( $this, 'search_form' ), 
			DFRPS_CPT, 
			'normal', 
			'default', 
			array()
		);
		
		// Add the saved search meta box.
		add_meta_box(
			'div_dfrps_tab_saved_search', 
			'Saved Search Products Tabbed Area', 
			array( $this, 'saved_search_products' ), 
			DFRPS_CPT, 
			'normal', 
			'default', 
			array()
		);
		
		// Add the included products meta box.
		add_meta_box(
			'div_dfrps_tab_included', 
			'Included Products Tabbed Area', 
			array( $this, 'included_products' ), 
			DFRPS_CPT, 
			'normal', 
			'default', 
			array()
		);
		
		// Add the blocked products meta box.
		add_meta_box(
			'div_dfrps_tab_blocked', 
			'Blocked Products Tabbed Area', 
			array( $this, 'blocked_products' ), 
			DFRPS_CPT, 
			'normal', 
			'default', 
			array()
		);
	}
	
	function saved_search_products() {
		echo 'saved search here';		
	}
	
	function included_products() {
		echo 'included products here';
	}
	
	function blocked_products() {
		echo 'blocked products here';
	}
	
	function last_update_status() {
	
		$post 				= $GLOBALS['post'];
		$meta 				= get_post_custom( $post->ID );
		$completed 			= $meta['_dfrps_cpt_last_update_time_completed'][0];
		$update_errors 		= ( isset( $meta['_dfrps_cpt_errors'][0] ) ) ? maybe_unserialize( $meta['_dfrps_cpt_errors'][0] ) : '';
				
		// Show last update stats
		if ( $completed == 0 ) {
			// Is updating now or has never updated.
			// Show values from '_dfrps_cpt_previous_update_info' meta field if exists.
			$meta = ( isset( $meta['_dfrps_cpt_previous_update_info'][0] ) ) ? maybe_unserialize( $meta['_dfrps_cpt_previous_update_info'][0] ) : array();
		}
		
		if ( !isset( $meta['_dfrps_cpt_last_update_time_completed'][0] ) ) {
		
			echo __( 'This Product Set has never been imported.', DFRPS_DOMAIN );
		
		} else {
		
			if ( version_compare( phpversion(), '5.3.0', '>=') ) {
				$datetime1 = new DateTime('@' . $meta['_dfrps_cpt_last_update_time_started'][0]);
				$datetime2 = new DateTime('@' . $meta['_dfrps_cpt_last_update_time_completed'][0]);
			   	$interval  = $datetime1->diff($datetime2);
				$elapsed   = $interval->format('%y years, %m months, %a days, %h hours, %i minutes, %S seconds');
				$elapsed   = str_replace(array('0 years,', ' 0 months,', ' 0 days,',  ' 0 hours,', ' 0 minutes,'), '', $elapsed);
				$elapsed   = str_replace(array('1 years, ', ' 1 months, ', ' 1 days, ',  ' 1 hours, ', ' 1 minutes'), array('1 year, ', '1 month, ', ' 1 day, ', ' 1 hour, ', ' 1 minute'), $elapsed);
			}
						
			if ( version_compare( phpversion(), '5.3.0', '>=') ) {
				$elapsed_row = '
				<tr class="alternate">
					<td class="row-title">' . __( 'Elapsed Time', DFRPS_DOMAIN ) . '</td>
					<td class="desc">' . $elapsed. '</td>
				</tr>
				';
			}
			
			if ( is_array( $update_errors ) && !empty( $update_errors ) ) {
				echo dfrapi_output_api_error( $update_errors );
			}
			
			echo '
			<table class="widefat last_update_table" cellspacing="0">
				<tbody>
					<tr class="alternate">
						<td class="row-title">' . __( 'Started', DFRPS_DOMAIN ) . '</td>
						<td class="desc">' . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ) , $meta['_dfrps_cpt_last_update_time_started'][0] ) . '</td>
					</tr>
					<tr>
						<td class="row-title">' . __( 'Completed', DFRPS_DOMAIN ) . '</td>
						<td class="desc">' . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ) , $meta['_dfrps_cpt_last_update_time_completed'][0] ) . '</td>
					</tr>
					' . $elapsed_row . '
					<tr>
						<td class="row-title">' . __( 'API Requests', DFRPS_DOMAIN ) . '</td>
						<td class="desc">' . number_format( $meta['_dfrps_cpt_last_update_num_api_requests'][0] ) . '</td>
					</tr>
					<tr class="alternate">
						<td class="row-title">' . __( 'Products Added', DFRPS_DOMAIN ) . '</td>
						<td class="desc">' . number_format( $meta['_dfrps_cpt_last_update_num_products_added'][0] ) . '</td>
					</tr>
					<tr>
						<td class="row-title">' . __( 'Products Deleted', DFRPS_DOMAIN ) . '</td>
						<td class="desc">' . number_format( $meta['_dfrps_cpt_last_update_num_products_deleted'][0] ) . '</td>
					</tr>
				</tbody>
			</table>
			';
		}
	}
	
	/**
	 * Tabs layout for "Search", "Included" and "Excluded" products.
	 */
	function tabs() {
		?>		
		<h2 class="nav-tab-wrapper" id="dfrps_cpt_tabs">
			<a href="#" class="nav-tab nav-tab-active" id="tab_search">
				<?php _e( 'Search', DFRPS_DOMAIN ); ?>
			</a>
			<a href="#" class="nav-tab tab_disabled" id="tab_saved_search">
				<?php _e( 'Saved Search', DFRPS_DOMAIN ); ?>
				<span class="loading"> ....... </span>
				<span class="count" title="<?php _e( 'Products added to this Product Set via a Saved Search.', DFRPS_DOMAIN ); ?>">0</span>
			</a>
			<a href="#" class="nav-tab tab_disabled" id="tab_included">
				<?php _e( 'Single Products', DFRPS_DOMAIN ); ?>
				<span class="loading"> ....... </span>
				<span class="count" title="<?php _e( 'Products individually added to this Product Set.', DFRPS_DOMAIN ); ?>">0</span>
			</a>
			<a href="#" class="nav-tab tab_disabled" id="tab_blocked">
				<?php _e( 'Blocked Products', DFRPS_DOMAIN ); ?>
				<span class="loading"> ....... </span>
				<span class="count" title="' . __( 'Products blocked from this Product Set.', DFRPS_DOMAIN ) ); ?>">0</span>
			</a>
		</h2>
		<?php
	}
	
	/**
	* Add CSS class to meta boxes.
	* @http://wordpress.stackexchange.com/a/49784/34155
	*/
	function remove_postbox_classes( $classes ) {
		array_push( $classes, 'dfrps_meta_box', 'no_box' );
		return $classes;
	}
	
	/**
	* Add CSS class to meta boxes.
	* @http://wordpress.stackexchange.com/a/49784/34155
	*/
	function hide_meta_box( $classes ) {
		array_push( $classes, 'dfrps_meta_box', 'dfrps_hidden', 'no_box' );
		return $classes;
	}
	
	function get_search_form_defaults( $post_id ) {
		
		$saved_query = get_post_meta( $post_id, '_dfrps_cpt_query', true );
		if ( $saved_query != '' ) {
			return $saved_query;
		}

		$temp_query = get_post_meta( $post_id, '_dfrps_cpt_temp_query', true );
		if ( $temp_query != '' ) {
			return $temp_query;
		}
		
		$configuration = (array) get_option( DFRPS_PREFIX.'_configuration' );
		$default_filters = $configuration['default_filters'];
		if ( !empty( $default_filters ) ) {
			return $configuration['default_filters']['dfrps_query'];
		}
		
		return false;		
	}
	
	function search_form() { ?>	
		<div id="dfrps_search_form_wrapper" class="stuffbox">
			<div class="instructions" style="display: none;">
				<a href="#" id="dfrps_search_instructions_toggle"><?php _e( 'search help...', DFRPS_DOMAIN ); ?></a>
				<div style="display:none" id="dfrps_search_instructions">
					<div id="dfrps_search_instructions_wrapper">
						<h2><?php _e( 'Search Help', DFRPS_DOMAIN ); ?></h2>
						<h3><?php _e( 'Filters', DFRPS_DOMAIN ); ?></h3>
						<p><?php _e( 'Use filters to search and filter lists of products. ', DFRPS_DOMAIN ); ?></p> 
						<table class="widefat" cellspacing="0">
							<tbody>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Any field', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on "Text" fields.  Generally "Text" fields are: product name, product description, brand, etc...', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'Product name', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on the product name.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Brand', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on the product\'s brand name. Not every product has a brand name.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'Description', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on the product description field.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Tags', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on the product tags.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'Product type', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on type: product or coupon. Note that in order to use this filter, you should have already selected merchants that provide this type of products. For example, if you choose "coupon" but you have not selected any Merchants, your search will return an error.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Currency', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on their currency code. Not every product has a currency code.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'Price', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on their price.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Sale Price', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on their sale price.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'Network', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on the affiliate network they are from.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Merchant', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products based on the merchant they are from.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'On Sale', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products by whether or not they are on sale.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Discount', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products by the percent of discount. For example, if you only want products that are on sale and the discount is greater than 20%, choose the "greater than" operator and type "20".', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'Has Image', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products by whether or not they have an image. Note that if a merchant provides an image URL in their data feed but that image URL is broken, the product will still be returned in your search results.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Last updated', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Filter products by the last time they were updated in our database. You can use about any English textual datetime description. For examples, see PHP\'s ', DFRPS_DOMAIN ); ?><a href="http://php.net/strtotime" target="_blank">strtotime()</a> <?php _e( 'function.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'Limit', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Return a limited number of products. Note that the maximum number of products that will be returned is 10,000 products.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'Sort By', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Sort the search results by various parameters.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'Exclude Duplicates', DFRPS_DOMAIN ); ?></td>
									<td class="desc">
										<?php _e( 'Exclude products that have duplicate fields. Possible values are: ', DFRPS_DOMAIN ); ?>
										<br />
										<tt>name, brand, currency, price, saleprice, source_id, merchant_id, onsale, image, thumbnail</tt>
										<br /><br />
										<strong><?php _e( 'Examples:', DFRPS_DOMAIN ); ?></strong>
										<br />
										<tt>name image</tt> - <?php _e( 'Exclude products with the same name AND the same image URL.', DFRPS_DOMAIN ); ?><br />
										<tt>name|image</tt> - <?php _e( 'Exclude products with the same name OR the same image URL.', DFRPS_DOMAIN ); ?><br />
										<tt>merchant_id name|image</tt> - <?php _e( 'Exclude products which have the same merchant AND their name OR image URL are the same.', DFRPS_DOMAIN ); ?>
									</td>
								</tr>
							</tbody>
						</table>
						<h3><?php _e( 'Filter Operators', DFRPS_DOMAIN ); ?></h3>
						<p><?php _e( 'Use these filter operators to modify your search filters. ', DFRPS_DOMAIN ); ?></p> 
						<table class="widefat" cellspacing="0">
							<thead>
								<tr>
									<th><?php _e( 'Filter Operators', DFRPS_DOMAIN ); ?></th>
									<th><?php _e( 'Description', DFRPS_DOMAIN ); ?></th>
									<th><?php _e( 'Character Operators', DFRPS_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'contains', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Return products which have the keyword(s).', DFRPS_DOMAIN ); ?></td>
									<td class="operators">
										<tt>= |</tt>
									</td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( "doesn't contain", DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Return products which do not have the keyword(s).', DFRPS_DOMAIN ); ?></td>
									<td class="operators">
										<tt>= |</tt>
									</td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'starts with', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Return products which keyword(s) start with a specific word.', DFRPS_DOMAIN ); ?></td>
									<td class="operators">
										<tt>= |</tt>
									</td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'ends with', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Return products which keyword(s) end with a specific word.', DFRPS_DOMAIN ); ?></td>
									<td class="operators">
										<tt>= |</tt>
									</td>
								</tr>
								<tr class="alternate">
									<td class="row-title"><?php _e( 'matches', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Allows use of all available operators.', DFRPS_DOMAIN ); ?></td>
									<td class="operators">
										<tt>= | ^ $ "&hellip;"</tt>
									</td>
								</tr>
								<tr>
									<td class="row-title"><?php _e( 'is', DFRPS_DOMAIN ); ?></td>
									<td class="desc"><?php _e( 'Return products that match an exact word (stemming is still in effect).', DFRPS_DOMAIN ); ?></td>
									<td class="operators">
										<tt>= |</tt>
									</td>
								</tr>
							</tbody>
						</table>
						
						<h3><?php _e( 'Character Operators', DFRPS_DOMAIN ); ?></h3>
						<p><?php _e( 'Use these filter operators to further modify your filter operators. ', DFRPS_DOMAIN ); ?></p> 
						<table class="widefat" cellspacing="0">
							<thead>
								<tr>
									<th><?php _e( 'Character', DFRPS_DOMAIN ); ?></th>
									<th><?php _e( 'Description', DFRPS_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr class="alternate">
									<td class="operators"><tt>=</tt></td>
									<td class="desc"><?php _e( 'Perform an exact word search.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="operators"><tt>|</tt></td>
									<td class="desc"><?php _e( 'Perform a search with an OR operator with the pipe symbol.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="operators"><tt>^</tt></td>
									<td class="desc"><?php _e( 'Perform a search that begin with a specific word.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr>
									<td class="operators"><tt>$</tt></td>
									<td class="desc"><?php _e( 'Perform a search that ends with a specific word.', DFRPS_DOMAIN ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="operators"><tt>"&hellip;"</tt></td>
									<td class="desc"><?php _e( 'Perform a phrasal search by surrounding a phrase in double quotes.', DFRPS_DOMAIN ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div style="margin-bottom: 10px;"> </div>
			<?php
			$sform = new Dfrapi_SearchForm();
			echo $sform->render( '_dfrps_cpt_query', $this->get_search_form_defaults ( get_the_ID() ) );
			?>
			<div class="actions">
				<span class="dfrps_raw_query"><a href="#" id="dfrps_view_raw_query"><?php _e( 'view api request', DFRPS_DOMAIN ); ?></a></span>
				<input name="search" type="submit" class="button" id="dfrps_cpt_search" value="<?php echo __( 'Search', DFRPS_DOMAIN ); ?>" />
				<div id="dfrps_save_update_search_actions">
					<?php
					$saved_query = get_post_meta( get_the_ID(), '_dfrps_cpt_query', true );
					$add_update_button_text = ( $saved_query ) ? __( 'Update Saved Search', DFRPS_DOMAIN ) : __( 'Add as Saved Search', DFRPS_DOMAIN );
					?>
					<input type="submit" class="button button-primary" id="dfrps_cpt_save_search" value="<?php echo $add_update_button_text; ?>" />
				</div>
			</div>
		</div>
		<div id="div_dfrps_tab_search_results"></div>
		<?php
	}
	
}



} // class_exists check