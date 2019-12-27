<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	
	
	
	<div class="card">
		<div class="card-world-wrap">
			<?php if( has_post_thumbnail() ): ?>
			<div class="thumbnail-wrapper">
				<div class="thumbnail"><?php the_post_thumbnail('large'); ?></div>
			</div>
			<header class="entry-header">
				<h3><?php the_title(); ?></h3>
			</header>
			<div class="excerpt">
				<?php the_excerpt(); ?>
			</div>
		
			<?php else: ?>
				<header class="entry-header">
					<h3><?php the_title(); ?></h3>
				</header>
				<div class="excerpt">
					<?php the_excerpt(); ?>
				</div>
			
			<?php endif; ?>
		</div>
		<div class="button-wrapper">
			<a href="<?php echo esc_url( get_permalink() ); ?>">Read More</a>
		</div>
	</div>

</article>

