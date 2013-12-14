<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'MultipleUpload',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Allows users to upload several files at once.',
);
$wgMaxUploadFiles = 5;

$wgExtensionMessagesFiles['MultipleUpload'] = dirname(__FILE__) . '/MultipleUpload.i18n.php';

$wgSpecialPages['MultipleUpload'] = 'MultipleUpload';
$wgAutoloadClasses['MultipleUpload'] = dirname( __FILE__ ) . '/MultipleUpload.body.php';

