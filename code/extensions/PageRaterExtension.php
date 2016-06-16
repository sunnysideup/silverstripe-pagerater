<?php

/**
 *@author nicolaas [at] sunnysideup up .co .nz
 * <% looo $PageRatings %>
 *
 * <% end_loop %>
 *
 **/

class PageRaterExtension extends DataExtension {

    private static $db = array(
        'PageRating' => 'Double'
    );

    private static $has_many = array(
        'PageRatings' => 'PageRating'
    );

    private static $indexes = array(
        'PageRating' => true
    );

    /**
     * add the default rating to each page ...
     * @var boolean
     */
    private static $add_default_rating = false;



    /**
     * @var boolean
     */
    private static $number_of_default_records_to_be_added = 5;

    function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab(
            "Root.Ratings",
            GridField::create(
                "PageRatings",
                Injector::inst()->get("PageRating")->plural_name(),
                $this->owner->PageRatings(),
                GridFieldConfig_RecordViewer::create()
            )
        );
    }


    function requireDefaultRecords() {
        parent::requireDefaultRecords();
        $step = 50;
        for($i = 0; $i < 1000000; $i = $i + $step) {
            if(Config::inst()->get("PageRaterExtension", "add_default_rating")) {
                $pages = SiteTree::get()
                    ->leftJoin("PageRating", "\"PageRating\".\"ParentID\" = \"SiteTree\".\"ID\"")
                    ->where("\"PageRating\".\"ID\" IS NULL")
                    ->limit($step, $i);

                if($pages->count()) {
                    foreach($pages as $page) {
                        $count = 0;
                        $max = PageRating::get_number_of_stars();
                        $goingBackTo = ($max / rand(1, $max)) - 1;
                        $stepsBack = $max - $goingBackTo;
                        $ratings = Config::inst()->get("PageRaterExtension", "number_of_default_records_to_be_added") / $stepsBack;
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
                else {
                    $i = 9999999;
                }
            }
        }
    }


}

class PageRaterExtension_Controller extends Extension {


    /**
     * @var string
     */
    private static $field_title = "Click on any star to rate:";

    /**
     * @var string
     */
    private static $field_right_title = "On a scale from 1 to 5, with 5 being the best";

    /**
     * @var boolean
     */
    private static $show_average_rating_in_rating_field = false;

    /**
     * @var boolean
     */
    private static $only_show_approved = false;

    private static $allowed_actions = array(
        "PageRatingForm",
        "rateagain",
        "dopagerating",
        "removedefaultpageratings",
        "removeallpageratings"
    );

    /**
     * action to allow use to rate again...
     */
    function rateagain($request) {
        $id = intval(Session::get('PageRated'.$this->owner->dataRecord->ID))-0;
        $pageRating = PageRating::get()->byID($id);
        if($pageRating) {
            $pageRating->delete();
        }
        Session::set('PageRated'.$this->owner->dataRecord->ID, false);
        Session::clear('PageRated'.$this->owner->dataRecord->ID);
        return $this->owner->redirect($this->owner->Link());
    }

    /**
     * @return Form
     */
    function PageRatingForm() {
        Requirements::themedCSS('PageRater', "pagerater");
        if($this->owner->PageHasBeenRatedByUser()) {
            $ratingField = LiteralField::create("RatingFor".$this->owner->dataRecord->ID, $this->owner->renderWith("PageRaterAjaxReturn"));
            $actions = FieldList::create();
        }
        else {
            if(Config::inst()->get("PageRaterExtension_Controller", "show_average_rating_in_rating_field")) {
                $defaultStart = $this->owner->getStarRating();
            }
            else {
                $defaultStart = 0;
            }
            $ratingField = PageRaterStarField::create(
                'RatingFor'.$this->owner->dataRecord->ID,
                Config::inst()->get("PageRaterExtension_Controller", "field_title"),
                $defaultStart,
                PageRating::get_number_of_stars()
            );
            $ratingField->setRightTitle(Config::inst()->get("PageRaterExtension_Controller", "field_right_title"));
            $actions = FieldList::create(FormAction::create('dopagerating', 'Submit'));
        }
        $fields = FieldList::create(
            $ratingField,
            HiddenField::create('ParentID', "ParentID", $this->owner->dataRecord->ID)
        );

        return Form::create($this->owner, 'PageRatingForm', $fields, $actions);
    }

    /**
     * action Page Rating Form
     */
    function dopagerating($data, $form) {
        $id = $this->owner->dataRecord->ID;
        $fieldName = "Form_PageRatingForm_RatingFor".$id;
        $data = Convert::raw2sql($data);
        $pageRating = PageRating::create();
        $form->saveInto($pageRating);
        $pageRating->ParentID = $this->owner->dataRecord->ID;
        if(isset($data[$fieldName])) {
            $pageRating->Rating = floatval($data[$fieldName]);
        }
        if(isset($data[$fieldName."_Comment"])) {
            $pageRating->Comment = Convert::raw2sql($data[$fieldName."_Comment"]);
        }
        if(isset($data[$fieldName."_Name"])) {
            $pageRating->Name = Convert::raw2sql($data[$fieldName."_Name"]);
        }
        if(isset($data[$fieldName."_Title"])) {
            $pageRating->Title = Convert::raw2sql($data[$fieldName."_Title"]);
        }
        $pageRating->write();
        Session::set('PageRated'.$this->owner->dataRecord->ID, $pageRating->ID);
        if(Director::is_ajax()) {
            return $this->owner->renderWith("PageRaterAjaxReturn");
        }
        else {
            $this->owner->redirectBack();
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





    /**
     * rating for this page ...
     * @return ArrayList
     */
    function PageRatingResults() {
        $sqlQuery = new SQLQuery();
        $sqlQuery->setSelect("AVG(\"PageRating\".\"Rating\") RatingAverage, ParentID");
        $sqlQuery->setFrom("\"PageRating\" ");
        if($this->onlyShowApprovedPageRatings()) {
            $sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID." AND \"PageRating\".\"IsApproved\" = 1");
        } else {
            $sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID."");
        }
        $sqlQuery->setOrderBy("RatingAverage DESC");
        $sqlQuery->setGroupby("\"ParentID\"");
        $sqlQuery->setLimit(1);
        return $this->turnPageRaterSQLIntoArrayList($sqlQuery, "PageRatingResults");
    }

    /**
     * rating of this page by this user ...
     * @return ArrayList
     */
    function CurrentUserRating() {
        $sqlQuery = new SQLQuery();
        $sqlQuery->setSelect("AVG(\"PageRating\".\"Rating\") RatingAverage, ParentID");
        $sqlQuery->setFrom("\"PageRating\" ");
        if($this->onlyShowApprovedPageRatings()) {
            $sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID." AND \"PageRating\".\"ID\" = '".Session::get('PageRated'.$this->owner->ID)."' AND \"PageRating\".\"IsApproved\" = 1");
        } else {
            $sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID." AND \"PageRating\".\"ID\" = '".Session::get('PageRated'.$this->owner->ID)."'");
        }

        $sqlQuery->setOrderBy("RatingAverage DESC");
        $sqlQuery->setGroupby("\"ParentID\"");
        $sqlQuery->setLimit(1);
        return $this->turnPageRaterSQLIntoArrayList($sqlQuery, "CurrentUserRating");
    }

    /**
     * list of all rated pages ...
     * @return ArrayList
     */
    function PageRaterListOfAllForPage() {
        if($this->onlyShowApprovedPageRatings()) {
            return $this->turnPageRaterSQLIntoArrayList(
                $this->owner->PageRatings()->filter(array("IsApproved" => 1)), "PageRaterListOfAllForPage");
        } else {
            return $this->turnPageRaterSQLIntoArrayList(
                $this->owner->PageRatings()->filter(array("IsApproved" => 0)),
                "PageRaterListOfAllForPage"
            );
        }
    }

    function PageRaterListAll(){
        $sqlQuery = new SQLQuery();
        $sqlQuery->setSelect("\"PageRating\".\"Rating\" AS RatingAverage, \"PageRating\".\"ParentID\"");
        if($this->onlyShowApprovedPageRatings()) {
            $sqlQuery->setWhere("\"PageRating\".\"IsApproved\" = 1");
        }
        $sqlQuery->setFrom(" \"PageRating\"");
        $sqlQuery->addInnerJoin("SiteTree", " \"PageRating\".\"ParentID\" = \"SiteTree\".\"ID\"");
        $sqlQuery->setOrderBy("RatingAverage DESC");
        $sqlQuery->setGroupby("\"SiteTree\".\"ParentID\"");
        return $this->turnPageRaterSQLIntoArrayList($sqlQuery, "PageRaterList");
    }

    /**
     * @param $data $sqlQuery | DataList
     * @param string $method
     *
     * @return ArrayList
     */
    protected function turnPageRaterSQLIntoArrayList($data, $method = "unknown") {
        if($data instanceof SQLQuery) {
            $data = $data->execute();
        }
        $al = new ArrayList();
        if($data) {
            foreach($data as $record) {
                if($record instanceof PageRating) {
                    $record->Method = $method;
                    //do nothing
                } else {
                    $score = $record["RatingAverage"];
                    $parentID = $record["ParentID"];
                    $record = PageRating::get_star_details_as_array_data($score, $parentID, $method);
                }
                $al->push($record);
            }
        }
        return $al;
    }

    /**
     * @return boolean
     */
    function PageHasBeenRatedByUser() {
        return Session::get('PageRated'.$this->owner->ID) ? true : false;
    }

    /**
     *
     * @return int
     */
    function NumberOfPageRatings() {
        $doSet = new ArrayList();
        $sqlQuery = new SQLQuery();
        $sqlQuery->setSelect("COUNT(\"PageRating\".\"Rating\") RatingCount");
        $sqlQuery->setFrom("\"PageRating\" ");
        if($this->onlyShowApprovedPageRatings()) {
            $sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID." AND \"PageRating\".\"IsApproved\" = 1");
        } else {
            $sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID."");
        }
        $sqlQuery->setOrderBy("RatingCount ASC");
        $sqlQuery->setGroupBy("\"ParentID\"");
        $sqlQuery->setLimit(1);
        $data = $sqlQuery->execute();
        if($data) {
            foreach($data as $record) {
                return $record["RatingCount"];
            }
        }
        return 0;
    }

    protected function onlyShowApprovedPageRatings()
    {
        return Config::inst()->get("PageRaterExtension_Controller", "only_show_approved");
    }


    /**
     * return the average rating...
     * @return Double
     */
     function getStarRating() {
        $ratings = $this->owner->PageRatingResults();
        $rating = 0;
        if($ratings->Count() == 1) {
            foreach($ratings as $ratingItem) {
                $rating = $ratingItem->Stars;
            }
        }
        return $rating;
    }

}
