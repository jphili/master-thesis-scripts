<?php

/**
 * This script collects pictures and their data from Instagram.
 * Due to API changes made by Instagram in April 2018 (after the data was 
 * already collected), this script is not executable anymore.
 *
 * @author: Julia Philipps
 * PHP version: 5.6.31
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

// get category/hashtag from program params
if (!isset($argv[1])) {
	echo "No category given.\n";
	exit();
}
if (!isset($argv[2])) {
	echo "No number of pictures given.\n";
	exit();
}

$category = $argv[1];
$numPics = $argv[2];

$mediaUrl = "http://www.instagram.com/explore/tags/".$category."/?__a=1";

if (!file_exists($category)) {
	mkdir($category, 0777, true);
	echo "Created folder for $category.\n";
}

$results = file_get_contents($mediaUrl);
$json = json_decode($results);

$filename = $category."/".$category.".csv";

if (!file_exists($filename)) {
	writeCSV($filename, "Picture_url;Photo_posted;User_name;Followed_by;Follows;Full_name;Biography;Gender;Hashtags;Hashtag_count;Image_caption;Pic_category;Pic_name;Content-related;Content-related_count;Emotiveness;Emotiveness_count;Fakeness;Fakeness_count;Insta-Tags;Insta-Tags_count;Isness;Isness_count;Performativeness;Performativeness_count;Sentences;Sentences_count;Count_control;Shortenings/Unknown_words;Links for shortenings/words;Coder_comment");
}

$counter = 0;
$counter = getPictureInfos($json, $counter, $filename, $category);

while ($json->tag->media->page_info->has_next_page) {
	if ($counter < $numPics) {
		//sleep(5);
		$mediaUrl = "http://www.instagram.com/explore/tags/".$category."/?__a=1&max-id=".$json->tag->media->page_info->end_cursor;
    	$results = file_get_contents($mediaUrl);
		$json = json_decode($results);
		$counter = getPictureInfos($json, $counter, $filename, $category);
	}
	else {
		print "Script stopped. Counter: ".$counter."\n";
		exit();
	}
}



function getPictureInfos($json, $counter, $filename, $category) {
	global $jsonUser;

	foreach ($json->tag->media->nodes as $item) {
		if ($item->is_video == false) {
			$counter++;

			if (empty($item->code)) {
				echo "ID empty\n";
				$counter--;
			}
			else {
				$id = $item->code;
				$name = "";
				$biography = "";
				$mediaUrl = "http://www.instagram.com/p/".$id."/?__a=1";
	
    			$resultsM = file_get_contents($mediaUrl);
				$jsonM = json_decode($resultsM);
	
				if ($jsonM->graphql->shortcode_media->owner->is_private == false && $jsonM->graphql->shortcode_media->__typename != "GraphSidecar") {
					$username = $json_m->graphql->shortcode_media->owner->username;

					if (!empty($username)) {
						$userUrl = "http://www.instagram.com/".$username."/?__a=1";
						$resultsUser = file_get_contents($userUrl);
						$jsonUser = json_decode($resultsUser);
		
						if (!empty($jsonUser->user->biography)) {
							$biography = str_replace(array("\r\n", "\r", "\n"), " ", $jsonUser->user->biography);
						}

						if (!empty($jsonUser->user->full_name)) {
							$name = $jsonUser->user->full_name;
						}
					}

    				$hashtags = getHashtags($item->caption);
    				$hashtagCount = count($hashtags);
    				$hashToString = implode(", ", $hashtags);
	
    				if (empty($hashToString)) {
    					$counter--;
    				}
    				else {
    					// save the image
    					$imgUrl = $item->display_src;
    					$img = $category."/".$category."_".$item->code.".jpg";
		
    					if (file_exists($img)) {
    						echo "Picture exists!\n";
    						$counter--;
    					}
    					else {
    						file_put_contents($img, file_get_contents($imgUrl));
    						echo "Saving image.\n";
    								
    						$url = "https://www.instagram.com/p/".$item->code."/";	
    						$caption = str_replace(array("\r\n", "\r", "\n"), " ", $item->caption);
	
    						if (strpos($biography, ";") !== false) {
    							str_replace(";", " ", $biography); 
    						}
    						if (strpos($caption, ";") !== false) {
    							str_replace(";", " ", $caption); 
    						}
    						if ($biography == "") {
    							$biography = "no biography for this user";
    						}

    						$csv = $url.";".$username.";".$jsonUser->user->followed_by->count.";".$jsonUser->user->follows->count.";".$name.";\"".$biography."\";;".$hashToString.";".$hashtagCount.";\"".$caption."\";".$category.";".$category."_".$item->code.".jpg;;;;;;;;;;;;;;;;;;";
    						writeCSV($filename, $csv);
    					}
    				}
   				}
   				else {					// if user profile is private or media is not an image
   					$counter--;
   				}
			}
		}
		return $counter;
	}
}



function getBioAndName($id, $filename) {
	if (empty($id)) {
		echo "ID empty\n";
		return "";
	}
	else {
		$name = "";
		$biography = "";

		$mediaUrl = "http://www.instagram.com/p/".$id."/?__a=1";
		echo $media_url."\n";

    	$results = file_get_contents($mediaUrl);
		$json = json_decode($results);

		if ($json->graphql->shortcode_media->owner->is_private == false) {
			$username = $json->graphql->shortcode_media->owner->username;

			if (!empty($username)) {

				$userUrl = "http://www.instagram.com/".$username."/?__a=1";
				$results = file_get_contents($userUrl);
				$json = json_decode($results);

				if (!empty($json->user->biography)) {
					$biography = str_replace(array("\r\n", "\r", "\n"), " ", $json->user->biography);
				}
				
				if (!empty($json->user->full_name)) {
					$name = $json->user->full_name;
				}
			}
		}
	}
}



function getHashtags($string) {  
    $hashtags = false;  
    preg_match_all("/(#\w+)/u", $string, $matches);  
    if ($matches) {
        $hashtagsArray = array_count_values($matches[0]);
        $hashtags = array_keys($hashtagsArray);
    }
    return $hashtags;
}



function writeCSV($filename, $data) {
  	$handle = fopen($filename, "a");
  	$encoding = mb_detect_encoding($data);
  	$str = mb_convert_encoding($data, "UTF-8", $encoding);
  	if (fwrite($handle, $str . "\n") == false) {
    	echo "Can not write to file $filename.\n";
    	exit();
  	}
  fclose($handle);
}


?>