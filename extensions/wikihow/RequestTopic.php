<?php
if ( ! defined( 'MEDIAWIKI' ) )
    die();

/**#@+
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.mediawiki.org/wiki/SpamDiffTool_Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Request Topic',
    'author' => 'Travis Derouin',
    'description' => 'Provides a basic way of suggesting new topics to be added to the article base.',
	'url' => 'none.'
);

$wgSpecialPages['RequestTopic'] = 'RequestTopic';
$wgSpecialPages['ListRequestedTopics'] = 'ListRequestedTopics';
$wgAutoloadClasses['RequestTopic'] = dirname( __FILE__ ) . '/RequestTopic.body.php';
$wgAutoloadClasses['ListRequestedTopics'] = dirname( __FILE__ ) . '/RequestTopic.body.php';

define('NS_ARTICLE_REQUEST', 16);
define('NS_ARTICLE_REQUEST_TALK', 17);
$wgExtraNamespaces[NS_ARTICLE_REQUEST] = "Request";
$wgExtraNamespaces[NS_ARTICLE_REQUEST_TALK] = "Request_talk";
$wgHooks['ArticleSaveComplete'][] = array("RequestTopic::notifyRequests");
$wgHooks['ArticleDelete'][] = array("RequestTopic::uncategorizeRequest"); 
