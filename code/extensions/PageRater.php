<?php

/**
 *@author nicolaas [at] sunnysideup up .co .nz
 *
 *
 **/

class PageRater extends DataObjectDecorator {

	public function extraStatics() {
		return array (
			'db' => array(
				'PageRating' => 'Double'
			),
			'indexes' => array(
				'PageRating' => true
			)
		);
	}


	protected static $add_default_rating = false;
		static function set_add_default_rating($v) {self::$add_default_rating = $v;}
		static function get_add_default_rating() {return self::$add_default_rating;}

	protected static $round_rating = true;
		static function set_round_rating($v) {self::$round_rating = $v;}
		static function get_round_rating() {return self::$round_rating;}

	protected static $number_of_default_records_to_be_added = 5;
		static function set_number_of_default_records_to_be_added($v) {self::$number_of_default_records_to_be_added = $v;}
		static function get_number_of_default_records_to_be_added() {return self::$number_of_default_records_to_be_added;}

	function PageRatingResults() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$doSet = new DataObjectSet();
    $sqlQuery = new SQLQuery(
			$select = "AVG({$bt}PageRating{$bt}.{$bt}Rating{$bt}) RatingAverage, ParentID",
			$from = " {$bt}PageRating{$bt} ",
			$where = "{$bt}ParentID{$bt} = ".$this->owner->ID."",
			$orderby = "RatingAverage DESC",
			$groupby = "{$bt}ParentID{$bt}",
			$having = "",
			$limit = "1"
		);
		return $this->turnSQLIntoDoset($sqlQuery, "PageRatingResults");
	}

	function CurrentUserRating() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$doSet = new DataObjectSet();
    $sqlQuery = new SQLQuery(
			$select = "AVG({$bt}PageRating{$bt}.{$bt}Rating{$bt}) RatingAverage, ParentID",
			$from = " {$bt}PageRating{$bt} ",
			$where = "{$bt}ParentID{$bt} = ".$this->owner->ID." AND {$bt}Rating{$bt} = '".Session::get('PageRated'.$this->owner->ID)."'",
			$orderby = "RatingAverage DESC",
			$groupby = "{$bt}ParentID{$bt}",
			$having = "",
			$limit = "1"
		);
		return $this->turnSQLIntoDoset($sqlQuery, "CurrentUserRating");
	}

	function PageRaterList(){
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$doSet = new DataObjectSet();
    $sqlQuery = new SQLQuery(
			$select = "AVG({$bt}PageRating{$bt}.{$bt}Rating{$bt}) RatingAverage, ParentID",
			$from = " {$bt}PageRating{$bt}, {$bt}SiteTree{$bt}  ",
			$where = "{$bt}ParentID{$bt} = {$bt}SiteTree{$bt}.{$bt}ID{$bt}",
			$orderby = "RatingAverage DESC",
			$groupby = "{$bt}ParentID{$bt}"
		);
		return $this->turnSQLIntoDoset($sqlQuery, "PageRaterList");
	}

	protected function turnSQLIntoDoset(SQLQuery $sqlQuery, $method = "unknown") {
		$data = $sqlQuery->execute();
		$doSet = new DataObjectSet();
		if($data) {
			foreach($data as $record) {
				$score = $record["RatingAverage"];
				$stars = ($score);
				if(PageRater::get_round_rating()) {
					$stars = round($stars);
				}
				$widthOutOfOneHundredForEachStar = 100 / PageRating::get_number_of_stars();
				$percentage = round($score * $widthOutOfOneHundredForEachStar );
				$roundedPercentage = round($stars * $widthOutOfOneHundredForEachStar);
				$reversePercentage = round(100 - $percentage);
				$reverseRoundedPercentage = round(100 - $roundedPercentage);
				$starClass = PageRating::get_star_entry_code($stars);
				$page = DataObject::get_by_id("SiteTree", $record["ParentID"]);
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
				$doSet->push(new ArrayData($record));
			}
			Requirements::themedCSS("PageRater");
		}
		return $doSet;
	}

	function PageHasBeenRatedByUser() {
		return Session::get('PageRated'.$this->owner->ID);
	}

	function NumberOfPageRatings() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$doSet = new DataObjectSet();
    $sqlQuery = new SQLQuery(
			$select = "COUNT({$bt}PageRating{$bt}.{$bt}Rating{$bt}) RatingCount",
			$from = " {$bt}PageRating{$bt} ",
			$where = "{$bt}ParentID{$bt} = ".$this->owner->ID."",
			$orderby = "RatingCount",
			$groupby = "{$bt}ParentID{$bt}",
			$having = "",
			$limit = "1"
		);
		$data = $sqlQuery->execute();
		if($data) {
			foreach($data as $record) {
				return $record["RatingCount"];
			}
		}
		return 0;
	}

	function requireDefaultRecords() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		parent::requireDefaultRecords();
		if(self::get_add_default_rating()) {
			$pages = DataObject::get(
				$className = "SiteTree",
				$where = "{$bt}PageRating{$bt}.{$bt}ID{$bt} IS NULL",
				$sort = "",
				$join = "LEFT JOIN {$bt}PageRating{$bt} ON {$bt}PageRating{$bt}.{$bt}ParentID{$bt} = {$bt}SiteTree{$bt}.{$bt}ID{$bt}"
			);
			if($pages) {
				foreach($pages as $page) {
					$count = 0;
					$max = PageRating::get_number_of_stars();
					$goingBackTo = ($max / rand(1, $max)) - 1;
					$stepsBack = $max - $goingBackTo;
					$ratings = PageRater::get_number_of_default_records_to_be_added() / $stepsBack;
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

   function getStarRating(){
		$ratings = $this->PageRatingResults();
		$rating = 0;
		if($ratings->Count() > 0){
			foreach($ratings as $ratingItem){
				$rating = $ratingItem->Stars;
			}
		}
		return $rating;
	}

}

class PageRater_Controller extends Extension {

	protected static $field_title = "Click on any star to rate:";
		static function set_field_title($v) {self::$field_title = $v;}
		static function get_field_title() {return self::$field_title;}

	protected static $field_right_title = "On a scale from 1 to 5, with 5 being the best";
		static function set_field_right_title($v){self::$field_right_title = $v;}
		static function get_field_right_title(){return self::$field_right_title;}

	protected static $show_average_rating_in_rating_field = false;
		static function set_show_average_rating_in_rating_field($v){self::$show_average_rating_in_rating_field = $v;}
		static function get_show_average_rating_in_rating_field(){return self::$show_average_rating_in_rating_field;}

	static $allowed_actions = array("PageRatingForm", "dopagerating", "removedefaultpageratings", "removeallpageratings" );

	function rateagain (){
		Session::set('PageRated'.$this->owner->dataRecord->ID, false);
		Session::clear('PageRated'.$this->owner->dataRecord->ID);
		return array();
	}

	function PageRatingForm() {
		if($this->owner->PageHasBeenRatedByUser()) {
			return false;
		}

		if(self::get_show_average_rating_in_rating_field()) {
			$defaultStart = $this->owner->getStarRating();
		}
		else {
			$defaultStart = 0;
		}
		$ratingField = new PageRaterStarField('Rating', PageRater_Controller::get_field_title(), $defaultStart, PageRating::get_number_of_stars());
		$ratingField->setRightTitle(PageRater_Controller::get_field_right_title());
		$fields = new FieldSet(
			$ratingField,
			new HiddenField('ParentID', "ParentID", $this->owner->dataRecord->ID)
		);
		$actions = new FieldSet(new FormAction('dopagerating', 'Submit'));
		return new Form($this->owner, 'PageRatingForm', $fields, $actions);
	}

	function dopagerating($data, $form) {
		$data = Convert::raw2sql($data);
		$PageRating = new PageRating();
		$form->saveInto($PageRating);
		$PageRating->write();
		Session::set('PageRated'.$this->owner->dataRecord->ID, intval($data["Rating"]));
		if($this->owner->isAjax()) {
			return $this->owner->renderWith("PageRaterAjaxReturn");
		}
		else {
			Director::redirectBack();
		}
	}


	function removedefaultpageratings() {
		if(Permission::check("ADMIN")) {
			DB::query("DELETE FROM PageRating WHERE IsDefault = 1;");
			debug::show("removed all ratings for all pages");
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
