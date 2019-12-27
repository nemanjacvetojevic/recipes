<?php get_header(); ?>

<div class="row">
		
		<?php 
		
		if( have_posts() ):
			
			while( have_posts() ): the_post(); ?>
				
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php if( has_post_thumbnail() ): ?>
				<div class="thumbnail-wrapper-single">
					<div class="thumbnail-single"><?php the_post_thumbnail('full'); ?></div>
				</div>
				<div class="single-container">
					<header class="entry-header">
					<h3><?php the_title(); ?></h3>
					</header>
					<div class="single-content">
						<?php the_content(); ?>
					</div>	
				</div>	
				<?php else: ?>
				<div class="single-container">
					<header class="entry-header">
						<h3><?php the_title(); ?></h3>
					</header>
					<div class="single-content">
						<?php the_content(); ?>	
					</div>
					<?php endif; ?>
				</div>
				</article>

			<?php endwhile;	
		endif;?>
		
</div>

<?php get_footer(); ?>