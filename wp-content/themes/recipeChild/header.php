<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
        <meta charset="<?php bloginfo('charset'); ?>">
		<title><?php bloginfo('name'); ?><?php wp_title('|'); ?></title>
        <meta name="description" content="<?php bloginfo('description'); ?>">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <link href="https://fonts.googleapis.com/css?family=Mountains+of+Christmas:400,700|Roboto:300,400,500&display=swap" rel="stylesheet">
		<?php wp_head(); ?>
	</head>
	
	<?php 
		
		if( is_front_page() ):
			$recipe_classes = array( 'recipe-class', 'my-class' );
		else:
			$recipe_classes = array( 'no-recipe-class' );
		endif;
		
	?>
	
	<body <?php body_class( $recipe_classes ); ?>>
        <div class="world-wrap">
        
        <div class="navbar">
            <div class="logo">
                <?php recipe_site_logo(); ?>
            </div>
            <nav>
            <div class="recipe-navigation">
            <?php 
                        wp_nav_menu(array(
                            'theme_location' => 'primary',
                            'container' => false,
                            'menu_class' => 'nav navbar-nav navbar-right',
                            )
                        );
                    ?>
            </div>
            </nav>
        </div>
        
	    