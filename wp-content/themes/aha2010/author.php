<?php
/**
 * The template for displaying Author Archive pages.
 *
 * @package WordPress
 * @subpackage Aha_2010
 * @since AHA 2010 1.0
 */

get_header(); ?>

		<div id="container">
			<div id="content" role="main">

<?php
	/* Queue the first post, that way we know who
	 * the author is when we try to get their name,
	 * URL, description, avatar, etc.
	 *
	 * We reset this later so we can run the loop
	 * properly with a call to rewind_posts().
	 */

//Do we divert to author's profile view?

/*
******* NOTE!: I'm not entirely sure if $author comes in as clean input, so we will go ahead and clean it here.
This *could* break things later on if they expect it to not have changed at all, but we're probably safe.
*/
$author = intval($author);

$isPrivate = get_cimyFieldValue($author, 'PRIVATE_PROFILE', 'YES');
if(isset($_GET['profileview']) && !$isPrivate):
  get_template_part('author', 'profileview');
else:
  $authorData = get_userdata($author);
?>
<?php echo get_avatar($authorData->user_email, apply_filters('aha2010_author_bio_avatar_size', 60)); ?>
<h1 class="page-title author"><?php printf( __( 'History for %s', 'aha2010' ), "<span class='vcard'><a class='url fn n' href='" . get_author_posts_url($authorData->ID) . "?profileview' title='" . esc_attr($authorData->display_name) . "' rel='me'>" . $authorData->display_name . "</a></span>" ); ?></h1>
<?php
	/* Run the loop for the author archive page to output the authors posts
	 * If you want to overload this in a child theme then include a file
	 * called loop-author.php and that will be used instead.
	 */
	 get_template_part( 'loop', 'author' );
endif; //End profile view diversion.
?>
			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
