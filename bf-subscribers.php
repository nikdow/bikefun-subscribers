<?php
/**
 * Plugin Name: Bikefun subscribers
 * Plugin URI: http://www.cbdweb.net
 * Description: Subscribe to email newsletter
 * Version: 1.0
 * Author: Nik Dow, CBDWeb
 * License: GPL2
 */
defined('ABSPATH') or die("No script kiddies please!");
/*
 * Subscriptions - custom post type and facilities to support the email newsletter
 */

/*
 * Subscription AJAX calls
 */
/* this unsubscribe is not used ! */
add_action( 'wp_ajax_unsubscribe', 'bf_unsubscribe' );
add_action( 'wp_ajax_nopriv_unsubscribe', 'bf_unsubscribe' );

function bf_unsubscribe() {
    $email = $_POST['email'];

    global $wpdb;
    $query = $wpdb->prepare( // find the custom post for this sig
        "SELECT p.ID FROM " . $wpdb->posts . " p "
            . "WHERE p.post_title=%s AND (p.post_status='private' OR p.post_status='draft')",
            $email );
    
    $id = $wpdb->get_col( $query );
    $post = get_post( $id[0], "OBJECT" );
    
    if( ! $post || ! ( $post->post_status === "private" || $post->post_status === "draft" ) ) {
        echo json_encode ( array( 'error'=>'We couldn\'t find that email address. ' ) );
        die;
    }
    
    $subject = "Unsubscribed from " . get_option('bf-organisation');
    $headers = array();
    $headers[] = 'From: ' . get_option('bf-organisation') . ' <' . get_option('newsletter-sender-address') . '>';
    $headers[] = "Content-type: text/html";
    $message = "<P>You have been unsubscribed from ' . get_option('bf-organisation') . ' emails.</P>";
    $message .= "<P>If this comes as a surprise to you, just re-subscribe now.</p>";
    $message .= "<P><a href='" .  get_site_url() . "/subscribe-to-bike-fun-newsletters' target='_blank'>Subscribe again here</a></P>";
    wp_mail( $email, $subject, $message, $headers );
    
    echo json_encode ( array( 'success' => 'One last email is heading your way now, however no further action is required from you.' ) );
    die;
}
   /*
     * Custom template for unsubscribe page
    * 
    * http://stackoverflow.com/questions/4647604/wp-use-file-in-plugin-directory-as-custom-page-template
     */
    add_action("template_redirect", 'unsubscribe_redirect');
    function unsubscribe_redirect() {
        global $post;
        if( $post->post_title === "Unsubscribe") {
            $templatefilename = 'page-unsubscribe.php';
            if (file_exists(TEMPLATEPATH . '/' . $templatefilename)) {
                $return_template = TEMPLATEPATH . '/' . $templatefilename;
            } else {
                $return_template = plugin_dir_path( __FILE__ ) . 'themefiles/' . $templatefilename;
            }
            do_theme_redirect($return_template);
        }
    }
    function do_theme_redirect($url) {
        global $wp_query;
        if (have_posts()) {
            include($url);
            die();
        } else {
            $wp_query->is_404 = true;
        }
    }
/*
 * AJAX call to add a new signature
 */
add_action( 'wp_ajax_newSubscription', 'bf_newSubscription' );
add_action( 'wp_ajax_nopriv_newSubscription', 'bf_newSubscription' );

function bf_newSubscription() {
    $bf_subscription_email = $_POST['bf_subscription_email'];
    $areYouThere = $_POST['areYouThere'];
    if (
        ! isset( $_POST['fs_nonce'] ) 
        || ! wp_verify_nonce( $_POST['fs_nonce'], 'bf_new_subscription' ) 
    ) {

       echo json_encode( array( 'error'=>'Sorry, your nonce did not verify.' ) );
       die;
    }
    
    if($bf_subscription_email === "") {
        echo json_encode( array( 'error'=>'Please supply an email address' ) );
        die;
    }
    global $wpdb;
    // check to see this email isn't already subscribed
    $query = $wpdb->prepare('SELECT p.post_status as `status` FROM ' . $wpdb->posts . ' p WHERE p.post_title="%s"', $bf_subscription_email );
    $results = $wpdb->get_results( $query );
    $found = false;
    foreach( $results as $row ) {
        if( $row->status === 'private' ) $found = true; // for this check we ignore drafts, so people can try again
    }
    if($found) {
        echo json_encode( array('error'=>'That email address is already registered.') );
        die;
    }
    if( $areYouThere !== "y" ) {
        echo json_encode( array('error'=>'Please tick the box to show you are not a robot') );
        die;
    }
    $query = $wpdb->prepare('SELECT p.post_status as `status` FROM ' . $wpdb->posts . ' p WHERE p.post_title="%s"', $bf_subscription_email );
    $results = $wpdb->get_results( $query );
    foreach( $results as $row ) {
        if( $row->status === 'draft' ) $found = true; // for this check we ignore drafts, so people can try again
    }
    if ( $found ) {
        $post_id = $row->ID;
    } else {

        $post_id = wp_insert_post(array(
                'post_title'=>$bf_subscription_email,
                'post_status'=>'draft',
                'post_type'=>'bf_subscription',
                'ping_status'=>false,
                'comment_status'=>'closed',
            ),
            true
        );
        if(is_wp_error($post_id)) {
            echo json_encode( array( 'error'=>$post_id->get_error_message() ) );
            die;
        }
    }
//    update_post_meta($post_id, "bf_subscription_email", $bf_subscription_email );
    
    if(isset($_COOKIE['referrer'])) {
        $referrer = $_COOKIE['referrer'];
    } else {
        $referrer = $_SERVER['HTTP_REFERER'];
    }
    if(strpos($referrer, get_site_url() ) !== false ) $referrer = "";
    if($referrer) update_post_meta ( $post_id, "bf_subscription_referrer", substr( $referrer, 0, 255 ) );
    $secret = generateRandomString();
    update_post_meta ( $post_id, "bf_subscription_secret", $secret );
    
    $subject = "Confirm your subscription to " . get_option('bf-organisation');
    $headers = array();
    $headers[] = 'From: ' . get_option('bf-organisation') . ' <' . get_option('newsletter-sender-address') . '>';
    $headers[] = "Content-type: text/html";
    $message = "<P>Thanks for subscribing to ' . get_option('bf-organisation') . ' emails.</P>";
    $message .= "<P>Before we send you any emails, you need to click on the link below, so we know it wasn't a mistake.</p>";
    $message .= "<P>Don't worry, we don't give your email address to anyone, and you can unsubscribe any time you like.</P>";
    $message .= "<P><a href=". get_site_url() . "'/confirm?secret=" . $secret . "'>Click here to verify your email address and start receiving " . get_option('bf-organisation') . " emails</a></P>";
    wp_mail( $fs_signature_email, $subject, $message, $headers );
    
    echo json_encode( array( 'success'=>'You have successfully registered your email. Look for an email from us and click on the link to confirm your email address - until then we can\'t send you any newsletters.' ) );
    die();
}
/*
 * Create custom post type to store subscriptions
 */
add_action( 'init', 'create_bf_subscription' );
function create_bf_subscription() {
	$labels = array(
        'name' => _x('Subscriptions', 'post type general name'),
        'singular_name' => _x('Subscription', 'post type singular name'),
        'add_new' => _x('Add New', 'events'),
        'add_new_item' => __('Add New Subscription'),
        'edit_item' => __('Edit Subscription'),
        'new_item' => __('New Subscription'),
        'view_item' => __('View Subscription'),
        'search_items' => __('Search Subscriptions'),
        'not_found' =>  __('No subscriptions found'),
        'not_found_in_trash' => __('No subscriptions found in Trash'),
        'parent_item_colon' => '',
    );
    register_post_type( 'bf_subscription',
        array(
            'label'=>__('Subscriptions'),
            'labels' => $labels,
            'description' => 'Each post is one sign-up to the ' . get_option('bf-organisation') . ' email newsletter.',
            'public' => true,
            'can_export' => true,
            'exclude_from_search' => true,
            'has_archive' => true,
            'show_ui' => true,
            'capability_type' => 'post',
            'menu_icon' => "dashicons-welcome-write-blog",
            'hierarchical' => false,
            'rewrite' => false,
            'supports'=> array('title') ,
            'show_in_nav_menus' => true,
            )
    );
}
/*
 * specify columns in admin view of signatures custom post listing
 */
add_filter ( "manage_edit-bf_subscription_columns", "bf_subscription_edit_columns" );
add_action ( "manage_posts_custom_column", "bf_subscription_custom_columns" );
function bf_subscription_edit_columns($columns) {
    $columns = array(
        "cb" => "<input type=\"checkbox\" />",
        "title" => "email",
//        "bf_col_email" => "email address",
        "bf_col_referrer" => "Referrer",
    );
    return $columns;
}
function bf_subscription_custom_columns($column) {
    global $post;
    $custom = get_post_custom();
    switch ( $column ) {
/*        case "bf_col_email":
            echo "<a href='" . admin_url() . "post.php?post=" . $post->ID . "&action=edit'>" . $custom['bf_subscription_email'][0] . "</a>";
            break; */
        case "fs_col_referrer":
            preg_match( '@^(?:http([s]*)://)?([^/]+)@i', $custom["bf_subscription_referrer"][0], $matches );
            $host = $matches[2];
            if( $host ) {
                echo "<a href='" . $custom["bf_subscription_referrer"][0] . "' target='_blank'>http" . $matches[1] . "://" . $host . "</a>";
            } else {
                echo "&nbsp;";
            }
            break;
    }
}
/*
 * Add fields for admin to edit signature custom post
 */
add_action( 'admin_init', 'bf_subscription_create' );
function bf_subscription_create() {
    add_meta_box('bf_subscription_meta', 'Subscription', 'bf_subscription_meta', 'bf_subscription' );
}
function bf_subscription_meta() {
    global $post;
    $custom = get_post_custom( $post->ID );
    $meta_email = $post->post_title; // $custom['bf_subscription_email'][0];
    $meta_referrer = $custom['bf_subscription_referrer'][0];
    
    echo '<input type="hidden" name="bf-subscription-nonce" id="bf-subscription-nonce" value="' .
        wp_create_nonce( 'bf_subscription-nonce' ) . '" />';
    ?>
    <div class="bf-meta">
        <ul>
            <li><label>Referrer</label><input name="bf_subscription_referrer" class="wide" value="<?php echo $meta_referrer; ?>" /></li>
        </ul>
    </div>
    <?php    
}

/*
 * label for title field on custom posts
 */

add_filter('enter_title_here', 'bf_subscription_enter_title');
function bf_subscription_enter_title( $input ) {
    global $post_type;

    if ( 'bf_subscription' === $post_type ) {
        return __( 'Subscriber email' );
    }
    return $input;
}

add_action ('save_post', 'save_bf_subscription');
 
function save_bf_subscription(){
 
    global $post;

    // - still require nonce

    if ( !wp_verify_nonce( $_POST['bf-subscription-nonce'], 'bf_subscription-nonce' )) {
        return $post->ID;
    }

    if ( !current_user_can( 'edit_post', $post->ID ))
        return $post->ID;

    if(!isset($_POST["bf_subscription_email"])):
        return $post;
    endif;
    update_post_meta( $post->ID, "bf_subscription_referrer", $_POST["bf_subscription_referrer"] );
//    update_post_meta($post->ID, "bf_subscription_email", $_POST["bf_subscription_email"]);
}

add_filter('post_updated_messages', 'subscription_updated_messages');
 
function subscription_updated_messages( $messages ) {
 
  global $post, $post_ID;
 
  $messages['bf_subscription'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Subscription updated. <a href="%s">View item</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Subscription updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Subscription restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Subscription recorded. <a href="%s">View Subscription</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Subscription recorded.'),
    8 => sprintf( __('Subscription submitted. <a target="_blank" href="%s">Preview subscription</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Subscription scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview subscription</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Subscription draft updated. <a target="_blank" href="%s">Preview subscription</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );
 
  return $messages;
}
/*
 * Shortcode for confirming subscription
 */
function fs_subscription_confirm () {
    
    $secret = $_GET['secret'];
    $email = $_GET['email'];
    global $wpdb;
    $found = false;
    if($secret!=="") {
        $query = $wpdb->prepare ( "SELECT * FROM " . $wpdb->postmeta . " WHERE meta_value='%s' AND meta_key='bf_subscription_secret'", $secret );
        $row = $wpdb->get_row( $query );
        if( $row ) {
            $post_id = $row->post_id;
            if( $post_id ) {
                $sig = get_post ( $post_id, 'OBJECT' );
                if($sig->post_type === 'bf_subscription' && ( $sig->post_status==="draft" || $sig->post_status==="private" ) ) {
                    $custom = get_post_custom( $post_id );
                    $update = $sig->post_status === "private";

                    $found = true;
                    $sig->post_status = "private";
                    if( ! $update ) update_post_meta( $post_id, 'bf_subscription_registered', date('Y-m-d') );
                    wp_update_post ( $sig );
                }
            }
        }
    }
    ob_start();
    if( ! $found ) { 
        if( $secret ) { ?>
            <P>Sorry, the secret code doesn't match our records and we can't confirm your email.</P>
            <P>The link in our original email to you only works once.  Try subscribing again.</P>
        <?php } else { ?>
            <P>You have reached this page in error</P>
        <?php } 
    } else { ?>
            <P>Thanks for confirming your signature. You will receive email newsletters from <?=get_option('bf-organisation')?> from now on.</P>
    <?php } ?>
    <?php
    return ob_get_clean();
}
add_shortcode('confirm', 'bf_subscription_confirm' );
/* 
 * Shortcode for signature submission form
 */
function bf_subscription_register_script() {
    wp_register_script('subscription', plugins_url( 'js/subscription.js' , __FILE__ ), 'jquery');
    wp_register_style('bf-subscription-styles', plugins_url( 'css/style.css', __FILE__ ) );	
}
function enqueue_subscription_register_script() {	// support for signup form, which appears on two pages and in a popup
    global $add_subscription_register_script;
    if( ! $add_subscription_register_script ) return;
    wp_localize_script('subscription', 'data', array('stylesheetUri' => plugin_dir_url( __FILE__ ), 'ajaxUrl'=> admin_url('admin-ajax.php') ) );
    wp_enqueue_script('subscription');
    wp_enqueue_style('bf-subscription-styles');
}
add_action('init', 'bf_subscription_register_script' );
add_action( 'wp_footer', 'enqueue_subscription_register_script' );
function bf_subscription_sign ( $atts ) { 
    global $add_subscription_register_script;
    $add_subscription_register_script = true;
    
    ob_start() ?>

    <form name="register">
        <table border="0">
            <tbody>
            <tr valign="top"><td class="leftcol">email:</td><td class="rightcol"><input type="email" name="bf_subscription_email" id="email<?=($popup ? "_popup" : "");?>" title="email address"><br>
                <div class="smallfont">An email will be sent to you to confirm this address, to ensure you own this email address.</div></td></tr>
            <tr><td class="leftcol"><input id="simpleTuring<?=($popup ? "_popup" : "");?>" name="areYouThere" type="checkbox" value="y" class="inputc"></td><td class="medfont">Tick this box to show you are not a robot</td></tr>
            <tr><td colspan="2"><button type="button" id="saveButton">Save</button></td></tr>
            <tr><td colspan="2"><div id="ajax-loading" class="farleft"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></div></td></tr>
            <tr><td colspan="2"><div id="returnMessage"></div></td></tr>
        </tbody></table>
        <input name="action" value="newSubscription" type="hidden">
        <?php wp_nonce_field( "bf_new_subscription", "fs_nonce");?>
    </form>
<?php return ob_get_clean();
}
add_shortcode('subscription', bf_subscription_sign );

function fs_subscriptions_enqueue_scripts(  ) {
    global $post;
    if( $post->post_type !== 'bf_subscription' ) return;
    wp_enqueue_style( 'admin-style', plugins_url( 'css/admin-style.css' , __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'fs_subscriptions_enqueue_scripts' );
/*
 * set cookies for referrer and campaign
 */
add_action( 'wp_head', 'bf_subscription_head_cookies' );
function bf_subscription_head_cookies() {
    if(isset($_COOKIE['referrer'])) {
        $referrer = $_COOKIE['referrer'];
    } else {
        $referrer = $_SERVER['HTTP_REFERER'];
        setcookie('referrer', $referrer, 0, '/' );
    }
}