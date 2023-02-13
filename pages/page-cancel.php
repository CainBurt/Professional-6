<?php
/**
 * Template Name: Cancellation Page
 *
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/views/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package     WordPress
 * @subpackage  Timber
 * @since       Timber 0.1
 */

$context         = Timber::get_context();
$post            = new TimberPost(); 
$context['cancel_page']   = true;
$context['post'] = $post;

$post_form = get_field('form_code');
if(isset($_GET['ev']) && $_GET['ev'] != '' && isset($_GET['e']) && $_GET['e'] != '' && isset($_GET['d']) && $_GET['d'] != '')
{
    $event = base64_decode($_GET['ev']);
    $email = base64_decode($_GET['e']);
    $event_date = base64_decode($_GET['d']);
    $args = array(  
        'post_type' => 'flamingo_inbound',
        'post_status' => 'publish',
        'suppress_filters' => true,
        'numberposts'   => -1,
        'order'         => 'ASC',
        'meta_query' =>  array(
                            array(
                                'key' => '_field_activity',
                                'value' => $event,
                            ),
                            'relation' =>'AND',
                            array(
                                'key' => '_from_email',
                                'value' => $email,
                            ),
                        ),
    );
    $event_users = get_posts( $args );
    //echo '<pre>'; print_r($event_users); exit;
    if(!empty($event_users))
    { 
        // check email exists in event list
        $sub_event_name = base64_decode($_GET['ev']);
        if($sub_event_name != 'jersey')
            $event_full_title = ucfirst(base64_decode($_GET['mev'])).' ('.str_replace("_"," ",$sub_event_name).')'; //ucfirst(base64_decode($_GET['mev'])).'-'.base64_decode($_GET['ev']);
        else
        $event_full_title = ucfirst(base64_decode($_GET['mev']));
        $post_form = str_replace( 'Event Name', $event_full_title, $post_form );
        $post_form = str_replace( 'Email', base64_decode($_GET['e']), $post_form );
        $post_form = str_replace( 'Event Date', base64_decode($_GET['d']), $post_form );
        $post_form = str_replace( 'Sub Event Type', base64_decode($_GET['ev']), $post_form );

        $date = base64_decode($_GET['date']);
        if($date != '');
            $post_form = str_replace( 'Slot Date', date("j F, Y" , strtotime($date)), $post_form );
        $time = base64_decode($_GET['time']);
        if($time != '')    
            $post_form = str_replace( 'Slot Time', str_replace("-", " to ", $time), $post_form );

    }
    else
    {
        $context['error_message'] = "You are not registered for this event";
    }
}
else
    $context['error_message'] = 'Invalid url';
$context['post_form'] = $post_form; 

Timber::render('pages/cancel.twig', $context);
 