//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class hook15 extends _HOOK_CLASS_
{
	private $ceNew;

	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		if( $this->_new AND !isset($this->ceNew) )
		{
			$this->ceNew = TRUE;
		}

		call_user_func_array( 'parent::save', func_get_args() );

		if ( !$this->ceNew )
			return;
		
		if(get_class($this)=='IPS\forums\Topic')
		{
			$forum = $this->container();
			$forumsAccepted = json_decode( \IPS\Settings::i()->forum_to_shoutbox );

			if( in_array( $forum->id, $forumsAccepted ) ) {
				$time = time();
				$user = \IPS\Settings::i()->tweet_user_post;
				$author = $this->author();
				$authorGroup = \IPS\Member\Group::load( $author->member_group_id );
				$authorUrl = preg_replace( '#^https?://#', '//', $author->url() );
				$topicUrl = preg_replace( '#^https?://#', '//', $this->url() );
				$chat = '<a href="'.$authorUrl.'">'.$authorGroup->formatName(  $author->name ).'</a> a posté un nouveau sujet : <a href="'.$topicUrl.'">'.$this->title.'</a>';

				\IPS\Db::i()->insert( 'bimchatbox_chat', array(
										'user'		=> $user,
										'chat'		=> $chat,
										'time'		=> $time,
				) );
			}

			$this->ceNew = FALSE;
		} else
			return;
	}

}
