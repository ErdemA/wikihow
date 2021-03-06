<?
// this script will POST to websolr the id and title of an article so we can use it for partial searching

/*
	options
	v - verbose
	s - start pageid
	n - number of iterations
	b - batch size (10k is ok and recommended)
	d - only pages touched in the x number of days
	p - post to production url (default is dev url)
	f - do not get image data (much faster..good for only updating page_count)

when running from spare1..i found it works best when running as apache
but first make sure the output file is writeable by apache (or everyone)

run the script with verbose update only pages updated in last 10 days
sudo -u apache php addArticlesToWebsolr.php -n20 -b10000 -d10 -v

run the script on tons of articles from the page_id = 0
sudo -u apache php addArticlesToWebsolr.php -n20 -b10000 -s0

you could run this once a week to update any new pages and new thumbnails
sudo -u apache php addArticlesToWebsolr.php -n20 -b10000 -s0 -d8

you could run this once a week to just update the page_count
sudo -u apache php addArticlesToWebsolr.php -n20 -b20000 -s0 -f

the schema used:

<?xml version="1.0" encoding="utf-8"?>
<schema name="Autocomplete EdgeNGrams v3" version="1.0">
  <types>
    <fieldType name="string" class="solr.StrField"  omitNorms="true" />
    <fieldType name="integer" class="solr.IntField"  omitNorms="true" />
    <fieldtype name="text" class="solr.TextField" positionIncrementGap="1">
      <analyzer>
        <tokenizer class="solr.StandardTokenizerFactory" />
        <filter    class="solr.StandardFilterFactory"    />
        <filter    class="solr.LowerCaseFilterFactory"   />
        <filter    class="solr.EdgeNGramFilterFactory" minGramSize="1" maxGramSize="15" side="front"/>
      </analyzer>
    </fieldtype>
    <fieldType name="sfloat" class="solr.SortableFloatField" omitNorms="true"/>
  </types>
  <fields>
    <field name="id" stored="true" type="integer" multiValued="false" indexed="true" />
    <field name="title" stored="true" type="text"   multiValued="true"  indexed="true" />
    <field name="page_counter" stored="true" type="sfloat" multiValued="false" indexed="true" />
    <field name="image_58x58" stored="true" type="text"   multiValued="false"  indexed="false" />
  </fields>
  <uniqueKey>id</uniqueKey>
  <defaultSearchField>title</defaultSearchField>
  <solrQueryParser defaultOperator="AND" />
</schema>

synonyms section (still default and can be improved)

#-----------------------------------------------------------------------
#some test synonym mappings unlikely to appear in real input text
aaa => aaaa
bbb => bbbb1 bbbb2
ccc => cccc1,cccc2
a\=>a => b\=>b
a\,a => b\,b
fooaaa,baraaa,bazaaa

# Some synonym groups specific to this example
GB,gib,gigabyte,gigabytes
MB,mib,megabyte,megabytes
Television, Televisions, TV, TVs
#notice we use "gib" instead of "GiB" so any WordDelimiterFilter coming
#after us won't split it into two words.

# Synonym mappings can be used for spelling correction too
pixima => pixma

---end synonyms section

-- sample curl to post a new doc to websolr
	curl http://ec2-west.websolr.com/solr/d4901f648d5/update
	-H "Content-type: text/xml"
	--data-binary  '<add><doc>
	<field name="id">2053</field>
	<field name="title">How to Kiss</field>
	<field name="page_counter">12667739</field>
	<field name="image_58x58">http://www.wikihow.com/images/thumb/9/97/Kiss-Step-17.jpg/-crop-58-58-58px-nowatermark-Kiss-Step-17.jpg</field>
	</doc></add>';

same as before but all in one line so easy to copy/paste
	curl http://ec2-west.websolr.com/solr/d4901f648d5/update -H "Content-type: text/xml" --data-binary  '<add><doc><field name="id">2053</field><field name="title">How to Kiss</field><field name="page_counter">12667739</field><field name="image_58x58">http:/www.wikihow.com/images/thumb/9/97/Kiss-Step-17.jpg/-crop-58-58-58px-nowatermark-Kiss-Step-17.jpg</field></doc></add>';

example search url:
	http://ec2-west.websolr.com/solr/d4901f648d5/select?q=Kiss&sort=page_counter+desc&defType=edismax&wt=json&fl=id,title,score
	 */


// import helper functions
global $IP;
define('WH_USE_BACKUP_DB', true);
require_once( dirname(__FILE__).'/../commandLine.inc' );
require_once("$IP/extensions/wikihow/ApiApp.body.php");

$time = -microtime(true);

// file to write the last page we updated so we can rerun this script and not have it start from the beginning
$file = realpath(dirname(__FILE__))."/addtitles_lastpage";
$fileContents = @file($file);
$lastPage = $fileContents?$fileContents[0]:0;

// reuse the handle to the db
$dbr = wfGetDB(DB_SLAVE);

$url = "http://ec2-west.websolr.com/solr/d4901f648d5/update";
$options = getopt("b:n:s::d::p::v::f::");

if (array_key_exists('v', $options)) {
	$verbose = true;
}

if (array_key_exists('d', $options)) {
	$days = intval($options['d']);
	if ($verbose) {
		decho("will only update documents touched in last $days days", false, false);
	}
	$since = wfTimestamp( TS_MW, time() - 60 * 60 * 24 * $days );
}

if (array_key_exists('f', $options)) {
	echo "will not process images\n";
	$noImages = true;
}

if (array_key_exists('p', $options)) {
	if (!IS_PROD_EN_SITE) {
		echo("cannot update production db when not on production server\n");
		exit();
	}
	echo "will use production url\n";
	$url = "http://index.websolr.com/solr/1040955300c/update";
}

if (array_key_exists('s', $options)) {
	$lastPage = intval($options['s']);
}

decho("will process pages greater than", $lastPage, false);

$ch = curl_init();
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_URL, $url);

// how many documents to send in each post
$batchSize = intval($options['b']);
$numberOfBatches = intval($options['n']);
for ($i = 0; $i < $numberOfBatches; $i++) {
	$processed = postDocuments($dbr, $ch, $file, &$lastPage, $since, $batchSize, $noImages, $verbose);
	if ($processed < 1) {
		break;
	}
	logTime($time, "processed $processed documents in");
}

removeSpecialPages($ch, $verbose);

// we are done, close curl
curl_close($ch);

/*
 * Removes special pages from websolr we don't want in search results
 */
function removeSpecialPages($ch, $verbose) {
	$deleteData = "<delete>";
	// how to categories page
	$deleteData .= "<id>5791</id>";
	// how to main page
	$deleteData .= "<id>5</id>";
	$deleteData .= "</delete>";
	curl_setopt( $ch , CURLOPT_POSTFIELDS , $deleteData );
	if ($verbose) decho("will delete", $deleteData, false);

	$contents = curl_exec($ch);
	if ($verbose) decho("delete result", $contents, false);
	if (curl_errno($ch)) {
		echo "curl error {$url}: " . curl_error($ch) . "\n";
	}
}
/*
 * does the actual post
 * posts a bunch of documents at once
 *
 */
function postDocuments($dbr, $ch, $lastPageFile, $lastPage, $since, $batchSize, $noImages, $verbose = false) {
	$id = $lastPage;

	$options = array("page_id > $lastPage", "page_namespace = 0", "page_is_redirect = 0");
	if ($since) {
		$options[] = "page_touched > $since";
	}
	$res = $dbr->select("page",
			"page_id, page_title, page_counter",
			$options,
			__FILE__,
			array("ORDER BY"=>"page_id", "LIMIT"=>$batchSize));

	$postData = '<add>';
	$count = 0;
	foreach($res as $row) {
		$id = $row->page_id;
		$postData .= getDocData($row, $noImages);
		$count++;
	}
	if ($count == 0) {
		return -1;
	} 

	$postData .= '</add>';
	if ($verbose) decho("will post", $postData, false);

	curl_setopt( $ch , CURLOPT_POSTFIELDS , $postData );
	$contents = curl_exec($ch);
	if ($verbose) decho("result", $contents, false);
	if (curl_errno($ch)) {
		echo "curl error {$url}: " . curl_error($ch) . "\n";
	}
	@file_put_contents($lastPageFile, $id);
	$lastPage = $id;
	return $count;
}

/*
 * gets the document data given the database row
 *
 */
function getDocData($row, $noImages) {
	$page_id = $row->page_id;
	$page_counter = $row->page_counter;

	$title = Title::newFromDBkey($row->page_title);
	if (!$title || !$title->exists()) {
		decho("unknown title for id", $page_id, false);
		return "";
	}
	$title_text = "<![CDATA[".wfMsg('howto', $title->getText())."]]>";

	if (!$noImages) {
		$image = Wikitext::getTitleImage($title, true)?:AppDataFormatter::getCategoryImageFile($title);
	}

	if ($image) {
		$heightPreference = $image->getWidth() > $image->getHeight();
		$thumb = WatermarkSupport::getUnwatermarkedThumbnail($image, AppDataFormatter::SEARCH_THUMB_WIDTH, AppDataFormatter::SEARCH_THUMB_HEIGHT, true, true, $heightPreference);
	}

	if ($thumb && !($thumb instanceof MediaTransformError)) {
		$thumbUrl = AppDataFormatter::uriencode(wfGetPad($thumb->url));
	}

	$update = "";
	if ($noImages) {
		$update = 'update="set"';
	}
	$postData =  '<doc>'.
		'<field name="id">'.$page_id.'</field>'.
		'<field name="title" '.$update.'>'.$title_text.'</field>'.
		'<field name="page_counter" '.$update.'>'.$page_counter.'</field>';

	if ($thumbUrl) {
		$postData .= '<field name="image_58x58">'.$thumbUrl.'</field>';
	}

	$postData .= '</doc>';
	return $postData;
}

function logTime(&$time, $message = NULL) {
	if (!$message) {
		$message = "time";
	}
	$time += microtime(true);
	echo "$message: ",sprintf('%f', $time),PHP_EOL;
	$time = -microtime(true);
}

