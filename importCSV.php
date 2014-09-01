<?php 
/*template name: importCSV*/
?>

<div class="container-wrap">

	<div class="container main-content">
		
		<div class="row liftup">
                    
                    <?php
                    // http://stackoverflow.com/questions/5813168/how-to-import-csv-file-in-php
                    $row = 1;
                    global $wpdb;
                    if (($handle = fopen("/home/lamp/webroot/freestyle/wordpress/wp-content/themes/salient-child/freestyle.csv", "r")) !== FALSE) {
                        while (($data = fgetcsv($handle)) !== FALSE) {
                            $num = count($data);
                            $row++;
                            
                            if($row % 100 === 0 ) echo "row = " . $row . "<br/>\n";
                            
                            $query = $wpdb->prepare ( "SELECT * from " . $wpdb->posts . " p LEFT JOIN " . $wpdb->post_meta . " m on m.post_id=p.ID where meta_key='fs_signature_email' AND meta_value='%s'",
                                    $data[2] );
                            $rows = $wpdb->get_row ( $query );
                            if( ! $rows ) {

                                $post_id = wp_insert_post(array(
                                        'post_title'=>$data[3],
                                        'post_status'=>'private',
                                        'post_type'=>'fs_signature',
                                        'ping_status'=>false,
                                        'post_excerpt'=>$data[8],
                                        'comment_status'=>'closed',
                                    ),
                                    true
                                );
                                
                                if($data[3] === "Hendrikje Krone" ) { 
                                    echo $data[3] . "<br/>\n";
                                    echo "row = " . $row . "<br/>\n";
                                    echo "post_id = " . $post_id . "<br/>\n";
                                }
                                
                                if(is_wp_error($post_id)) {
                                    echo $row . $post_id->get_error_message();
                                }
                                update_post_meta($post_id, "fs_signature_country", $data[5] );
                                if($data[5]==="AU") {
                                    update_post_meta($post_id, "fs_signature_state", $data[6] );
                                }
                                update_post_meta($post_id, "fs_signature_email", $data[2] );
                                $public = $data[4];
                                if($public) update_post_meta($post_id, "fs_signature_public", $public );
                                $newsletter = $data[9];
                                if($newsletter) update_post_meta($post_id, "fs_signature_newsletter", $newsletter );

                                $referrer = substr( $data[1], 0, 255 );
                                if(strpos($referrer, 'freestylecyclists.org') !== false ) $referrer = "";
                                if($referrer) update_post_meta ( $post_id, "fs_signature_referrer", $referrer );

                                list($day, $month, $year) = explode("/", $data[0] );
                                $day = str_pad( $day, 2, "0", STR_PAD_LEFT );
                                $month = str_pad ( $month, 2, "0", STR_PAD_LEFT );
                                update_post_meta($post_id, "fs_signature_registered", $year . "-" . $month . "-" . $day );

                                update_post_meta($post_id, "fs_signature_moderate", $data[7]);

                                $campaign = substr( $data[10], 0, 255 );
                                if($campaign) update_post_meta( $post_id, "fs_signature_campaign", $campaign );
                            }
                        }
                        fclose($handle);
                    }?>
                    
                </div><!--/row-->

        </div><!--/container-->
        

    </div>
