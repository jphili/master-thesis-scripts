<?php   

/**
 * This script corrects the data according to the hashtag category the 
 * coders decided on. It writes a new result file.
 *
 *
 * @author: Julia Philipps
 * PHP version: 5.6.31
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('content-type:text/html;charset=utf-8');

// hashtag;coder1;coder2;imageName;discuss

if (empty($argv[1] || $argv[2] || $argv[3])) {
	echo "No category or files defined. Exit...\n";
	exit();
}

$category = $argv[1];
$resultFilename = $category."_finished.csv";
$source = $argv[2];
$discussed = $argv[3];
$discussedArr = array();
$imageArray = array();

$discussedHandle = fopen($discussed, 'r') or exit("Couldn't open $discussed.\n");
$headerDiscuss = fgetcsv($discussedHandle, 2000, ";");

while (!feof($discussedHandle)) {
	$line = fgetcsv($discussedHandle, 4000, ";", "\t", "\"");
	$line = str_replace(chr(194).chr(160), "", $line);

	if (isset($line[3])) {
		$imageName = trim($line[3]);
	}
	else continue;

	$hashtag = trim($line[0]);
	$rightCategory = trim($line[4]);

	if (!empty($hashtag)) {
		$imageArray[] = $imageName;
		$discussedArr[] = array($hashtag, $rightCategory, $imageName);
	}
}

fclose($discussedHandle);

// create result file
$resultFile = fopen($resultFilename, "w") or die("Unable to open file!\n");
fwrite($resultFile, "Picture_url;User_name;Followed_by;Follows;Full_name;Biography;Gender;Hashtags;Hashtag_count;Image_caption;Pic_category;Pic_name;Content-related;Content-related_count;Emotiveness;Emotiveness_count;Fakeness;Fakeness_count;Insta-Tags;Insta-Tags_count;Isness;Isness_count;Performativeness;Performativeness_count;Sentences;Sentences_count;Count_control;Shortenings/Unknown_words;Links for shortenings/words;Coder_comment\n");	

// open source file and get header
$sourceHandle = fopen($source, 'r') or exit("Couldn't open $source.\n");
$header = fgetcsv($sourceHandle, 2000, ";");

$counter = 0;


// read source file line by line
while (!feof($sourceHandle)) {
	$line = fgetcsv($sourceHandle, 4000, ";", "\t", "\"");
	$line = str_replace(chr(194).chr(160), "", $line);

	if (isset($line[11])) {
		$imageName = trim($line[11]);
	}
	else continue;

	// build result file
	fwrite($resultFile, implode(";", array_slice($line, 0, 12, true)).";");

	/* the hashtag is only sorted into one category!
	   so check every category for this hashtag and if found, delete it
	   place deleted hashtag in the right category
 	   go through hashtags that need to be corrected 
 	*/

	if (in_array($imageName, $imageArray)) {			// if hashtags in this particular image need to be corrected, set up array for search and correct

		$contentRelatedness = explode(",",str_replace(' ', '', $line[12]));
		$emotiveness = explode(",",str_replace(' ', '', $line[14]));
		$fakeness = explode(",",str_replace(' ', '', $line[16]));
		$instaTags = explode(",",str_replace(' ', '', $line[18]));
		$isness = explode(",",str_replace(' ', '', $line[20]));
		$performativeness = explode(",",str_replace(' ', '', $line[22]));
		$sentences = explode(",",str_replace(' ', '', $line[24]));
	
		$all = array($contentRelatedness, $emotiveness, $fakeness, $instaTags, $isness, $performativeness, $sentences);

		// for each hashtag that needs to be corrected
		foreach ($discussedArr as &$correct) {
			$hashtag = $correct[0];
			// always $catKey plus 1 to get the right category since arrays start at index 0
			$category = $correct[1]-1;				// right category
			$keyCorr = array_search($correct, $discussedArr);


			/* look through all categories for that specific hashtag
			   imageName must be identical ($correct[2])
			   category must be different, otherwise the hashtag was already sorted into the right category
			*/
			foreach($all as $catKey => &$tagArr) {
				
				// if hashtag was found in tagArr
				if (in_array($hashtag, $tagArr)) {
					// check if imageName is identical
					if ($correct[2] === $imageName) {
						if ($catKey != $category) { 		// hashtag needs to be updated
							$counter++;						

							// get key from the tagArr and delete hashtag from wrong 	category
							$key = array_search($hashtag, $tagArr);
							unset($tagArr[$key]);
			
							// write hashtag to right category
							$all[$category][] = $hashtag;
							// unset entry in array with hashtags that need to be corrected for it is now corrected
							unset($discussedArr[$keyCorr]);
							break;
						}
						else {				// no need to update hashtag, just unset key
							unset($discussedArr[$keyCorr]);
							break;
						}
					}
				}
					
			}	
		}
		// write corrected hashtags to file
		fwrite($resultFile, implode(", ", $all[0]).";".$line[13].";".implode(", ", $all[1]).";".$line[15].";".implode(", ", $all[2]).";".$line[17].";".implode(", ", $all[3]).";".$line[19].";".implode(", ", $all[4]).";".$line[21].";".implode(", ", $all[5]).";".$line[23].";".implode(", ", $all[6]).";");
		fwrite($resultFile, implode(";", array_slice($line, 25, 29, true))."\n");
	}
	else {
		// hashtags don't need to be corrected, write to file
		fwrite($resultFile, implode(";", array_slice($line, 12, 29, true))."\n");
	}
}

if (!empty($discussedArr)) {
	echo "There are hashtags left.\n";
	var_dump($discussedArr);
}

echo "Corrected Hashtags in $source: $counter\n";

?>