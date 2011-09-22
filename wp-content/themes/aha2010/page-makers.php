<?php
//Number of makers per table row.
define('MAX_COLS', 3);
get_header(); ?>

		<div id="container">
			<div id="content" role="main">

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  				<h1 class="entry-title"><?php the_title(); ?></h1>
					<div class="entry-content">
						<?php
						  the_content();
						  
						  //Print out a nice table of makers, including their avatar and name, linking to their full profile.
						  $output = '<table class="all-makers-table"><tr>';
						  
						  //Get all user IDs.
						  //TODO: Might want to sort this differently at some point.  Perhaps by membership status?
						  $userIdArr = $wpdb->get_col($wpdb->prepare("SELECT $wpdb->users.ID FROM $wpdb->users ORDER BY user_nicename ASC"));
						  //Keep track of table row size.
						  $numCols = 0;
						  
						  foreach($userIdArr as $userId) {
						    //Skip over private profiles.
						    if(get_cimyFieldValue($userId, 'PRIVATE_PROFILE', 'YES')) {
                  continue;
                }
                
                //Get the user data for this user.
                $userData = get_userdata($userId);
                $profileLink = '<a href="' . get_author_posts_url($userId) . '?profileview" />';
                
                $output .=
                '<td class="all-makers-table-cell"><table class="maker-table">' . 
                  '<tr><td class="maker-table-avatar">' . $profileLink . get_avatar($userData->user_email, apply_filters('aha2010_author_bio_avatar_size', 150)) . '</a></td></tr>' .
                  '<tr><td class="maker-table-username">' . $profileLink . $userData->display_name . '</a></td></tr>' .
                '</table></td>';
                
                //Check if we should start a new table row.
                if(++$numCols >= MAX_COLS) {
                  $output .= '</tr><tr>';
                  $numCols = 0;
                }
						  }
						  
						  $output .= '</tr></table>';
						  echo $output;
						?>
						
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'aha2010' ), 'after' => '</div>' ) ); ?>
						<?php edit_post_link( __( 'Edit', 'aha2010' ), '<span class="edit-link">', '</span>' ); ?>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->

				<?php comments_template( '', true ); ?>

<?php endwhile; ?>

			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
