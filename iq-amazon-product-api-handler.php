<?php
set_time_limit(30);
/* Import relevant classes from ApaiIO */
use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Search;

function iq_amazon_shortcode( $atts ) {
	try {
		/* Get the options from the plugin to fill in the API Data or return error */
		global $options;
		$options = get_option( 'iq_amazon_product_api' );
		if( $options != '' ) {
			$iq_amazon_api_key = $options['iq_amazon_api_key'];
			$iq_amazon_secret_key = $options['iq_amazon_secret_key'];
			$iq_amazon_api_key_2 = $options['iq_amazon_api_key_2'];
			$iq_amazon_secret_key_2 = $options['iq_amazon_secret_key_2'];
			$iq_amazon_associate_tag = $options['iq_amazon_associate_tag'];
		} else {
			return 'Please define the Access Key, Secret Key and Associate Key under Settings -> IQ Amazon';
		}

		/* Get the Files from exeu/apai-io to connect to amazon */
		require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'vendor/autoload.php';

		define('AWS_API_KEY', $iq_amazon_api_key);
		define('AWS_API_SECRET_KEY', $iq_amazon_secret_key);
		define('AWS_ASSOCIATE_TAG', $iq_amazon_associate_tag);

		if(isset($iq_amazon_api_key_2) && isset ($iq_amazon_secret_key_2) && mt_rand(0,1) <= 0.5) {
			define('AWS_API_KEY', $iq_amazon_api_key_2);
			define('AWS_API_SECRET_KEY', $iq_amazon_secret_key_2);
		}

		$conf = new GenericConfiguration();
		$client = new \GuzzleHttp\Client(['timeout'  => 2.0]);
		$request = new \ApaiIO\Request\GuzzleRequest($client);
		 $conf
		        ->setCountry('de')
		        ->setAccessKey(AWS_API_KEY)
		        ->setSecretKey(AWS_API_SECRET_KEY)
		        ->setAssociateTag(AWS_ASSOCIATE_TAG)
		        ->setRequest($request)
		        ->setResponseTransformer(new \ApaiIO\ResponseTransformer\XmlToSimpleXmlObject());

		$apaiIO = new ApaiIO($conf);
		$browseNodeLookup = new Search();
		$browseNodeLookup->setResponseGroup(array('Images,ItemAttributes,OfferListings'));
		$browseNodeLookup->setPage(1);
		if(isset($atts['sort'])) {
			$browseNodeLookup->setSort($atts['sort']);
		}
		if(isset($atts['search_index'])) {
			$browseNodeLookup->setCategory($atts['search_index']);
		} else {
			$browseNodeLookup->setCategory('All');
		}

		if( isset($atts['browsenodeid'])) {
			$browseNodeLookup->setBrowseNode($atts['browsenodeid']);
		} else if( isset($atts['search'])) {
			$browseNodeLookup->setKeywords($atts['search']);
		}

		$itemcount = $atts['count'];
		$pages = ceil($itemcount / 10);
		$currentPage = 1;
		$responses = array();
		$all_elements = array();

		/* do stuff here */

		$counter = 0;
		while($currentPage <= $pages) {
			$browseNodeLookup->setPage($currentPage);
			try {
				$xml = $apaiIO->runOperation($browseNodeLookup);
			} catch(Exception $e1) {
				if($counter >= 30) {
					break;
				}
				usleep(500000);
			}
			if($xml) {
				$all_elements =  iq_parse_data($xml, $itemcount, $all_elements);
				$currentPage++;
			} else {
				if($counter >= 30) {
					break;
				}
			}
			$counter++;
		}


		$json_string = json_encode( $all_elements );
		$input = json_decode($json_string);
		$output = iq_build_output( $input );

		return $output;
	} catch (Exception $e) {
		return $e->getMessage();
	}

}

function iq_amazon_build_output( $parsedData ) {

	$json_string = json_encode($response);
	$response = json_decode($json_string, TRUE);
	$output = '<div>';
	foreach( $parsedData['Items']['Item'] as $item ) {
		$output .= '<div class="iq_around iq_shadow">';
		$output .= '<h3 class="iq_title_around" style="padding-right: 40px;" >';
		$output .= $item['ItemAttributes']['Title'];
		$output .= '</h3>';
		$output .= '</div>';
	}
	$output .= '</div>';
	return $output;
}

function iq_amazon_parse_data( $responses ) {
	$parsedData = array();
	$parsedData['Items'] = array();
	$parsedData['Items']['Item'] = array();
	$json_string = json_encode($responses);
	$responses = json_decode($json_string, TRUE);
	foreach( $responses as $response ) {
		foreach( $response['Items']['Item'] as $item ) {
			array_push( $parsedData['Items']['Item'], $item );
		}
	}
	return $parsedData;
}
//checks, if ratings can be loaded.
//if they can be loaded, they will be saved in db too
function iq_handle_ratings($temp_obj){
	if($temp_obj->iframe_url != "" AND $temp_obj->has_reviews){
		$temp_iframe_return =  iq_get_rating_from_iframeurl($temp_obj->iframe_url);
		$temp_obj->rating = $temp_iframe_return['rating'];
		$temp_obj->num_reviews = $temp_iframe_return['num_reviews'];

		global $wpdb;
		$table_name = $wpdb->prefix . 'easy_amazon_product_information_data';
		$wpdb->update($table_name , array('review_information' => json_encode(array('iframe_url' => $temp_obj->iframe_url,
			'has_reviews' => $temp_obj->has_reviews,
			'rating' => $temp_obj->rating,
			'num_reviews' => $temp_obj->num_reviews)
		)),
		array('asin' =>$temp_obj->asin));

	}
	return $temp_obj;
}

//builds the output
function iq_build_output($product_arr)
{
	//json_decode the entry in db.
	// foreach($product_arr as $key => $value){
	// 	if(isset($product_arr[$key]->review_information)){
	// 			if($review_info = json_decode($product_arr[$key]->review_information)){
	// 			$product_arr[$key]->has_reviews = $review_info->has_reviews;
	// 			$product_arr[$key]->iframe_url = $review_info->iframe_url;
	// 			$product_arr[$key]->num_reviews = $review_info->num_reviews;
	// 			$product_arr[$key]->rating = $review_info->rating;
	// 		}else{
	// 			$product_arr[$key]->has_reviews = false;
	// 			$product_arr[$key]->iframe_url = "";
	// 			$product_arr[$key]->num_reviews = 0;
	// 			$product_arr[$key]->rating = "";
	// 		}
	// 	}

	// 	//(rating == ""), if ratings aren't loaded yet
	// 	//check if ratings should even be loaded
	// 		//$product_arr[$key] = iq_handle_ratings($product_arr[$key]);
	// }
	$ret = "";
	$increasing_number = '1';
	//'display_savings' will get true, if the savings are shown in the corner right on the top.
	$display_savings = false;
	$ret .= '<div>';

		foreach($product_arr as $sA){

			$ret .= "<div class='iq_around ";
			$ret .= "iq_shadow";
			$ret .= "'";

			$ret .= " >";

			if(isset($sA->amount_saved) AND $sA->amount_saved != ""){

				$saving = str_replace("EUR" ,"", $sA->amount_saved) + str_replace("EUR" ,"", $sA->new_price);
				if($saving == 0) {
					$saving = '100';
				} else {
					$saving = round((str_replace("EUR" ,"", $sA->amount_saved)/$saving) * 100, 0);
				}

				$ret .= "<div class='iq_saving_procent' >-$saving%</div>";
				$display_savings = true;
			}
			$url = iq_build_url($sA);

			if(isset($sA->title)){
				$ret .= "<h3";
				$ret .= " class='iq_title_around'";
				if($display_savings){
					$ret .= "style='";
					if($display_savings){
						$ret .= "padding-right: 40px; ";
					}
					$ret .= "'";
				}

				$ret .= " >";

				$ret .=  "<a class='iq_product_title' ";
				$ret .= 'title="'. $sA->title . '"';

				$ret .= "	target='_blank' rel='nofollow' href='$url'><span class='increasingnumber'>";
				$ret .= $increasing_number . ".</span> ";
				$ret .= "$sA->title</a>";
				$ret .= "</h3>";
			}
			$ret .= iq_build_picture_output($sA, $url, $increasing_number);
			$ret .= '<div class="columns large-8">';
			$ret .= iq_build_feature_output($sA, $url, $increasing_number);

			//display of rating and prime logo
			$ret .= iq_information_output($sA, $url );
			//display of button and price
				$ret .= "<div class='";
					$ret.="iq_custom_info";
				$ret.="'>";
				$ret .= iq_build_button_output($sA, $url );
				$ret .= iq_build_price_output($sA);
				$ret .= "</div>";
			global $options;
			$options = get_option( 'iq_amazon_product_api' );
			$ret .= "<div class='";
					$ret.="iq_custom_info";
				$ret.="'>";
			if( isset($options['iq_ebay_campaign_id'])) {

				$ret .= iq_build_ebay_button_output($sA);
				$ret .= '<span class="iq-or">oder </span>';
			}
			$ret .= "</div>";
			$ret .= '</div>';
			$ret .= "</div>";
			$increasing_number++;
		}
		$ret .= "</div>";
	return $ret;
}
//builds feature output
function iq_build_feature_output($sA, $url, $increasing_number){
	$ret = "";
	if($increasing_number < 10){
	}
	if(isset($sA->features)){
		if(!is_array($sA->features)){
			$sA->features = json_decode($sA->features);
		}
		$sA->features = preg_replace("#\[\-\]#", "\"", $sA->features);
		if(sizeof($sA->features) > 0){
				$ret .="<div class='iq_feature_div' ";
				$ret .= "><ul class='iq_feature_list'>";
				$n_feature = 0;
				if($sA->features) {
				foreach($sA->features as $f){
					$ret .= "<li ";
					$ret .= ">";
					$ret .= $f;
					$ret .= "</li>";

					$n_feature++;
					if($n_feature >= 5){
						break;
					}
				}

				$ret .= "</ul></div>";
			}
		}
	}
	return $ret;
}

//compares the setting of the backend and the setting of the param and chooses the dominant one.
function iq_dominating_setting($backend_state, $param_state){
	if($param_state){
		return 1;
	}else if($param_state == '' or !isset($param_state)){
		if($backend_state){
			return 1;
		}else{
			return 0;
		}
	}else{
		return 0;
	}
}
//build prime and rating output
function iq_information_output($sA, $url) {
	$ret = "";
	$ret .= "<div class='";
		$ret.="iq_custom_info";
	$ret.="'>";
	$ret .= iq_build_prime_output($sA);
	$ret .= "</div>";
	return $ret;
}

function iq_build_prime_output($sA){
	$ret = "";
	if($sA->has_prime){
		$ret .= "<div class='";
		$ret .= "iq_prime_wrapper";
	$ret .="' ><img src='". plugins_url( 'images/iq_logos.png', __FILE__ ) . "' class='iq_prime_logo'
	alt='prime logo' title='".__('prime logo', 'easy-amazon-product-information')."'></div>";
	}
	return $ret;
}
function iq_build_number_ratings($sA, $url){
	$ret = "";
		if($sA->num_reviews > 0){
			$ret .= "<a  target='_blank' rel='nofollow' alt='".__('ratings', 'easy-amazon-product-information')."'  title='".__('ratings', 'easy-amazon-product-information')."'
			";
			$ret .= " class='iq_rating_link' ";
			//set google analytics tag
			if(get_option('iq_analytics_tracking') != ""){
				$ret .= iq_build_analytics_information($sA);
			}
			$ret .=" href='$url".'\#customerReviews'."'
			$ret .=><span class='";
				$ret .= "iq_number_rating_box";
			$ret .= "'";
			$ret .= ">";
			$ret .=	$sA->num_reviews . " ";
			if($sA->num_reviews == 1){//manage plural
				$ret .=  __('Bewertung', 'easy-amazon-product-information');
			}else{
				$ret .=  __('Bewertungen', 'easy-amazon-product-information');
			}
			$ret .= "</span></a>";
	}
	return $ret;
}
function iq_build_rating_output($sA, $url){
	$ret = "";
		if($sA->rating > 0 AND $sA->rating != ""){
			$ratings_round = $sA->rating;
			$star_shift = (5 - floor($ratings_round)) * 18;
			preg_match("#\.#", $ratings_round, $matches);
			$ret .= "<div class='";
				$ret .= "iq_rating_wrapper";
			$ret .= "'";
			$ret .="><a rel='nofollow' target='_blank' rel='nofollow' alt='".__('amazon product ratings', 'easy-amazon-product-information')."'  title='".__('amazon product ratings', 'easy-amazon-product-information')."' ";
			//set google analytics tag
			if(get_option('iq_analytics_tracking') != ""){
				$ret .= iq_build_analytics_information($sA);
			}
			$ret .=" href='$url".'\#customerReviews'."'><img src='". plugins_url( 'images/iq_logos.png', __FILE__ ) . "'";
			if(sizeof($matches) > 0){
				$star_shift += 180;
				$ret .= " style='left:-" . $star_shift . "px'";
			}else{
				$ret .=  " style='left:-" . $star_shift . "px'";
			}
			$ret .= "  alt='".__('amazon product ratings', 'easy-amazon-product-information')."'  title='".__('amazon product ratings', 'easy-amazon-product-information')."' class='iq_rating'></a></div>";
		}
	return $ret;
}

//builds all parameters for analytics
function iq_build_analytics_information($sA){
	$ret = "";
	$label = "default";
	$category = "";
	$current_page_id = get_the_ID();

		$analytic_information .= $sA->asin;
		$category = "iq amazon";
		$label =  $sA->asin;
	//user can add aditional values, which are displayed in analytics later.

	$ret .= 'onclick="__gaTracker('."'".'send'."'".', '."'".'event'."'".', '."'".$category."'".', '."'".$analytic_information."'".', '."'".$label."'" . ')" ';
	return $ret;
}

//builds the url: if product_link == '', it's an amazon-link, else it can be an extern or intern link (not amazon).
function iq_build_url($sA){
	//is an amazon-link
	global $options;
	$options = get_option( 'iq_amazon_product_api' );
		$iq_amazon_associate_tag = $options['iq_amazon_associate_tag'];
	$url = "";
		if(isset($sA->asin)){
			$url = "https://www.amazon.de/dp/$sA->asin/?tag=" . $iq_amazon_associate_tag;
		}
	return $url;
}
function iq_build_button_output($sA, $url){
	$ret = "";
		$ret .= "<a ";
		$ret .= 'title="'. $sA->title . '"';
			$ret .=" class='iq_amazon_button_style' ";
		//set google analytics tag
		if(get_option('iq_analytics_tracking') != ""){
			$ret .= iq_build_analytics_information($sA);
		}
		//externe URL
		$ret .= " target='_blank' rel='nofollow' ";
		$ret .= " href='$url'>Bei Amazon kaufen!";

		//the inner statement is always same.
		$ret .= "</a>";
	return $ret;
}
function iq_build_ebay_button_output($sA) {
	$ret = "";
		$ret .= "<a ";
		$ret .= 'title="'. $sA->title . '"';
			$ret .=" class='iq_ebay_button_style' ";
		//set google analytics tag
		if(get_option('iq_analytics_tracking') != ""){
			$ret .= iq_build_analytics_information($sA);
		}
		if(get_option('iq_ebay_campaign_id') != '') {

		}
		//externe URL
		$ret .= " target='_blank' rel='nofollow' ";
		$ret .= " href='http://www.ebay.de/sch/i.html?LH_TitleDesc=1&_nkw=$sA->title'>Bei eBay kaufen!";

		//the inner statement is always same.
		$ret .= "</a>";
	return $ret;
}
function iq_build_link_output($sA, $url){

	$ret = "<a";
	$ret .= ' title="';
		$ret .= $sA->title;
	$ret .= '"';
	//externe URL
		$ret .= " target='_blank' rel='nofollow' ";
	//get google analytics code
	if(get_option('iq_analytics_tracking') != ""){
		$ret .= iq_build_analytics_information($sA);
	}
	$ret .= " href='$url' >";
		$ret .= $sA->title;
	$ret .= "</a>";
	return $ret;
}

function iq_build_picture_output($sA, $url, $increasing_number){
	$ret = "";
		$fallback = array('large' => 'medium', 'medium'=> 'small', 'small' => 'medium');
		$picture_url = "";
		$picture_size = "";
		$picture_resolution = "";
		$picture_variant_to_display = array();

		//if the user doesn't want to use the default (first) image, he can choose another picture with a parameter

			foreach(array('small_image', 'medium_image', 'large_image') as $image){
				$temp_arr = json_decode($sA->$image);
					$picture_variant_to_display[$image] = $temp_arr[0];
			}

		//set medium as default.
		if($picture_size == ""){
			$picture_size = "medium";
		}
		//if no picture_resolution is selected, take value of picture_size
		if($picture_resolution == ""){
			$picture_resolution = $picture_size;
		}
		$selected_size = $picture_resolution . "_image";
		$fall_attribute = $fallback[$picture_size] . "_image";
		if(isset($picture_variant_to_display[$selected_size] )){
			if($picture_variant_to_display[$selected_size]  != ""){
				$picture_url = $picture_variant_to_display[$selected_size] ;
			}else{
				$picture_size = $fallback[$picture_size];
				$picture_url = $picture_variant_to_display[$fall_attribute];
			}
		}else if(isset( $picture_variant_to_display[$fall_attribute])){
			$picture_size = $fallback[$picture_size];
			$picture_url = $picture_variant_to_display[$fall_attribute];
		}

		$picture_url = str_replace('http://ecx.', 'https://images-na.ssl-', $picture_url);

		if($picture_url != ""){
			$ret = "<div class='image-wrapper columns large-4'><a";
			$ret .= ' title="'. $sA->title . '"';
			$ret .= " target='_blank' rel='nofollow' href='$url'><img";
			$ret .= ' title="'. $sA->title . '"';
			$ret .= ' alt="'. $sA->title . '" ';
				$ret .="iq_product_image_box";
			$ret .= "' ";
			$ret .= "style='";
			$ret .= "'";
			$ret .= " src='$picture_url'></a></div>";
		}
	return $ret;
}
function iq_build_price_output($sA){
	$ret = "";
		$price_color = '#1f1f1f;';
		if(isset($sA->new_price)){
			$ret .= "<span ";
				$ret .= " class='iq_price_field ";
					$ret .= "iq_price_field_box";
				$ret .= "' ";
			//change of custom colour
			if($price_color != ""){
				$ret .= "style='";
					$ret .= "color: ".$price_color."; ";
				$ret .= "'";
			}
			$ret .= ">";
			$ret .=iq_build_formatted_price_display($sA->new_price) .  " €" ;
				$ret .= "</span>";
		}
		if(isset($sA->amount_saved)){
			if($sA->amount_saved != ""){
					$ret .= "<span class='iq_price_amount_saved ";
						$ret .= "iq_price_amount_saved_box";
					$ret .= "' ";
						$ret .= "style='color: #1f1f1f; '";
					$ret .= ">";
				$temp_saved = iq_build_formatted_price_display($sA->new_price, $sA->amount_saved);

				$ret .= "<s> ". $temp_saved. " €" . "</s>";
					$ret .= "</span>";
			}
		}
	return $ret;
}
//returns the price in a formatted way for output
//$opt_price2 ist optional and gets added to the first value
function iq_build_formatted_price_display($price1, $opt_price2 = null){

	if(isset($opt_price2)){
	return  number_format(str_replace(",", ".", trim(str_replace(array("EUR", "."), "", $opt_price2))) +
				str_replace(",", ".", trim(str_replace(array("EUR", "."), "", $price1))), 2, ",", ".");
	}else if($price1 != 'N/A'){
	 return	number_format(str_replace(",", ".", trim(str_replace(array("EUR", "."), "", $price1))), 2, ",", ".");
	}
	else {
		return 'N/A';
	}
}

function iq_parse_data($xml, $num_of_wanted_results, $ret_elements)
{
	$count_of_pushed_elemets = 0;

	foreach( $xml->Items->Item as $xmlItem)
	{
			$arr = array();
			$small_img_arr = array();
			if(isset($xmlItem->SmallImage->URL)){
				array_push($small_img_arr, (String)$xmlItem->SmallImage->URL);
			}
			$medium_img_arr = array();
			if(isset($xmlItem->MediumImage->URL)){
				array_push($medium_img_arr, (String)$xmlItem->MediumImage->URL);
			}
			$large_img_arr = array();
			if(isset($xmlItem->LargeImage->URL)){
				array_push($large_img_arr, (String)$xmlItem->LargeImage->URL);
			}
			if(isset($xmlItem->ImageSets)){
				foreach($xmlItem->ImageSets->ImageSet as $imageSet){
					if(isset($imageSet->SmallImage->URL)){
						array_push($small_img_arr, (String)$imageSet->SmallImage->URL);
					}if(isset($imageSet->MediumImage->URL)){
						array_push($medium_img_arr, (String)$imageSet->MediumImage->URL);
					}if(isset($imageSet->LargeImage->URL)){
						array_push($large_img_arr, (String)$imageSet->LargeImage->URL);
					}
				}
			}
			$arr['small_image'] = json_encode($small_img_arr);
			$arr['medium_image'] = json_encode($medium_img_arr);
			$arr['large_image'] = json_encode($large_img_arr);

			if(isset($xmlItem->EditorialReviews->EditorialReview->Content)){
				$arr['description'] = str_replace("\n", "", strip_tags((String)$xmlItem->EditorialReviews->EditorialReview->Content));
			}
			if(isset($xmlItem->ItemAttributes->Title)){
				$arr['title'] = (String)$xmlItem->ItemAttributes->Title;
			}
			if(isset($xmlItem->ItemAttributes->Feature)){
				$feature_arr = array();
				foreach($xmlItem->ItemAttributes->Feature as $feature){
					array_push($feature_arr , (string)$feature);
				}
				$arr['features'] = json_encode($feature_arr, JSON_UNESCAPED_UNICODE);
			}
			if(isset($xmlItem->ASIN)){
				$arr['asin'] = (String)$xmlItem->ASIN;
			}
			if(isset($xmlItem->Offers->Offer->OfferListing->SalePrice->FormattedPrice)){
				$arr['new_price'] = trim(str_replace("EUR", "", (String)$xmlItem->Offers->Offer->OfferListing->SalePrice->FormattedPrice));
			}else if(isset($xmlItem->Offers->Offer->OfferListing->Price->FormattedPrice)){
				$arr['new_price'] = trim(str_replace("EUR", "", (String)$xmlItem->Offers->Offer->OfferListing->Price->FormattedPrice));
			}else if(isset($xmlItem->ItemAttributes->ProductTypeName)AND $xmlItem->ItemAttributes->ProductTypeName =="ABIS_EBOOKS"){
				$arr['new_price'] = "0,0";
			} else {
				$arr['new_price'] = "N/A";
			}
			if(isset($xmlItem->Offers->Offer->OfferListing->AmountSaved->FormattedPrice)){
				$arr['amount_saved'] =  trim(str_replace("EUR", "", (String)$xmlItem->Offers->Offer->OfferListing->AmountSaved->FormattedPrice));
			}
			if(isset($xmlItem->CustomerReviews->HasReviews)){
				if((String)$xmlItem->CustomerReviews->HasReviews == "true"){
					$has_reviews = 1;
				}else{
					$has_reviews = 0;
				}
			}
			if(isset($xmlItem->CustomerReviews->IFrameURL)){
				$iframe_url = (String)$xmlItem->CustomerReviews->IFrameURL;
			}
			$arr['review_information'] = json_encode(array('has_reviews' => $has_reviews, 'iframe_url' => $iframe_url,
			'num_reviews' => '', 'rating' => ''
			));

			if(isset($xmlItem->Offers->Offer->OfferListing->IsEligibleForPrime)){
				$arr['has_prime'] = (String)$xmlItem->Offers->Offer->OfferListing->IsEligibleForPrime;
			}
			if(isset($xmlItem->Offers->Offer->OfferListing->AvailabilityAttributes->AvailabilityType)){
				$arr['availability'] = (String)$xmlItem->Offers->Offer->OfferListing->AvailabilityAttributes->AvailabilityType;
			}else{
				$arr['availability'] = 'now';
			}
			if(($num_of_wanted_results == 1 AND $count_of_pushed_elemets < 1) OR
			($num_of_wanted_results > 1 AND sizeof($ret_elements) < $num_of_wanted_results) ){
				array_push($ret_elements, $arr);
				$count_of_pushed_elemets++;
				if(sizeof($ret_elements) >= 10){//more than 10 results are requestet
					if($num_of_wanted_results == ($count_of_pushed_elemets ) ){
						break;
					}
				}else{
					if($num_of_wanted_results == $count_of_pushed_elemets){
						break;
					}
				}
			}
		}
	return $ret_elements;
}

function iq_get_rating_from_iframeurl($url){

$opts = array(
	'http'=>array(
	'header' => 'Connection: close',
	'ignore_errors' => true
	)
);
$context = stream_context_create($opts);
$ratings_round = "";
$num_reviews = "";
@$temp = file_get_contents($url, false, $context);

if(isset($http_response_header)){
	if(preg_match("#200#", $http_response_header[8])){//error message is created, when HTTP status != 200
		preg_match("#\d\.\d\svon\s5\sSternen#", $temp, $matches);
		$num_reviews =0;
		$ratings =0;
		if(isset($matches[0])){
			$ratings = preg_replace("#von\s5\sSternen#", "", $matches[0]);
		}
		$ratings_round = round($ratings * 2, 0)/2;
		preg_match("#((\d)(\.))?\d+\sKundenrezension#", $temp, $matches);
		if(isset($matches[0])){
			$num_reviews = preg_replace("#(Kundenrezension|\s)#", "", $matches[0]);
		}
	}
}
$ret = array('rating' => $ratings_round, 'num_reviews' =>$num_reviews);

return $ret;
}

?>
