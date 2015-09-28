<?php

/**
 *@author nicolaas [at] sunnysideup up .co .nz
 *
 *
 **/

class PageRater extends DataExtension {

	private static $db = array(
		'PageRating' => 'Double'
	);

	private static $indexes = array(
		'PageRating' => true
	);

	private static $add_default_rating = false;

	private static $round_rating = true;

	private static $number_of_default_records_to_be_added = 5;

	function PageRatingResults() {
		$doSet = new ArrayList();
		$sqlQuery = new SQLQuery();
		$sqlQuery->setSelect("AVG(\"PageRating\".\"Rating\") RatingAverage, ParentID");
		$sqlQuery->setFrom("\"PageRating\" ");
		$sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID."");
		$sqlQuery->setOrderBy("RatingAverage DESC");
		$sqlQuery->setGroupby("\"ParentID\"");
		$sqlQuery->setLimit(1);
		return $this->turnSQLIntoArrayList($sqlQuery, "PageRatingResults");
	}

	function CurrentUserRating() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$doSet = new ArrayList();
		$sqlQuery = new SQLQuery();
		$sqlQuery->setSelect("AVG(\"PageRating\".\"Rating\") RatingAverage, ParentID");
		$sqlQuery->setFrom("\"PageRating\" ");
		$sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID." AND \"Rating\" = '".Session::get('PageRated'.$this->owner->ID)."'");
		$sqlQuery->setOrderBy("RatingAverage DESC");
		$sqlQuery->setGroupby("\"ParentID\"");
		$sqlQuery->setLimit(1);
		return $this->turnSQLIntoArrayList($sqlQuery, "CurrentUserRating");
	}

	function PageRaterList() {
		$doSet = new ArrayList();
		$sqlQuery = new SQLQuery();
		$sqlQuery->setSelect("AVG(\"PageRating\".\"Rating\") RatingAverage, ParentID");
		$sqlQuery->setFrom(" \"PageRating\", \"SiteTree\"  ");
		$sqlQuery->setWhere("\"ParentID\" = \"SiteTree\".\"ID\"");
		$sqlQuery->setOrderBy("RatingAverage DESC");
		$sqlQuery->setGroupby("\"ParentID\"");
		return $this->turnSQLIntoArrayList($sqlQuery, "PageRaterList");
	}

	protected function turnSQLIntoArrayList(SQLQuery $sqlQuery, $method = "unknown") {
		$data = $sqlQuery->execute();
		$al = new ArrayList();
		if($data) {
			foreach($data as $record) {
				$score = $record["RatingAverage"];
				$stars = $score;
				if(Config::inst()->get("PageRater", "round_rating")) {
					$stars = round($stars);
				}
				$widthOutOfOneHundredForEachStar = 100 / PageRating::get_number_of_stars();
				$percentage = round($score * $widthOutOfOneHundredForEachStar );
				$roundedPercentage = round($stars * $widthOutOfOneHundredForEachStar);
				$reversePercentage = round(100 - $percentage);
				$reverseRoundedPercentage = round(100 - $roundedPercentage);
				$starClass = PageRating::get_star_entry_code($stars);
				$page = SiteTree::get()->byId( $record["ParentID"]);
				$record = array(
					'Rating' => "Stars",
					'Method' => $method,
					'Score' => $score,
					'Stars' => $stars,
					'Percentage' => $percentage,
					'RoundedPercentage' => $percentage,
					'ReversePercentage' => $reversePercentage,
					'ReverseRoundedPercentage' => $reverseRoundedPercentage,
					'StarClass' => $starClass,
					'Page' => $page
				);
				if(isset($_GET["debug"])) {
					debug::show("
						width out of 100 for each star: ".$widthOutOfOneHundredForEachStar."<br />
						Method: ".$method."<br />
						Score: ".$score."<br />
						Stars: ".$stars."<br />
						Percentage: ".$percentage."<br />
						RoundedPercentage: ".$roundedPercentage."<br />
						ReversePercentage: ".$reversePercentage."<br />
						ReverseRoundedPercentage: ".$reverseRoundedPercentage."<br />
						StarClass: ".$starClass."<br />
						Page: ".$page->Title
					);
				}
				$al->push(new ArrayData($record));
			}
		}
		return $al;
	}

	function PageHasBeenRatedByUser() {
		return Session::get('PageRated'.$this->owner->ID);
	}

	function NumberOfPageRatings() {
		$doSet = new ArrayList();
		$sqlQuery = new SQLQuery();
		$sqlQuery->setSelect("COUNT(\"PageRating\".\"Rating\") RatingCount");
		$sqlQuery->setFrom("\"PageRating\" ");
		$sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID."");
		$sqlQuery->setOrderBy("RatingCount ASC");
		$sqlQuery->setGoupby("\"ParentID\"");
		$sqlQuery->setLimit(1);
		$data = $sqlQuery->execute();
		if($data) {
			foreach($data as $record) {
				return $record["RatingCount"];
			}
		}
		return 0;
	}

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		if(Config::inst()->get("PageRater", "add_default_rating")) {
			$pages = SiteTree::get()
				->where( "\"PageRating\".\"ID\" IS NULL")
				->leftJoin("PageRating", "\"PageRating\".\"ParentID\" = \"SiteTree\".\"ID\"");
			if($pages) {
				foreach($pages as $page) {
					$count = 0;
					$max = PageRating::get_number_of_stars();
					$goingBackTo = ($max / rand(1, $max)) - 1;
					$stepsBack = $max - $goingBackTo;
					$ratings = Config::inst()->get("PageRater", "number_of_default_records_to_be_added") / $stepsBack;
					for($i = 1; $i <= $ratings; $i++) {
						for($j = $max; $j > $goingBackTo; $j--) {
							$PageRating = new PageRating();
							$PageRating->Rating = round(rand(1, $j));
							$PageRating->IsDefault = 1;
							$PageRating->ParentID = $page->ID;
							$PageRating->write();
							$count++;
						}
					}
					DB::alteration_message("Created Initial Ratings for Page with title ".$page->Title.". Ratings created: $count","created");
				}
			}
		}
	}

	/**
	 * return the average rating...
	 * @return Double
	 */
	 function getStarRating() {
		$ratings = $this->PageRatingResults();
		$rating = 0;
		if($ratings->Count() == 1) {
			foreach($ratings as $ratingItem) {
				$rating = $ratingItem->Stars;
			}
		}
		return $rating;
	}

}

class PageRater_Controller extends Extension {

	private static $field_title = "Click on any star to rate:";

	private static $field_right_title = "On a scale from 1 to 5, with 5 being the best";

	private static $show_average_rating_in_rating_field = false;

	private static $allowed_actions = array(
		"PageRatingForm",
		"dopagerating",
		"removedefaultpageratings",
		"removeallpageratings"
	);

	function rateagain () {
		Session::set('PageRated'.$this->owner->dataRecord->ID, false);
		Session::clear('PageRated'.$this->owner->dataRecord->ID);
		return array();
	}

	function PageRatingForm() {
		Requirements::themedCSS('PageRater', "pagerater");
		if($this->owner->PageHasBeenRatedByUser()) {
			$ratingField = new LiteralField("Rating", $this->owner->renderWith("PageRaterAjaxReturn"));
			$actions = new FieldList();
		}
		else {
			if(Config::inst()->get("PageRater_Controller", "show_average_rating_in_rating_field")) {
				$defaultStart = $this->owner->getStarRating();
			}
			else {
				$defaultStart = 0;
			}
			$ratingField = new PageRaterStarField(
				'Rating',
				Config::inst()->get("PageRater_Controller", "field_title"),
				$defaultStart,
				PageRating::get_number_of_stars()
			);
			$ratingField->setRightTitle(Config::inst()->get("PageRater_Controller", "field_right_title"));
			$actions = new FieldList(new FormAction('dopagerating', 'Submit'));
		}
		$fields = new FieldList(
			$ratingField,
			new HiddenField('ParentID', "ParentID", $this->owner->dataRecord->ID)
		);
		return new Form($this->owner, 'PageRatingForm', $fields, $actions);
	}

	function dopagerating($data, $form) {
		$data = Convert::raw2sql($data);
		$PageRating = new PageRating();
		$form->saveInto($PageRating);
		$PageRating->write();
		Session::set('PageRated'.$this->owner->dataRecord->ID, intval($data["Rating"]));
		if(Director::is_ajax()) {
			return $this->owner->renderWith("PageRaterAjaxReturn");
		}
		else {
			$this->redirectBack();
		}
	}


	function removedefaultpageratings() {
		if(Permission::check("ADMIN")) {
			DB::query("DELETE FROM PageRating WHERE IsDefault = 1;");
			debug::show("removed all default ratings for all pages");
		}
		else {
			Security::permissionFailure($this->owner, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
		}
	}

	function removeallpageratings() {
		if(Permission::check("ADMIN")) {
			DB::query("DELETE FROM PageRating;");
			debug::show("removed all ratings for all pages");
		}
		else {
			Security::permissionFailure($this->owner, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
		}
	}


}
