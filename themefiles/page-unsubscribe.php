<?php
/*
Template Name: Unsubscribe
*  http://stackoverflow.com/questions/4647604/wp-use-file-in-plugin-directory-as-custom-page-template
 * 
 */

$email = $_GET['email'];

get_header(); ?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
                    <div class="entry-content">
                        <br/><br/>
			<?php
                        if ( ! $email ) { ?>
                            To unsubscribe from Bike Fun emails, click on the unsubscribe link at the bottom of any email.
                            <?php
                        } else {
                            global $wpdb;
                            $query = $wpdb->prepare ( 
                                "SELECT * from " . $wpdb->postmeta . " m" . 
                                " WHERE m.meta_key='bf_subscription_email' AND m.meta_value='%s'",
                                $email
                            );
                            $meta_row = $wpdb->get_row ( $query, 'OBJECT' );
                            if ( $meta_row ) {
                                wp_delete_post( $meta_row->post_id, true );
                                
                                $headers = array();
                                $headers[] = 'From: Bike Fun <info@bikefun.org>';
                                $headers[] = "Content-type: text/html";
                                $subject = "Unsubscribed from Bikefun";
                                $message = "<P>Your address " . $email . " has been unsubscribed from Bike Fun emails.</P>";
                                $message .= "<P>If this comes as a surprise to you, just re-subscribe now.</p>";
                                $message .= "<P><a href='" .  get_site_url() . "'>Subscribe again here</a></P>";
                                $message .= "<P>The IP address of the computer used to unsubscribe you was " . $_SERVER['REMOTE_ADDR'];
                                wp_mail( $email, $subject, $message, $headers );
                                ?>
                                Your email address <?=$email?> has been unsubscribed.<br/>
                                We have sent you one last email to confirm this.
                                <?php
                            } else {
                                ?>
                                Something has gone wrong, we couldn't find your email address to unsubscribe:<br/>
                                <?=$email?> is not subscribed.
                                <?php
                            }
                        }
                        ?>
                    </div>
		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer();