<?php



class PageRaterExtension_Controller extends Extension
{


    /**
     * add the default rating to each page ...
     * @var boolean
     */
    private static $items_per_page = 8;

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
    public function rateagain($request)
    {
        $id = intval(Session::get('PageRated'.$this->owner->dataRecord->ID))-0;
        $pageRating = PageRating::get()->byID($id);
        if ($pageRating) {
            $pageRating->delete();
        }
        Session::set('PageRated'.$this->owner->dataRecord->ID, false);
        Session::clear('PageRated'.$this->owner->dataRecord->ID);
        return $this->owner->redirect($this->owner->Link());
    }

    /**
     * @return Form
     */
    public function PageRatingForm()
    {
        Requirements::themedCSS('PageRater', "pagerater");
        if ($this->owner->PageHasBeenRatedByUser()) {
            $ratingField = LiteralField::create("RatingFor".$this->owner->dataRecord->ID, $this->owner->renderWith("PageRaterAjaxReturn"));
            $actions = FieldList::create();
            $requiredFields = null;
        } else {
            if (Config::inst()->get("PageRaterExtension_Controller", "show_average_rating_in_rating_field")) {
                $defaultStart = $this->owner->getStarRating();
            } else {
                $defaultStart = 0;
            }
            $ratingField = PageRaterStarField::create(
                'RatingFor'.$this->owner->dataRecord->ID,
                Config::inst()->get("PageRaterExtension_Controller", "field_title"),
                $defaultStart,
                PageRating::get_number_of_stars()
            );
            $ratingField->setRightTitle(Config::inst()->get("PageRaterExtension_Controller", "field_right_title"));
            $requiredFields = RequiredFields::create($ratingField->getRequiredFields());
            $actions = FieldList::create(FormAction::create('dopagerating', 'Submit'));
        }
        $fields = FieldList::create(
            $ratingField,
            HiddenField::create('ParentID', "ParentID", $this->owner->dataRecord->ID)
        );

        return Form::create($this->owner, 'PageRatingForm', $fields, $actions, $requiredFields);
    }

    /**
     * action Page Rating Form
     */
    public function dopagerating($data, $form)
    {
        $id = $this->owner->dataRecord->ID;
        $fieldName = "RatingFor".$id;
        $data = Convert::raw2sql($data);
        $pageRating = PageRating::create();
        $form->saveInto($pageRating);
        $pageRating->ParentID = $this->owner->dataRecord->ID;
        if (isset($data[$fieldName])) {
            $pageRating->Rating = floatval($data[$fieldName]);
        }
        if (isset($data[$fieldName."_Comment"])) {
            $pageRating->Comment = Convert::raw2sql($data[$fieldName."_Comment"]);
        }
        if (isset($data[$fieldName."_Name"])) {
            $pageRating->Name = Convert::raw2sql($data[$fieldName."_Name"]);
        }
        if (isset($data[$fieldName."_Title"])) {
            $pageRating->Title = Convert::raw2sql($data[$fieldName."_Title"]);
        }
        $pageRating->write();
        Session::set('PageRated'.$this->owner->dataRecord->ID, $pageRating->ID);
        if (Director::is_ajax()) {
            return $this->owner->renderWith("PageRaterAjaxReturn");
        } else {
            $this->owner->redirectBack();
        }
    }


    public function removedefaultpageratings()
    {
        if (Permission::check("ADMIN")) {
            DB::query("DELETE FROM PageRating WHERE IsDefault = 1;");
            debug::show("removed all default ratings for all pages");
        } else {
            Security::permissionFailure($this->owner, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        }
    }

    public function removeallpageratings()
    {
        if (Permission::check("ADMIN")) {
            DB::query("DELETE FROM PageRating;");
            debug::show("removed all ratings for all pages");
        } else {
            Security::permissionFailure($this->owner, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        }
    }





    /**
     * list of all rated pages ...
     * @return ArrayList
     */
    public function PageRaterListOfAllForPage($paginated = false)
    {
        if ($this->owner->onlyShowApprovedPageRatings()) {
            $list = $this->owner->turnPageRaterSQLIntoArrayList(
                $this->owner->PageRatings()->filter(array("IsApproved" => 1)),
                "PageRaterListOfAllForPage"
            );
        } else {
            $list = $this->owner->turnPageRaterSQLIntoArrayList(
                $this->owner->PageRatings(),
                "PageRaterListOfAllForPage"
            );
        }
        if ($paginated) {
            $limit = Config::inst()->get('PageRaterExtension_Controller', 'items_per_page');
            if ($limit) {
                $list = PaginatedList::create($list, $this->owner->getRequest());
                $list->setPageLength($limit);
            }
        }
        return $list;
    }

    {
        $sqlQuery = new SQLQuery();
        $sqlQuery->setSelect("\"PageRating\".\"Rating\" AS RatingAverage, \"PageRating\".\"ParentID\"");
        if ($this->onlyShowApprovedPageRatings()) {
            $sqlQuery->setWhere("\"PageRating\".\"IsApproved\" = 1");
        }
        $sqlQuery->setFrom(" \"PageRating\"");
        $sqlQuery->addInnerJoin("SiteTree", " \"PageRating\".\"ParentID\" = \"SiteTree\".\"ID\"");
        $sqlQuery->setOrderBy("RatingAverage DESC");
        $sqlQuery->setGroupby("\"SiteTree\".\"ParentID\"");
        return $this->owner->turnPageRaterSQLIntoArrayList($sqlQuery, "PageRaterList");
    }

    /**
     * @return boolean
     */
    public function PageHasBeenRatedByUser()
    {
        return Session::get('PageRated'.$this->owner->ID) ? true : false;
    }



}
