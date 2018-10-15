<?php   

/**
 * This script structures the data of both coders to run the Krippendorff 
 * alpha calculation in SPSS.
 * Creates a .csv file with the following fields: hashtag, category 
 * according to coder 1, category according to coder 2 and image name.
 *
 * @author: Julia Philipps
 * PHP version: 5.6.31
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('content-type:text/html;charset=utf-8');

// set category and result filename
if (empty($argv[3])) {
	echo "No category defined. Exit...\n";
	exit();
}

$category = $argv[3];
$resultFilename = $category."_kalpha.csv";

// check if coder filenames are in the command line arguments
if (empty($argv[1]) || empty($argv[2])) {
	echo "No coder filenames. Exit.\n";
	exit();	
}


// get filenames of the two coder files from argv
$coder1 = $argv[1];
$coder2 = $argv[2];


/**
 * code text to numbers: 
 * 
 * content-related = 1
 * emotiveness = 2
 * fakeness = 3
 * insta-tags = 4
 * isness = 5
 * performativeness = 6
 * sentences = 7
**/


// set up the result .csv file 
// 0 = Hashtag, 1 = Coder 1, 2 = Coder 2, 3 = imageName
	
$resultFile = fopen($resultFilename, "w") or die("Unable to open file!\n");
fwrite($resultFile, "Hashtag;Coder 1;Coder 2;imageName\n");	

$coder1Array = process_file($resultFile, $coder1);
$coder2Array = process_file($resultFile, $coder2);

$count1 = count($coder1Array);
$count2 = count($coder2Array);

echo "1: ".$count1."\n";
echo "2: ".$count2."\n";


if ($count1 != $count2) {
	echo "Number of hashtags are not equal. There may be an error in your data.\n";
	exit();
}
else {
	// get the hashtags with categories from both coders and write it to the result file (hashtag, coder1 category, coder2 category, image name)
	$arr1 = $coder1Array;
	$arr2 = $coder2Array;
	
	foreach($coder1Array as $key1 => &$value1) {
		$hashtag = $value1[0];
		$category = $value1[1];
		$image = $value1[2];
		
		foreach($coder2Array as $key2 => &$value2) {
			if ($hashtag == $value2[0] && $image == $value2[2]) {
				fwrite($resultFile, "$hashtag;$category;$value2[1];$image\n");
				unset($coder1Array[$key1]);
				unset($coder2Array[$key2]);
			}
		}
	}
	// check if empty and if hashtags were forgotten/excluded
	var_dump($coder1Array);
	var_dump($coder2Array);
}

fclose($resultFile);
	
	

	
function process_file($resultFile, $sourceFile) {
	$counter = 0;	
	$sourceFile = fopen($sourceFile, 'r') or exit("Couldn't open $sourceFile");
	$header = fgetcsv($sourceFile, 2000, ";");

	$data = array();
	while (!feof($sourceFile)) {
		$line = fgetcsv($sourceFile, 4000, ";", "\t", "\"");		
		$line = str_replace(chr(194).chr(160), "", $line);
		// 7 = hashtags, 11 = imageName, 12 = Content-related, 14 = Emotiveness, 16 = Fakeness,
		// 18 = Insta-Tags, 20 = Isness, 22 = Performativeness, 24 = Sentences
		
		$hashtags = explode(",", str_replace(' ', '', $line[7]));  // delete non-breakable spaces
		$imageName = $line[11];
		$contentRelatedness = explode(",",str_replace(' ', '', $line[12]));
		$emotiveness = explode(",",str_replace(' ', '', $line[14]));
		$fakeness = explode(",",str_replace(' ', '', $line[16]));
		$instaTags = explode(",",str_replace(' ', '', $line[18]));
		$isness = explode(",",str_replace(' ', '', $line[20]));
		$performativeness = explode(",",str_replace(' ', '', $line[22]));
		$sentences = explode(",",str_replace(' ', '', $line[24]));
		
		$all = array($contentRelatedness, $emotiveness, $fakeness, $instaTags, $isness, $performativeness, $sentences);
		
		// for checking mistakes
		$check = array();
		
		foreach($all as $arr => &$values) {			
			foreach($hashtags as $i => $tag) {		
				if (in_array($tag, $values) && !empty($tag)) {
					$key = array_search($tag, $values);
					$data[] = array($tag, $arr+1, $imageName);
					unset($hashtags[$i]);
					unset($values[$key]);
				}
			}
		}
		
		// faulty data handling
		if (!empty(array_filter($hashtags))) {			
		
			echo "Faulty data: This hashtag was not coded. Check for spelling or other mistakes in $imageName.\n";
			var_dump($hashtags);
		}

		foreach ($all as $cat => $tags) {
			foreach($tags as $key => $value) {
				if (!empty($value)) {
					$check[] = $all[$arr[$value]];
				}
			}
		}

		if (count($check) > 0) {
			echo "Faulty data: Hashtags are left in $imageName...\n";
			var_dump($all);
		}
	}

	fclose($sourceFile);
	return $data;
}

?>