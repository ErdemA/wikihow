<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 **/

return array(

	//XXCHANGED: reuben/wikihow added groups to reduce URL length

	// big web JS
	'whjs' => array(
		'//extensions/wikihow/common/jquery-1.7.1.min.js',
		#'//skins/common/highlighter-0.6.js',
		'//skins/common/wikibits.js',
		'//skins/common/swfobject.js',
		'//extensions/wikihow/common/jquery.scrollTo/jquery.scrollTo.js',
		'//skins/common/fb.js',
		'//extensions/wikihow/GPlusLogin/gplus.js',
		'//skins/WikiHow/google_cse_search_box.js',
		#'//skins/common/mixpanel.js',
		'//skins/WikiHow/gaWHTracker.js',
		'//extensions/wikihow/common/CCSFG/popup.js',
		'//extensions/wikihow/LoginReminder.js',
		'//skins/common/jquery.menu-aim.js',
	),

	'jqui' => array('//extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery-ui-1.8.13.custom.min.js'),
	'wkt' => array('//extensions/wikihow/common/download.jQuery.js'),
	'rcw' => array('//extensions/wikihow/rcwidget/rcwidget.js'),
	'sp' => array('//skins/WikiHow/spotlightrotate.js'),
	'fl' => array('//extensions/wikihow/FollowWidget.js'),
	'slj' => array('//extensions/wikihow/slider/slider.js'),
	'ppj' => array('//extensions/wikihow/gallery/prettyPhoto-3.12/src/jquery.prettyPhoto.js'),
	'ads' => array('//extensions/wikihow/wikihowAds/wikihowAds.js'),
	'thm' => array('//extensions/wikihow/thumbsup/thumbsnotifications.js'),
	'stu' => array('//skins/common/stu.js'),
	'altj' => array('//extensions/wikihow/altmethodadder/altmethodadder.js'),
	'tpt' => array('//extensions/wikihow/tipsandwarnings/toptentips.js'),
	'hp' => array('//extensions/wikihow/homepage/wikihowhomepage.js'),
	'ts' => array('//extensions/wikihow/textscroller/textscroller.js'),
	'ii' => array('//extensions/wikihow/imagefeedback/imagefeedback.js'),
	'whv' => array(
		'//extensions/wikihow/whvid/whvid.js',
		'//extensions/wikihow/common/flowplayer/flowplayer.min.js',
	),
	'catj' => array('//extensions/wikihow/categories-owl.js'),


	// big web CSS
	'whcss' => array(
		'//skins/owl/main.css',
		'//extensions/wikihow/tipsandwarnings/topten.css',
	),

	'jquic' => array('//extensions/wikihow/common/jquery-ui-themes/jquery-ui.css'),
    'nona' => array('//skins/owl/nonarticle.css'),
	'liq' => array('//skins/owl/liquid.css'),
	'fix' => array('//skins/owl/fixed.css'),
	'hpc' => array('//skins/owl/home.css'),
	'li' => array(
		'//skins/WikiHow/loggedin.css',
		'//skins/owl/loggedin.css',
	),
	'slc' => array('//extensions/wikihow/slider/slider.css'),
	'ppc' => array('//extensions/wikihow/gallery/prettyPhoto-3.12/src/prettyPhoto.css'),
	'altc' => array('//extensions/wikihow/altmethodadder/altmethodadder.css'),
	'tsc' => array('//extensions/wikihow/textscroller/textscroller.css'),
	'tptc' => array('//extensions/wikihow/tipsandwarnings/topten.css'),
	'iic' => array('//extensions/wikihow/imagefeedback/imagefeedback.css'),
	'dvc' => array('//extensions/wikihow/docviewer/docviewer.css'),
	'spc' => array('//skins/owl/special.css'),
	'whvc' => array(
		'//extensions/wikihow/whvid/whvid.css',
		'//extensions/wikihow/common/flowplayer/skin/minimalist.css',
	),
	'catc' => array('//extensions/wikihow/categories-owl.css'),

	// Stubs / Hillary tool
	'stb' => array(
		'//extensions/wikihow/common/canv-gauge/gauge.js',
		'//extensions/wikihow/stubs/hillary.js'
	),
	'stbc' => array('//extensions/wikihow/stubs/hillary.css'),

	// mobile JS
	'mjq' => array('//extensions/wikihow/common/jquery-1.7.1.min.js'),
	'mwh' => array(
		'//extensions/wikihow/mobile/mobile.js',
	),
	'mga' => array('//skins/common/ga.js'),
	'mah' => array('//extensions/wikihow/mobile/add2home/add2home.js'),
	'mqg' => array('//extensions/wikihow/mqg/mqg.js'),
	'thr' => array('//extensions/wikihow/thumbratings/thumbratings.js'),
	'cm' => array('//extensions/wikihow/checkmarks/checkmarks.js'),
	'mscr' => array('//extensions/wikihow/common/jquery.scrollTo/jquery.scrollTo.js'),
	'mtip' => array('//extensions/wikihow/tipsandwarnings/tipsandwarnings.js',),
	'mtpt' => array('//extensions/wikihow/tipsandwarnings/toptentips.js'),
	'maim' => array('//extensions/wikihow/mobile/webtoolkit.aim.min.js'),
	'muci' => array('//extensions/wikihow/mobile/usercompletedimages.js'),

	// mobile CSS
	'mwhc' => array(
		'//extensions/wikihow/mobile/mobile.css',
	),
	'mwhf' => array('//extensions/wikihow/mobile/mobile-featured.css'),
	'mwhh' => array('//extensions/wikihow/mobile/mobile-home.css'),
	'mwhr' => array('//extensions/wikihow/mobile/mobile-results.css'),
	'mwha' => array('//extensions/wikihow/mobile/mobile-article.css'),
	'ma2h' => array('//extensions/wikihow/mobile/add2home/add2home.css'),
	'mqgc' => array('//extensions/wikihow/mqg/mqg.css'),
	'mcmc' => array('//extensions/wikihow/checkmarks/checkmarks.css'),
	'mthr' => array('//extensions/wikihow/thumbratings/thumbratings.css'),
	'msd' => array('//extensions/wikihow/docviewer/docviewer_m.css'),
	'mtptc' => array('//extensions/wikihow/tipsandwarnings/topten_m.css'),

    // 'js' => array('//js/file1.js', '//js/file2.js'),
    // 'css' => array('//css/file1.css', '//css/file2.css'),

    // custom source example
    /*'js2' => array(
        dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
        // do NOT process this file
        new Minify_Source(array(
            'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
            'minifier' => create_function('$a', 'return $a;')
        ))
    ),//*/

    /*'js3' => array(
        dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
        // do NOT process this file
        new Minify_Source(array(
            'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
            'minifier' => array('Minify_Packer', 'minify')
        ))
    ),//*/
);
