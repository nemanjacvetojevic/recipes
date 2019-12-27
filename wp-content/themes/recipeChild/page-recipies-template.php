<?php

/*
    Template Name: Recipe post type
*/


	
get_header(); ?>
	<div class="hero-banner">
		<div class="content-wrapper">
		<h1>Best prepared with love</h1>
		<a href="<?php echo get_page_link( get_page_by_title( 'Submit recipe' )->ID ); ?>">Submit recipe</a>
		</div>
		<img class="hero-banner-image" src="<?php echo get_stylesheet_directory_uri(); ?>/img/heroBanner.png"  alt="Christmass Recepies" />
		<img class="hero-banner-image-mobile" src="<?php echo get_stylesheet_directory_uri(); ?>/img/mobileBanner.png"  alt="Christmass Recepies" />
	</div>
	<div class="recipies-container">
		<h2>Newest recipies</h2>
		<div class="grid-wrapper">

			<?php 
			$paged = (get_query_var('page')) ? get_query_var('page') : 1;
			$args = array('post_type' => 'recipe', 'posts_per_page' => 9 , 'paged' => $paged);
			$loop = new WP_Query( $args );
			
			if( $loop->have_posts() ):
				
				while( $loop->have_posts() ): $loop->the_post(); ?>
					
					<?php get_template_part('content', 'archive'); ?>
				
				<?php endwhile; ?>
				
		<?php posts_nav_link(); ?>
		
			
		</div>
		<div class="e_next"><?php previous_posts_link('PREVIOUS PAGE') ?></div>
			<div class="e_prev"><?php next_posts_link('NEXT PAGE', $loop->max_num_pages) ?></div>
  		
			<?php endif; ?>
	</div>
<?php get_footer(); ?>