<?php
/**
 * Template Name: Innerpage
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
$context['inner']   = true;
$context['post'] = $post;

//Set Space Values For Different Activities
$space_value = get_field('event_counter_value') != '' ? get_field('event_counter_value') : 0;  
$page_id  = $post->ID();
$pagename = $post->post_name;

$post_sessions = get_field('date_repeat');

$context['post_sessions'] = $post_sessions;

Timber::render('pages/innerpage.twig', $context);
 