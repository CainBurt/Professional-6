<?php
$context             = Timber::get_context();
$post                = Timber::query_post();
$context['post']     = $post;
$context['single_news']   = true;
$context['category'] = \Timber\Timber::get_terms([
    'taxonomy'   => 'category',
    'hide_empty' => true,
]);

$context['single_news'] = true;

if (count($post->terms) > 0) {
    $related_args = [
        'post_type'      => 'post',
        'posts_per_page' => 3,
        'post__not_in'   => [$post->ID],
        'orderby'        => 'title',
        'order'          => 'ASC'
    ];

    foreach ($post->terms as $key => $term) {
        $related_args['tax_query'][] = [
            [
                'taxonomy' => $term->taxonomy,
                'field'    => 'slug',
                'terms'    => $term->slug,
                'operator' => 'IN'
            ]
        ];
    }

    $context['related'] = new \Timber\PostQuery($related_args);
}

Timber::render('posts/single.twig', $context);