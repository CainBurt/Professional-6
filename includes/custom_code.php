<?php

/* ----------------------------------------------------
            Contact Forms 7 Hooks
   ---------------------------------------------------- */
   add_action('wp_ajax_get_shirt_sizes_action', 'get_shirt_sizes');
   add_action('wp_ajax_nopriv_get_shirt_sizes_action', 'get_shirt_sizes');
   
   function get_shirt_sizes() {
       
       $gender  = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : null;
   
       $maleSizesAvialable = array(
           "XS" => 10,
           "S" => 10,
           "M" => 10,
           "L" => 9, 
           "XL" => 10,
       );
   
       $femaleSizesAvailable = array(
           "XS" => 10,
           "S" => 10,
           "M" => 10,
           "L" => 10, 
           "XL" => 10,
       );
   
       if($gender) {
           $args = array(  
               'post_type' => 'flamingo_inbound',
               'post_status' => 'publish',
               'suppress_filters' => true,
               'numberposts'   => -1,
               'order'         => 'ASC'
           );
           
           $all_posts = get_posts( $args );
           
           if($all_posts)
           {
               foreach ( $all_posts as $post ) {
                   $metas = get_post_meta( $post->ID );
                   if(!empty($metas['_field_hidden_size'][0])) {
                       $size = $metas['_field_hidden_size'][0];
                       if(strtolower($gender) == 'male' && strtolower($metas['_field_hidden_gender'][0]) == 'male' ) {
                          $maleSizesAvialable[$size] = $maleSizesAvialable[$size] - 1;
                       }
                       if(strtolower($gender) == 'female' && strtolower($metas['_field_hidden_gender'][0]) == 'female') {
                          $femaleSizesAvailable[$size] = $femaleSizesAvailable[$size] - 1;
                       }
                   }   
               }
   
           }
       }
   
       if(strtolower($gender) == 'male') {
           $result = $maleSizesAvialable;
       }
       if(strtolower($gender) == 'female') {
           $result = $femaleSizesAvailable;
       }
       
       echo json_encode($result);
   
       wp_die();
   }
   add_filter( 'wpcf7_validate_email*', 'custom_duplicate_email_filter', 20, 2 );
function custom_duplicate_email_filter( $result, $tag ) 
{ 
    $emailCtr = 0;
    $skateboard_conter = 0;
    $is_user_blocked = 0;
    $event_slots = 1;
    $email  = isset($_POST['customer-email']) ? sanitize_text_field($_POST['customer-email']) : null;
    $activity = isset($_POST['activity']) ? sanitize_text_field($_POST['activity']) : null;
    //$event_date = isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : null;
    
    // first check user is blocked or not
    $user_block =  check_user_blocked(strtolower($email), $activity);
    if($user_block == 1)
    {
        $is_user_blocked = 1;
    }
    else if($email) 
    {
        $args = array(  
            'post_type' => 'flamingo_inbound',
            'post_status' => 'publish',
            'suppress_filters' => true,
            'numberposts'   => -1,
            'order'         => 'ASC',
           // 'meta_key' => '_from_email',
           // 'meta_value' => strtolower($email),
           'meta_query'    =>  array(
                                    array(
                                        'key' => '_from_email',
                                        'value' => strtolower($email),
                                    ),
                                ),
        );
        $all_posts = get_posts( $args );
        if($all_posts)
        {
            
            foreach ( $all_posts as $post ) 
            {
                // for walkin
                $main_event = get_post_meta( $post->ID, '_field_main_event', true);
                $check_activity = get_post_meta( $post->ID, '_field_activity', true);
                if($main_event == 'Walk In'){
                    $check_activity = $check_activity[0];
                }
                if($check_activity == $activity)
                {
                    if($activity != 'skateboard')
                    {
                        $emailCtr++;
                        break;   
                    }
                    else
                    {
                        $skateboard_conter++;
                        if($skateboard_conter >= 2)
                            break;
                    }
                }
            }

        }
    }
    if($emailCtr > 0) {
        $result->invalidate( $tag, "This email address already exist." );
    }
    else if($skateboard_conter >= 2)
    {
        $result->invalidate( $tag, "This email address already used twice." );
    } 
    else if($is_user_blocked == 1)
        $result->invalidate( $tag, "Due to not attending a previous session registered for, you will be unable to register for 1 subsequent session. After 1 subsequent session, you will be able to resume with registrations." );
    return $result;
}
function check_user_blocked($email, $activity)
{
    $meta_query = array(
        array(
            'key' => 'activity',
            'value' => $activity,
        ),
        'relation' =>'AND',
        array(
            'key' => 'customer-email',     // customer-email
            'value' => $email,
        ),
    );   
    $args = array(  
        'post_type' => 'wpcf7_customData',
        'numberposts'   => -1,
        'order'         => 'DESC',
        'orderby' => 'post_date',
        'meta_query' =>  $meta_query
    );
    $is_user_block = 0;
    $posts = new WP_Query( $args );
    foreach($posts->posts as $user)
    {
        $is_user_block = get_post_meta($user->ID, 'is_user_block', true);
        break;
    }
    return $is_user_block;
}

add_filter( 'wpcf7_validate_select*', 'check_time_selected', 20, 2 );
function check_time_selected($result, $tag)
{
    if ( $tag->name == 'time') 
    {
        $time  = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : null;
        if($time == '')
        {
            $result->invalidate( $tag, "Please Select Time" );    
        }
    }
    return $result;
}

// add the action    wpcf7_mail_sent  wpcf7_submit 
add_action( 'wpcf7_mail_sent', 'action_wpcf7_after_form_submit', 10, 1 ); 
// define the wpcf7_after_flamingo callback 
function action_wpcf7_after_form_submit($result) //(  $instance, $result) 
{   
    $submission = WPCF7_Submission::get_instance();
    $posted_data = $submission->get_posted_data();
    // Form:  event submissions
    if(isset($posted_data['customer-email']) && $posted_data['activity']) // && $posted_data['event_date']
    {
        // First check user is new or old user
        global $wpdb;
        $sql = "select * from $wpdb->posts p
                inner join $wpdb->postmeta pm
                on pm.post_id = p.ID
                where
                p.post_type = 'wpcf7_customData'
                AND (pm.meta_key = 'customer-email' and pm.meta_value = '".$posted_data['customer-email']."')";
            
        $results = $wpdb->get_results( $sql );
        
        if(!empty($results))
        {
            
            $is_exists = 0;
            foreach($results as $record)
            {
                $check_activity = get_post_meta($record->ID, 'activity', true);
                if($check_activity == $posted_data['activity'])
                {
                    $is_exists = 1;
                    break;
                }
            }
            if($is_exists == 1)
                $user_status = 'Old';
            else
                $user_status = 'New';     
        }
        else
            $user_status = 'New'; 
        
        $new = array(
            'post_title' => 'Spreadshop Registration - '.ucfirst($posted_data['activity']),
            'post_content' => 'Spreadshop Registration - '.ucfirst($posted_data['activity']),
            'post_type' => 'wpcf7_customData',
            'post_status' => 'publish'
        );
        $post_id = wp_insert_post( $new );
        $url = wp_get_referer();
        $page_id = url_to_postid( $url ); 
        $main_event = get_field('main_event_type', $page_id);
        
        add_post_meta( $post_id, 'customer-name', $posted_data['customer-name'], true );
        add_post_meta( $post_id, 'customer-email', $posted_data['customer-email'], true );
        //add_post_meta( $post_id, 'customer-number', $posted_data['customer-number'], true );
        
        add_post_meta( $post_id, 'main_event_type', $main_event, true );
        if(isset($posted_data['main_event']) && $posted_data['main_event'] == 'Walk In')
            add_post_meta( $post_id, 'activity', $posted_data['activity'][0], true ); // activity for Walkin
        else
            add_post_meta( $post_id, 'activity', $posted_data['activity'], true ); // activity is Sub event type
        add_post_meta( $post_id, 'user_status', $user_status, true );

        add_post_meta( $post_id, 'web-address', $posted_data['web-address'], true );
        add_post_meta( $post_id, 'linkedin', $posted_data['linkedin'], true );
        add_post_meta( $post_id, 'sequence-generator', $posted_data['sequence-generator'], true );
        // add_post_meta( $post_id, 'gender', $posted_data['gender'][0] , true );
        //add_post_meta( $post_id, 'your-join', $posted_data['your-join'][0] , true );
        add_post_meta( $post_id, 'agree', $posted_data['agree'], true );  
        // add_post_meta( $post_id, 'age', $posted_data['age'], true );   

        add_post_meta( $post_id, 'shirt-size', $posted_data['shirt-size'][0], true );   
        add_post_meta( $post_id, 'address', $posted_data['address'], true );   

        add_post_meta( $post_id, 'date', trim($posted_data['date'][0]), true ); 
        add_post_meta( $post_id, 'time', trim($posted_data['time'][0]), true ); 
       
        //add_post_meta( $post_id, 'activity_counter', $posted_data['activity_counter'], true );            
    }

    // Cancellation form
    if(isset($posted_data['form']) && $posted_data['form'] == 'cancellation')
    {
        // remove person from list
        $args = array(  
            'post_type' => 'flamingo_inbound',
            'post_status' => 'publish',
            'suppress_filters' => true,
            'numberposts'   => 1,
            'order'         => 'ASC',
            'meta_query' =>  array(
                                array(
                                    'key' => '_field_activity',
                                    'value' => $posted_data['sub_event_type'],  // event_name
                                ),
                                'relation' => 'AND',
                                array(
                                    'key' => '_from_email',
                                    'value' => $posted_data['email'],
                                ),
                            ),
        );
        $event_user = get_posts( $args );
        if(!empty($event_user) && isset($event_user[0]->ID))
        {
            $user_id = $event_user[0]->ID;
            global $wpdb;  
            $sql = "Delete from  $wpdb->posts where ID = ".$user_id." "; 
            $wpdb->query($sql);
            $sql = "Delete from  $wpdb->postmeta where post_id = ".$user_id." "; 
            $wpdb->query($sql);
        }

        // remove record from custom dashboard
        $args = array(  
            'post_type' => 'wpcf7_customData',
            'post_status' => 'publish',
            'suppress_filters' => true,
            'numberposts'   => 1,
            'order'         => 'DESC',
            'meta_query' =>  array(
                                array(
                                    'key' => 'activity',
                                    'value' => $posted_data['sub_event_type'],
                                ),
                                'relation' =>'AND',
                                array(
                                    'key' => 'customer-email',
                                    'value' => $posted_data['email'],
                                ),
                                'relation' =>'AND',
                                array(
                                    'key' => 'date',
                                    'value' => date('Y-m-d', strtotime($posted_data['date'])),
                                ),
                                'relation' =>'AND',
                                array(
                                    'key' => 'time',
                                    'value' => str_replace(" to ", "-",$posted_data['time']),
                                ),
                            ),
        );
        $event_user = get_posts( $args );
        if(!empty($event_user) && isset($event_user[0]->ID))
        {
            $user_id = $event_user[0]->ID;
            global $wpdb;  
            $sql = "Delete from  $wpdb->posts where ID = ".$user_id." "; 
            $wpdb->query($sql);
            $sql = "Delete from  $wpdb->postmeta where post_id = ".$user_id." "; 
            $wpdb->query($sql);
        }
        
    }
}  

// Check event slots are full or not
add_action( 'wpcf7_before_send_mail', 'wpcf7_add_text_to_mail_body', 10, 3 );
function wpcf7_add_text_to_mail_body( $contact_form, &$abort, $submission ) 
{  
    $submission = WPCF7_Submission::get_instance();
    $posted_data = $submission->get_posted_data();
    if(isset($posted_data['main_event']) && $posted_data['main_event'] == 'Walk In')
    {
        return true;
    }
    else if(isset($posted_data['activity']) && $posted_data['activity'] != '')
    {
        $url = wp_get_referer();
        $post_id = url_to_postid( $url ); 
        $main_event = get_field('date_repeat', $post_id);
        $remaining_space = 0;
        
        if(!empty($main_event) && !empty($main_event))
        { 
            
            $total_records = get_booked_slots_count($posted_data['activity'], $posted_data['date'][0], $posted_data['time'][0]);
            //echo $total_records; echo '<br/>';
            $registration_per_slot = 0;
            foreach($main_event as $key => $event_session)
            { 
                if(in_array($posted_data['activity'], $event_session))
                { 
                    $time_slots = $event_session['time_slots'];
                    foreach($time_slots as $slots)
                    { 
                        $date_array = explode("/",$slots['date']); 
                        $date = $date_array[2].'-'.$date_array[1].'-'.$date_array[0];
                       // echo $posted_data['date'][0]; exit;
                        if($date == $posted_data['date'][0])
                        { 
                            $registration_per_slot = $slots['registration_per_slot'];
                        }
                    }
                }
            }
            
            $remaining_slots =  $registration_per_slot - $total_records;
            if($remaining_slots < 0)
                $remaining_slots = 0;
            
        }
        if($remaining_slots <= 0)
        {
            $abort = true;
            $submission->set_status('validation_failed');
            $submission->set_response( $contact_form->filter_message('No slot available.') );
        }
    }
}

// Get reminaing slots
function get_booked_slots_count($activity, $date, $time)
{
    // query
    $condition_sub_event = array(
        'key' => 'activity',
        'value' => $activity,
    );
    $condition_date = array(
        'key' => 'date',
        'value' => $date,
    );
    $condition_time = array(
        'key' => 'time',
        'value' => $time,
    );
    $meta_query = array(
        $condition_sub_event,
        'relation' =>'AND',
        $condition_date,
        'relation' =>'AND',
        $condition_time
    ); 
    $args = array(  
        'post_type' => 'wpcf7_customData',
        'numberposts'   => -1,
        'meta_query' =>  $meta_query
    );
    $posts = new WP_Query( $args );
    $total_records = count($posts->posts);
    
    return $total_records;
}
// Add Cancelation button in email template
add_action("wpcf7_before_send_mail", "wpcf7_add_cancelation_button");  
function wpcf7_add_cancelation_button($cf7) 
{
    $properites = $cf7->get_properties();
    // use below part if you want to add recipient based on the submitted data
    /*
    $submission = WPCF7_Submission::get_instance();
    $data = $submission->get_posted_data();
    */
    $form_id = $cf7->id();
    $submission = WPCF7_Submission::get_instance(); 
    $posted_data = $submission->get_posted_data();
    
    $url = wp_get_referer();
    $page_id = url_to_postid( $url ); 
    $main_event = get_field('main_event_type', $page_id);
    if(isset($posted_data['customer-email']) && $posted_data['activity'] && $main_event != '') // && $posted_data['event_date'] 
    {
        $event_name = base64_encode($posted_data['activity']);
        $main_event = base64_encode($main_event);
        $customer_email = base64_encode($posted_data['customer-email']);
        $event_date = base64_encode($posted_data['date'][0]);
        $date = base64_encode($posted_data['date'][0]);
        $time = base64_encode($posted_data['time'][0]);
        $cancellation_btn = '<a href="'.get_site_url().'/cancellation?ev='.$event_name.'&e='.$customer_email.'&d='.$event_date.'&mev='.$main_event.'&date='.$date.'&time='.$time.'">
                                <img src="'.get_site_url().'/wp-content/uploads/Email-Cancel-Button-new.png" style="width:200px; height:auto; display:block;" title="Cancel Registration" alt="Cancel Registration"/>
                             </a>';
                                                
        $properites['mail_2']['body'] = str_replace("{{Cancellation_link}}", $cancellation_btn, $properites['mail_2']['body']);
    }
    //echo $properites['mail_2']['body']; exit;
    $cf7->set_properties($properites);
    return ($cf7);
    
}

/* ****************************************************** */   
/* --------------- AJAX CALLs ----------------------------- */

/* -----  Jersey Form  ----- */

/* -----  Trick Shot Form  ----- */
// Drop Down date  Football verse
add_action( 'wp_ajax_nopriv_football_verse_form_dropdown_date', 'football_verse_form_dropdown_date' );  // trickshot_form_dropdown_date
add_action( 'wp_ajax_football_verse_form_dropdown_date', 'football_verse_form_dropdown_date' );
function football_verse_form_dropdown_date()
{
    $url = wp_get_referer();
    $page_id = url_to_postid( $url ); 
    $main_event = get_field('date_repeat', $page_id);
    //echo '<pre>'; print_r($main_event ); exit;
    $all_events_slots = array();
    $time_slots_array = array();
    foreach($main_event as $key => $event_session)
    {
        $session_event = $event_session['session_url'];
        $time_slots = $event_session['time_slots'];
        $time_slots_dates = array();
        $data = '<option value=""> Select Date</option>';
        foreach($time_slots as $slots)
        { 
            //$time_slots_dates[] = $slots['date'];
            $remaining_slots = 0;
            $date_array = explode("/",$slots['date']); 
            $date = $date_array[2].'-'.$date_array[1].'-'.$date_array[0];
            $registration_per_slot = $slots['registration_per_slot'];
            
            $time_slots_array = explode(",",$slots['time']);
            foreach($time_slots_array as $time)
            {
                $total_records = get_booked_slots_count($session_event, $date, $time);
                $remaining_slots = $remaining_slots + ($registration_per_slot - $total_records);
            }
            if($remaining_slots > 0)  
                $data .= '<option value="'.date('Y-m-d', strtotime($date)).'">'.date('j F, Y', strtotime($date)).'</option>';
            
        }
        $all_events_slots[$key]['activity'] = $session_event;
        $all_events_slots[$key]['date_slots'] = $data;
    }
    
    echo json_encode(array('status' =>  'success', 'data' => $all_events_slots));
    wp_die();        
}
// Drop down Time
add_action( 'wp_ajax_nopriv_football_verse_form_dropdown_time', 'football_verse_form_dropdown_time' ); // trickshot_form_dropdown_time
add_action( 'wp_ajax_football_verse_form_dropdown_time', 'football_verse_form_dropdown_time' );
function football_verse_form_dropdown_time()
{
    $url = wp_get_referer();
    $page_id = url_to_postid( $url ); 
    $main_event = get_field('date_repeat', $page_id);
    
    // Get weekday from date
    $current_activity = $_POST['activity'];
    //  ....
    //echo '<pre>'; print_r($main_event ); exit;
    $start_time = '';
    $end_time = '';
    $time_difference = '';
    $time_slots_array = array();
    $registration_per_slot = 0;
    foreach($main_event as $key => $event_session)
    {
        if(in_array($current_activity, $event_session))
        {
            $session_event = $event_session['session_url'];
            $time_slots = $event_session['time_slots'];
            foreach($time_slots as $slots)
            { 
                $date_array = explode("/",$slots['date']); 
                $date = $date_array[2].'-'.$date_array[1].'-'.$date_array[0];
                if($date == $_POST['date'])
                { 
                    $registration_per_slot = $slots['registration_per_slot'];
                    $time_slots_array = explode(",",$slots['time']);
                    
                }
            }
         }
    }
    //....
    $data = '<option value="">Select Time</option>';
    foreach($time_slots_array as $time)
    {
        $total_records = get_booked_slots_count($session_event, $_POST['date'], $time);
        $remaining_slots =  $registration_per_slot - $total_records;
        if($remaining_slots > 0)
            $data .= '<option value="'.trim($time).'">'.trim($time).'</option>';
    }
   
    $events_slots_time['activity'] = $current_activity;
    $events_slots_time['data'] = $data;
    echo json_encode(array('status' =>  'success', 'data' => $events_slots_time));
    wp_die();        
}
// Trick Shot get available slots
add_action( 'wp_ajax_nopriv_football_verse_get_slots', 'football_verse_get_slots' );  // trickshot_get_slots
add_action( 'wp_ajax_football_verse_get_slots', 'football_verse_get_slots' );
function football_verse_get_slots()
{
    $url = wp_get_referer();
    $page_id = url_to_postid( $url ); 
    $main_event = get_field('date_repeat', $page_id);
    //echo '<pre>'; print_r($main_event[0]['session_url']); exit;
    $current_activity = $_POST['activity'];
    // query
    $condition_sub_event = array(
        'key' => 'activity',
        'value' => $current_activity,
    );
    $condition_date = array(
        'key' => 'date',
        'value' => $_POST['date'],
    );
    $condition_time = array(
        'key' => 'time',
        'value' => $_POST['time'],
    ); 
    $meta_query = array(
        $condition_sub_event,
        'relation' =>'AND',
        $condition_date,
        'relation' =>'AND',
        $condition_time
    ); 
    $args = array(  
        'post_type' => 'wpcf7_customData',
        'numberposts'   => -1,
        'meta_query' =>  $meta_query
    );
    $posts = new WP_Query( $args );
   // echo '<pre>'; print_r($posts); exit;
   // echo $wpdb->last_query; exit;
    $total_records = count($posts->posts);
    // check available slots
    $registration_per_slot = 0;
    foreach($main_event as $key => $event_session)
    { 
        if(in_array($current_activity, $event_session))
        { 
            $session_event = $event_session['session_url'];
            $time_slots = $event_session['time_slots'];
            foreach($time_slots as $slots)
            {
                $date_array = explode("/",$slots['date']); 
                $date = $date_array[2].'-'.$date_array[1].'-'.$date_array[0];
                if($date == $_POST['date'])
                { 
                    $registration_per_slot = $slots['registration_per_slot'];
                }
            }
         }
    }
    $remaining_slots =  $registration_per_slot - $total_records;
    if($remaining_slots < 0) 
        $remaining_slots == 0;
    echo json_encode(array('status' =>  'success', 'remaining_slot_txt' => $remaining_slots.' SLOTS REMAINING'));
    wp_die(); 
}
// Trick Shot get available slots
add_action( 'wp_ajax_nopriv_walkin_form_dropdown', 'walkin_form_dropdown' );  // trickshot_get_slots
add_action( 'wp_ajax_walkin_form_dropdown', 'walkin_form_dropdown' );
function walkin_form_dropdown()
{
    $url = wp_get_referer();
    $page_id = url_to_postid( $url ); 
    //$main_event = get_field('date_repeat', $page_id);
    //$current_activity = $_POST['activity'];
    // query 
    $data = '<option value="">Select Experience</option><option value="jersey">Jersey Customisation</option><option value="late_goal_hero">Late Goal Hero</option><option value="lead_scorer">Scoring Leader</option>';
    echo json_encode(array('status' =>  'success', 'data' => $data));
    wp_die(); 
}

/* ----------------------------------------------------
            Add Script in Footer
   ---------------------------------------------------- */         
// Add js code in footer
function add_this_script_footer(){ 
    ?>
    <script>
        jQuery(document).ready(function(){
            jQuery.ajax({
                type : "POST",
                url : "/wp-admin/admin-ajax.php",
                data : {action: "jersey_form_dropdown_date"},
                beforeSend: function()
                {
                   
                },      
                success: function(response) 
                {
                    var data = JSON.parse(response);
                    if(data.status == 'success')
                    { 
                        jQuery('#jersey_form_date').html(data.data);
                        jQuery('#jersey_form_date').attr('onChange', 'get_jersey_dd_time(this.value);');
                    }
                }
            });

            // ajax for dropdown date for Trick Shot form
            jQuery.ajax({
                type : "POST",
                url : "/wp-admin/admin-ajax.php",
                data : {action: "football_verse_form_dropdown_date"},
                beforeSend: function()
                {
                   
                },      
                success: function(response) 
                {
                    var data = JSON.parse(response);
                    if(data.status == 'success')
                    { 
                        var all_events_data = data.data;
                        $.each( all_events_data, function( key, value ) {
                           // alert( value.activity ); alert( value.date_slots );
                            jQuery('#'+value.activity+'_form_date').html(value.date_slots);   
                            jQuery('#'+value.activity+'_form_date').attr('onChange', 'get_football_dd_time(this.value, "'+value.activity+'");'); 
                        });
                        
                    }
                }
            });

            // Get dropdown values for walkin form
            jQuery.ajax({
                type : "POST",
                url : "/wp-admin/admin-ajax.php",
                data : {action: "walkin_form_dropdown"},
                beforeSend: function()
                {
                   
                },      
                success: function(response) 
                {
                    var data = JSON.parse(response);
                    if(data.status == 'success')
                    { 
                        jQuery('#walkin_form_dropdown').html(data.data);
                        //jQuery('#jersey_form_date').attr('onChange', 'get_jersey_dd_time(this.value);');
                    }
                }
            });
        });

        function get_jersey_dd_time(date)
        {
            jQuery.ajax({
                type : "POST",
                url : "/wp-admin/admin-ajax.php",
                data : {action: "jersey_form_dropdown_time", date:date},
                beforeSend: function()
                {
                    //jQuery('#jersey_form_date').css('opacity', '0.4');
                },      
                success: function(response) 
                {
                    var data = JSON.parse(response);
                    if(data.status == 'success')
                    { 
                        jQuery('#jersey_form_time').html(data.data);
                    }
                    
                }
            });
        }

        function get_football_dd_time(date, activity)
        {
            jQuery.ajax({
                type : "POST",
                url : "/wp-admin/admin-ajax.php",
                data : {action: "football_verse_form_dropdown_time", date:date, activity:activity},
                beforeSend: function()
                {
                    jQuery('#remaining_slots_text_area_'+activity).html('');
                },      
                success: function(response) 
                {
                    var data = JSON.parse(response);
                    if(data.status == 'success')
                    { 
                        var value = data.data;
                        jQuery('#trickshot_form_time').html(data.data);
                        jQuery('#'+value.activity+'_form_time').html(value.data); 
                        jQuery('#'+value.activity+'_form_time').attr('onChange', 'get_football_verse_available_slots(this.value, "'+value.activity+'");');
                    }
                    
                }
            });
        }

        // get_trickshot_available_slots
        function get_football_verse_available_slots(time, activity)
        { 
            var date = jQuery('#'+activity+'_form_date').val();
            jQuery.ajax({
                type : "POST",
                url : "/wp-admin/admin-ajax.php",
                data : {action: "football_verse_get_slots", date:date, time:time, activity:activity},
                beforeSend: function()
                {
                    jQuery('#remaining_slots_text_area_'+activity).html('');
                },      
                success: function(response) 
                {
                    var data = JSON.parse(response);
                    if(data.status == 'success')
                    {  
                        jQuery('#remaining_slots_text_area_'+activity).html(data.remaining_slot_txt);
                    }
                    
                }
            });    
        }
        document.addEventListener( 'wpcf7submit', function( event ) {
            jQuery('.remaining_slots_text_area').html('');
        }, false );
    </script>    
    <?php } 
    add_action('wp_footer', 'add_this_script_footer'); 

/* ----------------------------------------------------
            Add Cron jobs 
   ---------------------------------------------------- */

    // cron job for reminder email
// add_action( 'cronjob_send_reminder_emails', 'send_reminder_emails' );
// function send_reminder_emails()
// {
//     $args = array(  
//         'post_type' => 'wpcf7_customData',
//         'suppress_filters' => true,
//         'posts_per_page' => 50,
//         'order'         => 'ASC',
//         'orderby' => 'post_date',
//         'meta_query' => array(
//                             array(
//                             'key' => 'is_reminder_sent',
//                             'compare' => 'NOT EXISTS' 
//                             ),
//                         ),
//     );  
//     $posts = new WP_Query( $args );
//     foreach($posts->posts as $rec)
//     { 
//         $is_reminder_sent = get_post_meta($rec->ID, 'is_reminder_sent', true);
//         if($is_reminder_sent != 1)
//         {
//             $event_date = get_post_meta($rec->ID, 'date', true);
//             $post_date = $rec->post_date;
            
//             $user_email = get_post_meta($rec->ID, 'customer-email', true);
//             $date = explode("/", $event_date);
//             $date_format = $date[2].'-'.$date[1].'-'.$date[0];
//             $new_date = strtotime(date('d M, Y', strtotime($date_format)));
            
//             //$post_time = strtotime($post_date) +  (60*10);  // add 1 hour 
//             // if(time() >= $post_time)
//             // New check and send email before 3 days to event date
//             if(date('Y-m-d') >= date('Y-m-d', strtotime("-3 day", $new_date)))
//             {
//                 $user_name = get_post_meta($rec->ID, 'customer-name', true);
//                 $main_event = get_post_meta($rec->ID, 'main_event_type', true);
//                 $activity = get_post_meta($rec->ID, 'activity', true);
//                 // Send Email
//                 $to = $user_email;
//                 $subject = 'Nike Kids Never Done – '.ucfirst($main_event).' Event';
//                 $body = email_template($activity);

//                 $body = str_replace("{{customer-name}}", $user_name, $body);
//                 $body = str_replace("{{event}}", ucfirst($main_event), $body);
//                 if($main_event == 'dance')
//                     $banner_image = get_site_url().'/wp-content/uploads/nnd-dance.jpg';
//                 else
//                     $banner_image = get_site_url().'/wp-content/uploads/nnd-skateboard.jpg';    
//                 $body = str_replace("{{banner_image}}", $banner_image, $body);
                
//                 // cancellation link
//                 $event_name = base64_encode($activity);
//                 $main_event = base64_encode($main_event);
//                 $customer_email = base64_encode($user_email);
//                 $event_date = base64_encode($event_date);
//                 $cancellation_btn = '<a href="'.get_site_url().'/cancellation?ev='.$event_name.'&e='.$customer_email.'&d='.$event_date.'&mev='.$main_event.'">
//                                         <img src="'.get_site_url().'/wp-content/uploads/Email-Cancel-Button-new.png" style="width:200px; height:auto; display:block;" title="Cancel Registration" alt="Cancel Registration"/>
//                                     </a>';
                                                        
//                 $body = str_replace("{{Cancellation_link}}", $cancellation_btn, $body);

//                 $headers = array('Content-Type: text/html; charset=UTF-8');
//                 $headers[] = 'From: Nike <noreply@nikebymarina.com>';
                     
//                 wp_mail( $to, $subject, $body, $headers );
//                 add_post_meta($rec->ID, 'is_reminder_sent', 1);
//             }
           
//         }
        
//     }
//     die;
// }

// Cron job function
add_action( 'cronjob_unblock_event_users', 'unblock_event_users' );
function unblock_event_users()
{
    $args = array(  
        'post_type' => 'wpcf7_customData',
        'suppress_filters' => true,
        'numberposts'   => -1,
        'posts_per_page' => -1,
        'order'         => 'DESC',
        'orderby' => 'post_date'
    );
    $posts = new WP_Query( $args );
    foreach($posts->posts as $rec)
    {
        $is_user_block = get_post_meta($rec->ID, 'is_user_block', true);
        if($is_user_block == 1)
        {
            $event_date = get_post_meta($rec->ID, 'date', true);
            $from_email = get_post_meta($rec->ID, 'customer-email', true);
            $date = explode("/", $event_date);
            $date_format = $date[2].'-'.$date[1].'-'.$date[0];
            $new_date = strtotime(date('d M, Y', strtotime($date_format)));
            // New check event date is older than 7 days
            if(date('Y-m-d') >= date('Y-m-d', strtotime("+7 day", $new_date)))
            {
                update_post_meta( $rec->ID, 'is_user_block', 0);
            }
            // echo $event_date.' -  '.$from_email.': '.date('d M, Y', strtotime("+7 day", $new_date)).'<br/>'; 
        }
        
    }
    die;
}



/* ----------------------------------------------------
            Backend Custom Dashboard Code
   ---------------------------------------------------- */
function wpcf7_entries() 
{   
    include('admin/entries_list.php');  
	//Create an instance of our package class...
    $ListTable = new Entries_list();
    if(isset($_GET['report']) && !isset($_GET['paged']))
    {
        $result = $ListTable->array_csv_download();
        //echo $result; exit;
        echo '<script type="text/javascript"> 
                setTimeout(function(){ 
                    jQuery("#download_link").hide().html("<a id=\'csv_custom_link\' href=\''.$result.'\' > Download</a>");
                    document.getElementById("csv_custom_link").click();
                },200);
               
              </script>';
       
    }
    // Block / Unblock user
    if(isset($_GET['block']) && $_GET['block'] != '' && isset($_GET['email']) && isset($_GET['event']))
    {
        $meta_query = array(
                    array(
                        'key' => 'activity',
                        'value' => $_GET['event'],
                    ),
                    'relation' =>'AND',
                    array(
                        'key' => 'customer-email',     // customer-email
                        'value' => $_GET['email'],
                    ),
                );   
        $args = array(  
            'post_type' => 'wpcf7_customData',
            'suppress_filters' => true,
            'numberposts'   => -1,
            'order'         => 'DESC',
            'orderby' => 'post_date',
            'meta_query' =>  $meta_query
        );
        $posts = new WP_Query( $args );
        //echo '<pre>'; print_r($posts->posts); exit;
        foreach($posts->posts as $user)
        {
            update_post_meta( $user->ID, 'is_user_block', $_GET['block'] );
        }
    }
    //Fetch, prepare, sort, and filter our data...
    $ListTable->prepare_items();
    @session_start();

    // get list of events
    global $wpdb;
     // group by pm.meta_value";
    $sql2 = "select pm.meta_value meta_value, pm.post_id  from $wpdb->posts p
           inner join $wpdb->postmeta pm
           on pm.post_id = p.ID
           where
           p.post_type = 'wpcf7_customData'
           AND pm.meta_key = 'activity'
           AND pm.meta_value != ''
           order by meta_value asc
           ";       
    $new_events = $wpdb->get_results( $sql2 );
   // this above query might not needed... but check...
   //--------------------
    // get event date
    $sql2 = "select pm.meta_value date from $wpdb->posts p
           inner join $wpdb->postmeta pm
           on pm.post_id = p.ID
           where
           p.post_type = 'wpcf7_customData'
           AND pm.meta_key = 'date'   
           AND pm.meta_value != ''
           group by pm.meta_value ";    
    $event_dates = $wpdb->get_results( $sql2 );
   // echo '<pre>'; print_r($new_events); exit;
    $sql = "select p.post_date post_date from $wpdb->posts p
            where
            p.post_type = 'wpcf7_customData'
            group by p.post_date";
    $date_result = $wpdb->get_results( $sql );
    
    
    $months = array();
    sort($date_result);
    foreach($date_result as $date)
    {
        // get month
        $date_month = date('F', strtotime($date->post_date));
        if(!in_array($date_month, $months))
        {
            $months[] = $date_month;
        }
    }
    sort($event_dates);
    $eventDates = array();
    foreach($event_dates as $date)
    {
        // get month
        if(!in_array($date->date, $eventDates) && $date != null)
        {
            $eventDates[] = $date->date;
        }
    }
    // Get main Event types
    $sql2 = "select pm.meta_value meta_value, pm.post_id  from $wpdb->posts p
           inner join $wpdb->postmeta pm
           on pm.post_id = p.ID
           where
           p.post_type = 'wpcf7_customData'
           AND pm.meta_key = 'main_event_type'
           AND pm.meta_value != ''
           group by pm.meta_value
           order by meta_value asc
           ";       
    $main_events = $wpdb->get_results( $sql2 );

    // Get sub Event types
    $sql2 = "select pm.meta_value meta_value, pm.post_id  from $wpdb->posts p
           inner join $wpdb->postmeta pm
           on pm.post_id = p.ID
           where
           p.post_type = 'wpcf7_customData'
           AND pm.meta_key = 'activity'
           AND pm.meta_value != ''
           group by pm.meta_value
           order by meta_value asc
           ";       
    $sub_events = $wpdb->get_results( $sql2 );
    //============================
    // This below code might not needed now,, but need to check................
    $event_type_data = array();
    $event_titles = array();
    $e_counter = 0;
    foreach($new_events as $event)
    { 
        // get event date
        $ev_date = get_post_meta($event->post_id, 'event_date', true); 
        if($ev_date != '')
        {
            $ev_date_array = explode("/", $ev_date);
            if(strtotime($ev_date_array[2].'-'.$ev_date_array[1].'-'.$ev_date_array[0]) > strtotime('2022-07-06'))
                $e_date = date('_F-d', strtotime($ev_date_array[2].'-'.$ev_date_array[1].'-'.$ev_date_array[0]));
            else
                $e_date = '';    
        }
        else
            $e_date = ''; 

        $event_type =  ucfirst($event->meta_value).$e_date;  
        if(!in_array($event_type, $event_titles)) 
        {
            $event_titles[] = $event_type; 
            $event_type_data[$e_counter]['title'] = $event_type;
            $event_type_data[$e_counter]['title_with_date'] = $event->meta_value.'_'.$ev_date;
            $event_type_data[$e_counter]['meta_value'] = $event->meta_value;
            $e_counter++;
        }  
    }
    ?>
	<div class="wrap">
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Contact Form Entries</h2>
        <?php if(isset($_SESSION['success_msg']) && $_SESSION['success_msg'] != ''){ ?>
               <div id="message" class="notice notice-success is-dismissible"><?php echo $_SESSION['success_msg']; ?> </div>
        <?php unset($_SESSION['success_msg']); } ?>
        <style>
            input[type="date"], input[type="text"] {line-height: 1.6;}                
        </style>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <lable> Start Date</label>
            <input type="date" name="search_start_date" value="<?php echo (isset($_GET['search_start_date']) ? $_GET['search_start_date'] : ''); ?>"/>
            <lable> End Date</label>
            <input type="date" name="search_end_date" value="<?php echo (isset($_GET['search_end_date']) ? $_GET['search_end_date'] : ''); ?>"/>
            <select name="search_by_date" style="display:none;">
                <option value="">All dates</option>
                <?php foreach($months as $mon){ ?>
                        <option value="<?php echo $mon;?>" <?php if(isset($_GET['search_by_date']) && $_GET['search_by_date'] == $mon) echo 'selected'; ?>><?php echo $mon; ?></option>
                <?php } ?>        
            </select>
            <select name="search_by_event" >
                <option value="">Main Event Type</option>
                <?php foreach($main_events as $event){  ?>
                        <option value="<?php echo $event->meta_value;?>" <?php if(isset($_GET['search_by_event']) && $_GET['search_by_event'] == $event->meta_value) echo 'selected';?>><?php echo $event->meta_value; ?></option>
                <?php } ?>        
            </select> 
            <select name="search_by_sub_event" >
                <option value="">Sub Event Type</option>
                <?php foreach($sub_events as $event){  ?>
                        <option value="<?php echo $event->meta_value;?>" <?php if(isset($_GET['search_by_sub_event']) && $_GET['search_by_sub_event'] == $event->meta_value) echo 'selected';?>><?php echo $event->meta_value; ?></option>
                <?php } ?>          
            </select>
            <select name="search_by_event_date" >
                <option value="">Event Date</option>
                <?php foreach($eventDates as $date){ ?>
                        <option value="<?php echo $date;?>" <?php if(isset($_GET['search_by_event_date']) && $_GET['search_by_event_date'] == $date) echo 'selected';?>><?php echo ucfirst($date); ?></option>
                <?php } ?>        
            </select>
            <input type="text" name="search_text" placeholder="Search" value="<?php echo (isset($_GET['search_text']) ? $_GET['search_text'] : ''); ?>"/>
            <input type="submit" id="doaction" class="button action" value="Filter"> 
            <?php   $search = ''; 
                    if(isset($_GET['search_by_event']) && $_GET['search_by_event'] != '')
                        $search = '&search_by_event='.$_GET['search_by_event'];
                    if(isset($_GET['search_start_date']) && isset($_GET['search_end_date']))   
                        $search .= '&search_start_date='.$_GET['search_start_date'].'&search_end_date='.$_GET['search_end_date'];
                    if(isset($_GET['search_text']) && isset($_GET['search_text']))  
                        $search .= '&search_text='.$_GET['search_text'];
                    if(isset($_GET['search_by_event_date']) && isset($_GET['search_by_event_date']))  
                        $search .= '&search_by_event_date='.$_GET['search_by_event_date'];  
                    if(isset($_GET['search_by_sub_event']) && isset($_GET['search_by_sub_event']))  
                        $search .= '&search_by_sub_event='.$_GET['search_by_sub_event'];             
                   
             ?>       
            <input type="button" onClick="location.href='<?php echo admin_url( '/admin.php?page=wpcf7_entries&report=1'.$search);?>'" class="button action" value="Export"> 
        </form>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $ListTable->display() ?>
        </form>
        <script>
            jQuery("#checkAll").change(function()
            {
                if (jQuery('#checkAll').is(":checked")) { 
                    jQuery('input:checkbox').prop('checked',true);
                } else {  
                    jQuery('input:checkbox').prop('checked', false);
                }       
            });
            
            function update_user_played(val, post_id)
            {
                jQuery.ajax({
                    type : "POST",
                    url : "/wp-admin/admin-ajax.php",
                    data : {action: "update_user_played_game",
                            val: val, 
                            post_id: post_id
                        },
                    beforeSend: function()
                    {
                        jQuery('#cd_user_played_'+post_id).parent().parent().css('opacity', '0.4');
                    },      
                    success: function(response) 
                    {
                        var data = JSON.parse(response);
                        jQuery('#cd_user_played_'+post_id).parent().parent().css('opacity', '1');
                        if(data.status != 'success')
                        {
                            alert('Error! Something wrong, please refresh page and try again')
                        }
                        
                    }
                });
            }
        </script>
    </div>
    <div id="download_link"></div>
    <?php 
}
add_action( 'wp_ajax_nopriv_update_user_played_game', 'update_user_played_game' );
add_action( 'wp_ajax_update_user_played_game', 'update_user_played_game' );
function update_user_played_game()
{
    if(isset($_POST['val']) && $_POST['post_id'] != '')
	{
        update_post_meta($_POST['post_id'], 'is_user_played', $_POST['val']);
        echo json_encode(array('status' =>  'success'));
	}
    else
    {
        echo json_encode(array('status' =>  'error'));
    }
    wp_die();
}

/* ------------    WINNER DASHBOARD ------------------ */
function winner_page() 
{   
    @session_start();
    if(isset($_POST['accept_winner']) && $_POST['accept_winner'] == 1 && $_POST['winner'] != '')
    {
        add_user_meta(1,'winner_users', serialize($_POST['winner']));
        // Send email to winners
        send_email_to_winners($_POST['winner']);
    }
    $selected_winner_users = unserialize(get_user_meta(1,'winner_users', true));
    $winner_users = array();
    if(empty($selected_winner_users))
    {
        global $wpdb;
        // group by pm.meta_value";
        $sql = "select group_concat(post_id) post_ids, meta_value
                From wp_posts p
                inner join wp_postmeta pm
                on pm.post_id = p.ID
                where 
                post_type = 'wpcf7_customData' 
                AND 
                meta_key = 'customer-email' 
                group by meta_value";       
        $all_users = $wpdb->get_results( $sql );
        //echo '<pre>'; print_r($all_users); exit;
        $all_users_data = array();
        $games = array('mixed_doubles','trick_shot','lead_scorer','late_goal_hero');
        $users_in_winner_draw = array();
        foreach($all_users as $user)
        {
            //if($user->meta_value == 'muhammad@thisiscrowd.com')
            {
                // all post ids of a user
                $postIDs = explode(",", $user->post_ids);
            
                $games_played_count = 0;
                foreach($postIDs as $post_id)
                {
                    $activity = get_post_meta($post_id, 'activity', true);  
                    if(in_array($activity, $games))
                    {
                        $is_user_played = get_post_meta($post_id, 'is_user_played', true);
                        if($is_user_played == 1)
                            $games_played_count++;
                    }  
                }
                if($games_played_count >= count($games))
                {
                    $users_in_winner_draw[$post_id] = $user->meta_value;
                }
                //$postIDs
                
            }
        }
        if(!empty($users_in_winner_draw))
        {
            $winners_array_node =  array_rand($users_in_winner_draw,3);
            foreach($winners_array_node as $key => $val)
            { 
                $winner_users[$val] = $users_in_winner_draw[$val];
            }
        }
    }
    else
    {
        foreach($selected_winner_users as $id)
        {
            $email = get_post_meta($id, 'customer-email', true);
            $winner_users[$id] = $email;
        }
    }
    
    ?>
	<div class="wrap">
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Winner Dashboard</h2>
        <?php if(isset($_SESSION['success_msg']) && $_SESSION['success_msg'] != ''){ ?>
               <div id="message" class="notice notice-success is-dismissible"><?php echo $_SESSION['success_msg']; ?> </div>
        <?php unset($_SESSION['success_msg']); } ?>
        <style>
            input[type="date"], input[type="text"] {line-height: 1.6;}                
        </style>
        <style>
            table {
            border-collapse: collapse;
            width: 100%;
            }

            th, td {
            text-align: left;
            padding: 8px;
            }

            tr:nth-child(even){background-color: #f2f2f2}

            th {
                background-color: #868d8a;
                 color: white;
            }
            td {
                background: white;
                 color: black;
            }
            </style>
        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="post">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <br/><br/>
            <table>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>    
                <?php 
                $counter = 1;
                foreach($winner_users as $key => $user)
                { 
                        $name = get_post_meta($key, 'customer-name', true);
                ?>
                    <tr>
                        <td><?php echo $counter; ?></td>
                        <td><?php echo $name; ?></td>
                        <td><?php echo $user; ?></td>
                    </tr>
                    <input type="hidden" name="winner[]" value="<?php echo $key; ?>" />
                <?php $counter++; } ?>    
            </table><br/><br/>
            <?php if(empty($selected_winner_users) && !empty($winner_users)) { ?>
                <input type="button" id="doaction" onclick="location.href='?page=winner_page&refresh_winner'" class="button action" value="Change Winners">
                <input type="hidden" name="accept_winner" value="1"/>
                <input type="submit" id="doaction" class="button action" value="Accept Winner">
            <?php } ?>    
            <!-- Now we can render the completed list table -->
        </form>
    </div>
    <?php 
}
function send_email_to_winners($winners)
{
    foreach($winners as $rec)
    {
        $name = get_post_meta($rec, 'customer-name', true);
        $email = get_post_meta($rec, 'customer-email', true);
        // Send Email
        $to = $email;
        $subject = 'NIKE FC Winner';
        $body = email_template('winner');

        $body = str_replace("{{customer-name}}", $name, $body);
        //$body = str_replace("{{event}}", ucfirst($main_event), $body);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $headers[] = 'From: Nike <noreply@nikefootballversedxb.com>';
            
        wp_mail( $to, $subject, $body, $headers );
    }
}
/* ****************************************************
            END Backend Custom Dashboard Code
   **************************************************** */

/* ----------------------------------------------------
            Add Extra Scripts 
   ---------------------------------------------------- */
/*  --------     Script to update Users status in custom dashboard ------------- */
// Check users status
if(isset($_GET['check_status']) && $_GET['check_status'] == 1)
{
    global $wpdb;
    $sql = "select p.* from $wpdb->posts p
            where
            p.post_type = 'wpcf7_customData'
            order by post_date ASC
            ";
    $results = $wpdb->get_results( $sql );
    if(!empty($results))
    {
        
        $is_exists = 0;
        foreach($results as $record)
        {
            $check_activity = get_post_meta($record->ID, 'activity', true);
            $user_email = get_post_meta($record->ID, 'customer-email', true);
            echo '<br/>-----------------------------<br/>';
            echo $record->ID .'<br/>';
            echo 'status update:'.update_post_meta( $record->ID, 'user_status', 'New' );
            echo '<br/>'.$user_email .'<br/>';
            // Now check same user exists in same event in previous date
            $sql = "select p.* from $wpdb->posts p
                    inner join $wpdb->postmeta pm
                    on pm.post_id = p.ID
                    where
                    p.post_type = 'wpcf7_customData'
                    AND (pm.meta_key = 'customer-email' and pm.meta_value = '".$user_email."')
                    AND p.post_date <= '".$record->post_date."'
                    AND p.ID != $record->ID
                    ";
              
            $user_results = $wpdb->get_results( $sql ); 
            if(!empty($user_results))
            {  
                foreach($user_results as $u_res)
                {
                    $n_user_activity = get_post_meta($u_res->ID, 'activity', true);
                    if($n_user_activity == $check_activity)
                    {
                        echo '<br/> status 2: '.update_post_meta( $record->ID, 'user_status', 'Old'); echo '<br/>';
                        break;
                    }
                } 
                
            }       
            
        }
        
    }
    exit;
}
/*  --------     -------------- Script End ------------------      ------------- */
