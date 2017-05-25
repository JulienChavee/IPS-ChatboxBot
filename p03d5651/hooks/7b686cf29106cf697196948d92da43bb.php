//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class hook43 extends _HOOK_CLASS_
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		call_user_func_array( 'parent::execute', func_get_args() );
		if($id = \IPS\Db::i()->select( 'plugin_id', 'core_plugins', array( 'plugin_name=?', 'Chatbox Tweet Import' ) )->first())
		{
			\IPS\Output::i()->sidebar['actions']['extender'] = [
				'icon'  => 'bullhorn',
				'link'  => \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=settings&id={$id}" ),
				'title' => 'Chatbox Tweet Import',
				'color' => 'ipsButton_positive'
			];
		}
	}

}
