<?php
/*
Template Name: Unsubscribe
*  http://stackoverflow.com/questions/4647604/wp-use-file-in-plugin-directory-as-custom-page-template
 * 
 */

$email = $_GET['email'];
if ( ! $email ) $email = $_POST['email'];

get_header(); ?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
                    
                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>

                    </header><!-- .entry-header -->

                    <div class="entry-content">
                        <?php while ( have_posts() ) : the_post(); ?>
                            
                            <?php the_content(); ?>
                        <?php endwhile; ?>
                    </div><!-- .entry-content -->
                    
                    <div class="entry-content">
			<?php
                        if ( ! $email ) { ?>
                            <form name='getemail' action='' method='post'>
                                <label>email address to unsubscribe</label> <input name='email' type='email'/><br/>
                                <input type="submit" name="Submit" class="button-primary" value="Unsubscribe" />
                            </form>
                            <?php
                        } else {
                            global $wpdb;
                            $query = $wpdb->prepare ( 
                                "SELECT * from " . $wpdb->posts . " p" . 
                                " WHERE p.post_title='%s'",
                                $email
                            );
                            $post = $wpdb->get_row ( $query, 'OBJECT' );
                            if ( $post ) {
                                wp_delete_post( $post->ID );
                                
                                $headers = array();
                                $headers[] = 'From: ' . get_option('newsletter-sender-name') . ' <' . get_option('newsletter-sender-address') . '>';
                                $headers[] = "Content-type: text/html";
                                $subject = "Unsubscribed from " . get_option('bf-organisation');
                                $message = "<P>Your address " . $email . " has been unsubscribed from " . get_option('bf-organisation') . " emails.</P>";
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
                <footer class="entry-meta">
                        <?php edit_post_link( __( 'Edit', 'twentythirteen' ), '<span class="edit-link">', '</span>' ); ?>
                </footer><!-- .entry-meta -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer();