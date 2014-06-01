<?php

// don't show this panel if there'r no matching sessions
if ( ! count( bstat()->report()->sessions_on_goal() ) )
{
	return;
}

// don't show this panel if there's only one term
$terms = bstat()->report()->terms_for_session( bstat()->report()->sessions_on_goal() );
if ( ! count( $terms ) )
{
	return;
}

$sum_sessions = array_sum( wp_list_pluck( $terms, 'sessions' ) );
$sum_sessions_on_goal = array_sum( wp_list_pluck( $terms, 'sessions_on_goal' ) );
$avg_cvr = $sum_sessions_on_goal / $sum_sessions;
$sum_matching_posts = array_sum( wp_list_pluck( $terms, 'count_in_set' ) );
$sum_posts_in_session = count( bstat()->report()->posts_for_session( bstat()->report()->sessions_on_goal() ) );

// for sanity, limit this to just the top few terms
$terms = array_slice( $terms, 0, bstat()->options()->report->max_items );

echo '<h2>Terms contributing to goal</h2>';
echo '<p>Showing ' . count( $terms ) . ' top terms contributing to ' . number_format( count( bstat()->report()->sessions_on_goal() ) ) . ' goal completions.</p>';
echo '<table>
	<tr>
		<td>Term</td>
		<td>All sessions</td>
		<td>Sessions on goal</td>
		<td>CVR</td>
		<td>Expected sessions on goal</td>
		<td>Difference: goal - expected</td>
		<td>Multiple: goal / expected</td>
	</tr>
';

foreach ( $terms as $term )
{

	// adjust the numbers to reflect the contribution an individual term has among many on each post
	$term->sessions = $term->sessions / $term->count_in_set;
	$term->sessions_on_goal = $term->sessions_on_goal / $term->count_in_set;

	$term->sessions_on_goal_expected = $avg_cvr * $term->sessions;

	printf(
		'<tr>
			<td>%1$s</td>
			<td>%2$s</td>
			<td>%3$s</td>
			<td>%4$s</td>
			<td>%5$s</td>
			<td>%6$s</td>
			<td>%7$s</td>
		</tr>',
		$term->taxonomy . ':' . $term->slug,
		number_format(  $term->sessions, 2 ),
		number_format(  $term->sessions_on_goal, 2 ),
		number_format( ( $term->sessions_on_goal / $term->sessions ) * 100 , 2 ) . '%',
		number_format( $term->sessions_on_goal_expected, 2 ),
		number_format( $term->sessions_on_goal - $term->sessions_on_goal_expected, 2 ),
		number_format( $term->sessions_on_goal / $term->sessions_on_goal_expected, 2 )
	);

/*
	$posts = bstat()->report()->top_posts_for_term( $term, array( 'posts_per_page' => 3, 'post_type' => 'any' ) );
	echo '<ol>';
	foreach ( $posts as $post )
	{
		printf(
			'<li %1$s><a href="%2$s">%3$s</a> (%4$s hits)</li>',
			get_post_class( '', $post->ID ),
			bstat()->report()->report_url( array( 'post' => $post->ID, ) ),
			get_the_title( $post->ID ),
			(int) $post->hits
		);
	}
	echo '</ol></li>';
*/
}

printf(
	'<tr>
		<td>%1$s</td>
		<td>%2$s</td>
		<td>%3$s</td>
		<td>%4$s</td>
		<td>%5$s</td>
		<td>%6$s</td>
		<td>%7$s</td>
	</tr>',
	'Totals:',
	(int) $sum_sessions,
	(int) $sum_sessions_on_goal,
	number_format( ( $sum_sessions_on_goal / $sum_sessions ) * 100 , 2 ) . '%',
	'&nbsp;',
	'&nbsp;',
	'&nbsp;'
);
echo '</table>';