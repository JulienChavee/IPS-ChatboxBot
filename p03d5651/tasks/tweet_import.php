<?php
/**
 * @brief		tweet_import Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	p03d5651190
 * @since		15 Mar 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\pluginTasks;

require "twitteroauth/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * tweet_import Task
 */
class _tweet_import extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
		try {
			$connexion = new TwitterOAuth('MDP6PBcgJ3NOQ6kzTw', '7RGS6iHnk37ZNxOvIwcWIEPZReF9kkUxjTu8bj0iojw', '283255274-9j6ClbYWJYF02tu6fALcGVvJtyThu21LMvReKPYH', '7chI1Y8WIqx2YQMMdnqKyaJFI3soH0QJdmYRm8SnYU');

			$connexion->setTimeouts(10, 15);

			$cache = json_decode( \IPS\Settings::i()->tweet_cache, true );
			$users = json_decode( \IPS\Settings::i()->tweet_import_users );

			foreach( $users as $k => $v ) {

				if( isset( $cache[$v] ) ) {
					$tweets = array_reverse( $connexion->get( 'statuses/user_timeline', array( 
						'screen_name'	=> $v,
						'since_id' => $cache[$v]
					) ) );
				} else {
					$tweets = $connexion->get( 'statuses/user_timeline', array( 
						'screen_name'	=> $v,
						'count' => 1
					) );
				}
				
				if( $connexion->getLastHttpCode() == 200 ) {
					foreach( $tweets as $k => $v2 ) {
						$time = \IPS\Settings::i()->tweet_time ? (new \DateTime($v2->created_at, new \DateTimeZone('UTC')))->getTimestamp() : time(); 

						try {
							\IPS\Db::i()->insert( 'bimchatbox_chat', array(
								'user'		=> \IPS\Settings::i()->tweet_user_post,
								'chat'		=> $this->linkify('@'.$v2->user->screen_name).' : '.$this->format_tweet($v2),
								'time'		=> $time,
							) );
						} catch(\Exception $e) {
							throw new \IPS\Task\Exception( $this, $e->getMessage() );
						} finally {
							$cache[$v] = $v2->id_str;
						}
					}

				} else {
					$return[] = 'Impossible de charger les tweets de l\'utilisateur '.$v.' (Erreur '.$connexion->getLastHttpCode().')';
				}
			}

			/* On update le cache du dernier tweet de chaque users suivis et on reset le cache */
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( $cache ) ), array( 'conf_key=?', 'tweet_cache' ) );
			unset( \IPS\Data\Store::i()->settings );
		}
		catch(\Exception $e) {
			throw new \IPS\Task\Exception( $this, $e->getMessage().'<br>'.$e->getTraceAsString() );
		}
		

		if(isset($return))
			return $return;

		return NULL;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}

	protected function format_tweet($tweet) {
		if(isset($tweet->retweeted_status)) {
			$formatted_text = $this->linkify($tweet->retweeted_status->text);

			$formatted_text = "RT <a target='_blank' href='//twitter.com/".$tweet->retweeted_status->user->screen_name."'>@".$tweet->retweeted_status->user->screen_name."</a> : $formatted_text";
		} else {
			$formatted_text =  $this->linkify($tweet->text);
		}

		return nl2br( $formatted_text );
	}

	protected function linkify($text) {
		// Les liens sont déjà automatiquement parsés. Afin de pouvoir utiliser les balises, je ne précise pas le protocole (http/https) pour les liens twitter (ex : //twitter.com... )
		//$formatted_text = preg_replace('/(\b(www\.|https?\:\/\/)\S+\b)/', "<a target='_blank' href='$1'>$1</a>", $text);
		$formatted_text = preg_replace('/\#(\S+)/', "<a target='_blank' href='//twitter.com/hashtag/$1'>#$1</a>", $text);
		$formatted_text = preg_replace('/\@(\S+)/', "<a target='_blank' href='//twitter.com/$1'>@$1</a>", $formatted_text);

		return $formatted_text;
	}
		
}