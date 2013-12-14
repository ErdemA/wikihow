<?php

if ( !defined('MEDIAWIKI') ) die();

$wgSpecialPages['BuildWikihowArticle'] = 'BuildWikihowArticle';
$wgAutoloadClasses['WikihowArticleEditor'] = dirname(__FILE__) . '/WikihowArticle.class.php';
$wgAutoloadClasses['WikihowArticleHTML'] = dirname(__FILE__) . '/WikihowArticle.class.php';
$wgAutoloadClasses['BuildWikihowArticle'] = dirname(__FILE__) . '/WikihowArticle.class.php';

