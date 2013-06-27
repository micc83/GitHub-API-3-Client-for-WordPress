<?php

require_once( 'class-wp-github-client.php' );

$github_client = new Wp_Github_Client( array(
    'client_id'        =>    'client_id',
	'client_secret'    =>    'client_secret',
	'scope'            =>    'gist'
) );

// Create a new gists
$github_client->post( '/gists', array(
	'description' =>  'the description for this gist',
	'public'      =>  true,
	'files'       =>  array(
		'file1.txt'		=> array(
			'content'    => 'String file contents'
		)
	)
) ); 

// Get starred gists of the current user
var_dump( $github_client->get( '/gists/starred' ) ); 

// Delete a gist by ID
$github_client->delete( '/gists/5875745' );

// Update an existing gist
$github_client->patch( '/gists/5875558', array(
    'description' =>  'the description for this gist changed'
) );

// Star a gist
$github_client->put( '/gists/5875706/star' ); 

// Get all the gist of the current user after a given date
$datetime = new DateTime('2012-07-26 23:21:46');

var_dump( $github_client->get( '/gists', array(
    'since'	=>	$datetime->format(DateTime::ISO8601)
) ) ); 
