//<?php

$text = json_decode( \IPS\Settings::i()->tweet_import_users );
$temp = \IPS\Settings::i()->tweet_user_post;
$forums = json_decode( \IPS\Settings::i()->forum_to_shoutbox );

$form->add( new \IPS\Helpers\Form\Text( 'tweet_import_users', implode( ',', $text ) ) );
$form->add( new \IPS\Helpers\Form\Member( 'tweet_user_post', $temp ? \IPS\Member::load( $temp ) : NULL ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'tweet_time', \IPS\Settings::i()->tweet_time ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'reset_cache', 0 ) );
$form->add( new \IPS\Helpers\Form\Node( 'forum_to_shoutbox', implode( ',', $forums ), FALSE, array( 'class'	=> 'IPS\forums\Forum', 'multiple' => true ) ) );

if ( $values = $form->values() )
{
	if( is_array( $values['forum_to_shoutbox'] ) ) {
		foreach( $values['forum_to_shoutbox'] as $k => $v )
			$forum_to_shoutbox[] = $v->id;

		$values['forum_to_shoutbox'] = json_encode( $forum_to_shoutbox );
	} else
		$values['forum_to_shoutbox'] = '[]';
	

	$values['tweet_import_users'] = json_encode( explode( ',', $values['tweet_import_users'] ) );
	$values['tweet_user_post'] = $values['tweet_user_post']->member_id;

	if( $values['reset_cache'] )
		$values['tweet_cache'] = '[]';

	$form->saveAsSettings( $values );
	return TRUE;
}

return $form;