<?php

/**
 * Plugin Name: Rating Content
 * Description: brief
 * Author: Ja3Bu9
 * Author URI: http://www.youtube.com/ja3bu9
 * Textdomain: rc
 */

if( ! defined( 'ABSPATH' ) ) {
	return;
} 

/**
 * Top Level Menu and submenu
 */
function rc_rating_options_page()
{
    // add top level menu page
   	add_menu_page(
        __( 'Ratings', 'rc' ),
        __( 'Ratings', 'rc' ),
        'manage_options',
        'rc_rating',
        'rc_rating_page_html',
        'dashicons-star-empty'
    );

   	add_submenu_page( 
   	 	'rc_rating', 
   	 	__( 'Settings', 'rc' ), 
   	 	__( 'Settings', 'rc' ), 
   	 	'manage_options', 
   	 	'rc_rating_settings', 
   	 	'rc_rating_settings_html'
   	 );
}
add_action('admin_menu', 'rc_rating_options_page');


/**
 * The page to display all rated content
 * @return void 
 */
function rc_rating_page_html() {
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    global $wpdb;

    // SQL query to get all the content which has the meta key 'rc_rating'. Group the content by the ID and get an average rating on each
    $sql = "SELECT * FROM ( SELECT p.post_title 'title', p.guid 'link', post_id, AVG(meta_value) AS rating, count(meta_value) 'count' FROM {$wpdb->prefix}postmeta pm";
    $sql .= " LEFT JOIN wp_posts p ON p.ID = pm.post_id";
    $sql .= " where meta_key = 'rc_rating' group by post_id ) as ratingTable ORDER BY rating DESC";
    
    $result = $wpdb->get_results( $sql, 'ARRAY_A' );
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div id="poststuff">
            <table class="form-table widefat">
                <thead>
                    <tr>
                        <td>
                            <strong><?php _e( 'Content', 'rc' ); ?></strong>
                        </td>
                        <td>
                            <strong><?php _e( 'Rating', 'rc' ); ?></strong>
                        </td>
                        <td>
                           <strong><?php _e( 'No. of Ratings', 'rc' ); ?></strong>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        foreach ( $result as $row ) {
                            
                            echo '<tr>';
                                echo '<td>' . $row['title'] . '<br/><a href="' . $row['link'] . '" target="_blank">' . __( 'View the Content', 'rc' ) . '</a></td>';
                                echo '<td>' . round( $row['rating'], 2 ) . '</td>';
                                echo '<td>' . $row['count'] . '</td>';
                            echo '</tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Registering Settings for Rating Settings
 */
function rc_ratings_settings_init()
{
    // Registering the setting 'rc_rating_types' for the page 'rc_rating_settings'
    register_setting( 'rc_rating_settings', 'rc_rating_types');
 
    // Registering the section 'rc_rating_section' for the page 'rc_rating_settings'
    add_settings_section(
        'rc_rating_section',
        '',
        '',
        'rc_rating_settings'
    );
 
    // Registering the field for the setting 'rc_rating_types' on the page 'rc_rating_settings' under section 'rc_rating_section'
    add_settings_field(
        'rc_rating_types', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Show Rating on Content:', 'wporg'),
        'rc_rating_types_html',
        'rc_rating_settings',
        'rc_rating_section',
        [
            'label_for'         => 'rc_rating_pages',
            'class'             => 'wporg_row',
            'wporg_custom_data' => 'custom',
        ]
    );
}
add_action('admin_init', 'rc_ratings_settings_init');


/**
 * Get all Custom Post Types that are available publicly
 * For each of those add a checkbox to choose 
 * @param  array $args 
 * @return void       
 */
function rc_rating_types_html( $args ) {   
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    
    // get the value of the setting we've registered with register_setting()
    $rating_types = get_option('rc_rating_types', array());
    
    if( ! empty( $post_types ) ) {
        foreach ( $post_types as $key => $value ) {
            $isChecked = in_array( $key, $rating_types );
            echo '<input ' . ( $isChecked ? 'checked="checked"' : '' ) . ' type="checkbox" name="rc_rating_types[]" value="' . $key . '" /> ' . $value->label . '<br/>';
        }
    }
}


/**
 * Displaying the form with our Rating settings
 * @return void 
 */
function rc_rating_settings_html() {
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "rc_rating_settings"
            settings_fields('rc_rating_settings');
    
            // output setting sections and their fields
            do_settings_sections('rc_rating_settings');
    
            // output save settings button
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}


/**
 * Checking for Rating
 * @return void 
 */
function rc_check_for_rating() {
  
    $rating_types = get_option( 'rc_rating_types', array() );

    if( is_array( $rating_types ) && count( $rating_types ) > 0 && is_singular( $rating_types ) ) { 

        $rate_id = get_the_id();
        $ratingCookie = isset( $_COOKIE['rc_rating'] ) ? unserialize( base64_decode( $_COOKIE['rc_rating'] ) ) : array();
        // if( ! in_array( $rate_id, $ratingCookie ) ) { 
        //     // This content has not been rated yet by that user 

        //     add_action( 'wp_enqueue_scripts', 'rc_rating_scripts');
        //     add_action( 'wp_footer', 'rc_rating_render' );
        // }
        add_action( 'wp_enqueue_scripts', 'rc_rating_scripts');
            add_action( 'wp_footer', 'rc_rating_render' );
    }
    
}
add_action( 'template_redirect', 'rc_check_for_rating' );


/**
 * Enqueueing Scripts
 * @return void 
 */
function rc_rating_scripts() { 
    wp_enqueue_style( 'rating-css', plugin_dir_url( __FILE__ ) . 'rating.css', array(), '', 'screen' );
    wp_register_script( 'rating-js', plugin_dir_url( __FILE__ ) . 'rating.js', array('jquery'), '', true );
    wp_localize_script( 'rating-js', 'rc_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'rc_rating' ),
        'text'     => array(
            'close_rating' => __( 'Close Rating', 'rc' ),
            'rate_it' => __( 'Rate It', 'rc' ),
            'choose_rate' => __( 'Choose a Rate', 'rc' ),
            'submitting' => __( 'Submitting...', 'rc' ),
            'thank_you' => __( 'Thank You for Your Rating!', 'rc' ),
            'submit' => __( 'Submit', 'rc' ),
        )
    ));
    wp_enqueue_script( 'rating-js' );
}



add_action( 'wp_ajax_submit_rating', 'rc_submit_rating' );
add_action( 'wp_ajax_nopriv_submit_rating', 'rc_submit_rating' );
/**
 * Submitting Rating
 * @return string  JSON encoded array
 */
function rc_submit_rating() {
    check_ajax_referer( 'rc_rating', '_wpnonce', true );
    $result = array( 'success' => 1, 'message' => '' );

    $ratingCookie = isset( $_COOKIE['rc_rating'] ) ? unserialize( base64_decode( $_COOKIE['rc_rating'] ) ) : array();
    $rate_id = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;
 
    if( ! $ratingCookie ) {
        $ratingCookie = array();
    }
    
    $ratingCookie = array();
    if( $rate_id > 0 ) {

        if( ! in_array( $rate_id, $ratingCookie ) ) {

            $rate_value = isset( $_POST['rating'] ) ? $_POST['rating'] : 0;
            if( $rate_value > 0 ) {
                
                $success = add_post_meta( $rate_id, 'rc_rating', $rate_value );
                
                if( $success ) {

                    $result['message'] = __( 'Thank you for rating!', 'rc' );
                    $ratingCookie[] = $rate_id;
                    $expire = time() + 30*DAY_IN_SECONDS;
                    setcookie( 'rc_rating', base64_encode(serialize( $ratingCookie )), $expire, COOKIEPATH, COOKIE_DOMAIN );
                    $_COOKIE['rc_rating'] = base64_encode(serialize( $ratingCookie ));
                }

            } else {
                $result['success'] = 0;
                $result['message'] = __( 'Something went wrong. Try to rate later', 'rc' );
            }

        } else {
            $result['success'] = 0;
            $result['message'] = __( 'You have already rated this content.', 'rc' );
        }
    } else {
        $result['success'] = 0;
        $result['message'] = __( 'Something went wrong. Try to rate later', 'rc' );
    }

    echo json_encode( $result );
    wp_die();
}



/**
 * Render Rating
 * @return void 
 */
function rc_rating_render() {
     
    $ratingValues = 5;
    global $wpdb;
    $id = get_queried_object_id();

    $sql = "SELECT * FROM $wpdb->postmeta where post_id = $id AND meta_key = 'rc_rating' " ;
   
    
    $result = $wpdb->get_results( $sql, 'ARRAY_A' );

    ?>
   
    <div id="contentRating" class="rc-rating">
        <button type="button" id="toggleRating" class="active">
            <span class="text">
                <?php _e( 'Rate It', 'rc' ); ?>
            </span>
            <span class="arrow"></span>
        </button> 
        <div id="entryRating" class="rc-rating-content active">
            <div class="errors" id="ratingErrors"></div>
            <ul>
            <div class="ratingstar">
            <div class="ratingstars">
            <?php
            $star1 = 0;
            $star2 = 0;
            $star3 = 0;
            $star4 = 0;
            $star5 = 0;
            foreach ( $result as $row ) {
                
                if($row['meta_value']== '1'){
                    $star1++;
                }else if ($row['meta_value']== '2'){
                    $star2++;

                }else if ($row['meta_value']== '3'){
                    $star3++;

                }else if ($row['meta_value']== '4'){
                    $star4++;

                }else if ($row['meta_value']== '5'){
                    $star5++;

                }

                ?>
            <?php } ?>

            <span><?php echo($star1) ?></span>
            <span><?php echo($star2) ?></span>
            <span><?php echo($star3) ?></span>
            <span><?php echo($star4) ?></span>
            <span><?php echo($star5) ?></span>
            
            </div>
            
            <div class="ratingstars">
            <span>★</span>
            <span>★★</span>
            <span>★★★</span>
            <span>★★★★</span>
            <span>★★★★★</span>
            </div>

            </div>
            
                <?php for( $i = 1; $i <= $ratingValues; $i++ ) {
                    echo '<li>';
                        echo '<input type="radio" name="ratingValue" value="' . $i . '" id="rating' . $i . '"/>';;
                        
                        echo '<label for="rating' . $i . '">';
                            echo $i;
                        echo '</label>';
                    echo '</li>';
                }
                ?>
                 
            </ul>
            <button type="button" data-rate="<?php echo get_the_id(); ?>"id="submitRating"><?php _e( 'Submit', 'rc' ); ?></button>
        </div>
    </div>
    <?php
}