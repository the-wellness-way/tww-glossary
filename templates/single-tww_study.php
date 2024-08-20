<?php
get_header();

if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
<div class="single-tww-study--page-header">
    <div class="single-tww-study--page-title single-study-container"><h1><?php the_title(); ?></h1></div>
</div>
    <div id="content" class="site-content">
        <div id="primary" class="content-area">
            <main id="main" class="site-main single-tww-study--article single-study-container" role="main">
                <div class="single-tww_study--breadcrumbs">
                    <span><a href="/research"> Research </a> </span> <i class="fa fa-caret-right"></i> <span><?php the_title(); ?></span>
                </div>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry-content">
                        <?php 
                            $current_study_id = get_the_ID();

                            // WP_Query to fetch tww_study_single posts associated with the current tww_study
                            $query = new WP_Query([
                                'post_type' => 'tww_study_single',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC',
                                'meta_query' => [
                                    [
                                        'key' => '_tww_study_single_category',
                                        'value' => 'post:tww_study:' . $current_study_id,
                                        'compare' => '='
                                    ]
                                ]
                            ]);
                            
                            

                            if ($query->have_posts()) :
                                while ($query->have_posts()) : $query->the_post();
                                    $title = get_the_title();
                                    $permalink = get_the_permalink();
                                    $cat = carbon_get_the_post_meta('tww_study_single_category');
                                    $description = carbon_get_the_post_meta('tww_study_single_description');
                                    $links = carbon_get_the_post_meta('tww_study_single_links');
                                    ?>

                                    <div class="study">
                                        <h4>
                                        <span class="title-text"><?php echo esc_html($title); ?></span>
                                        </h4>
                                        <div><?php echo wp_kses_post($description); ?></div>
                                        <br />
                                        <?php if ($links): ?>
                                            <ul style="margin: 0; padding: 0;">
                                                <?php foreach ($links as $link): ?>
                                                    <li style="list-style: none;"><a target="_blank" href="<?php echo esc_url($link['link_url']); ?>"><?php echo esc_html($link['link_text']); ?> <i class="fa-solid fa-up-right-from-square"></i></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    endwhile;
                                    wp_reset_postdata();
                                    endif;
                            ?>

                        <?php the_content(); ?>
                </article>
            </main>
        </div>
    </div>
<?php endwhile; endif;

$user_id = get_current_user_id();

if($user_id) {
    $user = new \MeprUser($user_id);
    $active_memberships = $user->active_product_subscriptions('ids');
}

if((!$active_memberships || !count($active_memberships)) && !current_user_can('manage_options')) {
    ?>
    <div class="gate-overlay"></div>

    <div style="padding: 40px;" class="gate--cta <?php echo current_user_can('manage_options') ? 'gate-admin' : ''; ?>"> 
        <div class="gate--cta--content">
            <h2>Unlock the full database</h2>
            <p>Access all of our research and studies by becoming a member.</p>
            <a href="/join" style="margin: 5px auto; display: flex !important; align-items: center !important;" class="button-primary primary-button">Get Access</a> 
            <?php
                if(!is_user_logged_in()) {
                    echo '<p style="text-align: center;">or <a href="/login" class="tww-glossary-login">Login</a></p>';
                }
            ?>
        </div>
    </div>
<?php
}

get_footer();
?>
