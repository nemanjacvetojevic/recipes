<?php
$wp_error = null;
$status = "success";
$message = [];
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if (
    isset($_POST['cpt_nonce_field']) &&
    wp_verify_nonce( $_POST['cpt_nonce_field'], 'cpt_nonce_action' )
) {

    // create post object with the form values
    $insertPostArgs = array(
        'post_title'    => $_POST['cptTitle'],
        'post_content'  => $_POST['cptContent'],
        'post_status'   => 'pending',
        'post_type' => 'recipe',
    );
    // insert the post into the database

    $recipeId = wp_insert_post( $insertPostArgs, $wp_error);

    if($recipeId && isset($_FILES["image"]["name"])) {
        $upload = wp_upload_bits($_FILES["image"]["name"], null, file_get_contents($_FILES["image"]["tmp_name"]));

        $filename = $upload['file'];
        $wp_filetype = wp_check_filetype($filename, null );

        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachmentId = wp_insert_attachment( $attachment, $filename, $recipeId );
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata( $attachmentId, $filename );
        wp_update_attachment_metadata( $attachmentId, $attach_data );
        set_post_thumbnail( $recipeId, $attachmentId );
    }

    if($recipeId) {
        $message = [
            'type' => 'success',
            'content' => 'Your recipe has been submitted for review'
        ];
    } else {
        $status = "error";
        $message = [
            'type' => 'error',
            'content' => $wp_error
        ];
    }
}

if($isAjax) {
    wp_send_json([
        'status' => $status,
        'message' => $message
    ]);
}
?>


<?php

/*
    Template Name: Add Recepie
*/

 get_header(); ?>


	<div class="hero-banner">
		<div class="content-wrapper">
		<h1>Show us your own</h1>
		</div>
		<img class="hero-banner-image" src="<?php echo get_stylesheet_directory_uri(); ?>/img/heroBanner.png"  alt="Christmass Recepies" />
		<img class="hero-banner-image-mobile" src="<?php echo get_stylesheet_directory_uri(); ?>/img/mobileBanner.png"  alt="Christmass Recepies" />
	</div>
	<div class="recipies-container">

        <h3>
            Help us make this holiday magical, leave your sweetest legacy and be proud of your work
        </h3>

        <div id="recipe-add-messages" class="messages">
            <div class="message <?= $message['type']; ?>"><?= $message['content']; ?></div>
        </div>

        <form id="recipe-add-form" method="post" enctype="multipart/form-data">
        <p><label for="cptTitle"><?php _e('Recipe title:', 'mytextdomain') ?></label>

        <input required="required" type="text" name="cptTitle" id="cptTitle" /></p>


        <p> <label for="cptContent"><?php _e('Recipe Description:', 'mytextdomain') ?></label>

        <textarea required="required" name="cptContent" id="cptContent" rows="4″ cols="20″></textarea> </p>
        <div class="form-group">
                <label><?php _e('Select Image:', 'Your text domain here');?></label>
                <input type="file" name="image">
        </div>
        <button id="recipe-add-submit" type="submit"><?php _e('Submit recipe', 'mytextdomain') ?></button>

        <?php wp_nonce_field( 'cpt_nonce_action', 'cpt_nonce_field' ); ?>
        </form>
</div>

<?php get_footer(); ?>