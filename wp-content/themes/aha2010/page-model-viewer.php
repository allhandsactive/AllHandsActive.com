<?php 
$modelId = $_GET['mc'];
get_header();
?>

		<div id="container">
			<div id="content" role="main">

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  				<h1 class="entry-title"><?php the_title(); ?></h1>
					<div class="entry-content">
						<?php if(preg_match('/^[a-zA-Z0-9]{5}$/', $modelId) === 1): ?>
							<iframe src="http://p3d.in/e/<?php echo $modelId; ?>+spin+load" width="100%" height="480px" frameborder="0" seamless allowfullscreen webkitallowfullscreen></iframe>
						<?php else: ?>
							<p><strong>There was an error loading your model.  Please ensure the URL is entered correctly.</strong></p>
						<?php
							endif;
						  the_content();
						?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'aha2010' ), 'after' => '</div>' ) ); ?>
						<?php edit_post_link( __( 'Edit', 'aha2010' ), '<span class="edit-link">', '</span>' ); ?>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->

<?php endwhile; ?>

			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
