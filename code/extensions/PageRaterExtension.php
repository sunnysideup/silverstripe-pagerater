<?php

/**
 *@author nicolaas [at] sunnysideup up .co .nz
 * <% loop $PageRatings %>
 *
 * <% end_loop %>
 *
 **/

class PageRaterExtension extends DataExtension
{
    private static $has_many = array(
        'PageRatings' => 'PageRating'
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

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->PageRatings() && $this->owner->PageRatings()->count()) {
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
    }


    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $step = 50;
        if (Config::inst()->get("PageRaterExtension", "add_default_rating")) {
            for ($i = 0; $i < 1000000; $i = $i + $step) {
                $pages = SiteTree::get()
                    ->leftJoin("PageRating", "\"PageRating\".\"ParentID\" = \"SiteTree\".\"ID\"")
                    ->where("\"PageRating\".\"ID\" IS NULL")
                    ->limit($step, $i);

                if ($pages->count()) {
                    foreach ($pages as $page) {
                        $count = 0;
                        $max = PageRating::get_number_of_stars();
                        $goingBackTo = ($max / rand(1, $max)) - 1;
                        $stepsBack = $max - $goingBackTo;
                        $ratings = Config::inst()->get("PageRaterExtension", "number_of_default_records_to_be_added") / $stepsBack;
                        for ($i = 1; $i <= $ratings; $i++) {
                            for ($j = $max; $j > $goingBackTo; $j--) {
                                $PageRating = new PageRating();
                                $PageRating->Rating = round(rand(1, $j));
                                $PageRating->IsDefault = 1;
                                $PageRating->ParentID = $page->ID;
                                $PageRating->write();
                                $count++;
                            }
                        }
                        DB::alteration_message("Created Initial Ratings for Page with title ".$page->Title.". Ratings created: $count", "created");
                    }
                } else {
                    $i = 1000000;
                }
            }
        }
    }


    /**
     * return the average rating...
     * @return Double
     */
    public function StarRating()
    {
        return $this->getStarRating();
    }
    /**
     *
     * @param string $character optional character (e.g. ★,
     *                           if supplied and the number of stars is 3
     *                           then it will return ★★★)
     * @return int|string
     */
    public function getStarRating($character = '')
    {
        $ratings = $this->owner->PageRatingResults();
        $rating = 0;
        if ($ratings->Count() == 1) {
            foreach ($ratings as $ratingItem) {
                $rating = $ratingItem->Stars;
            }
        }
        if ($character && $rating) {
            return str_repeat($character, $rating);
        } else {
            return $rating;
        }
    }
    /**
     *
     * @return int
     */
    public function NumberOfPageRatings()
    {
        $doSet = new ArrayList();
        $sqlQuery = new SQLQuery();
        $sqlQuery->setSelect("COUNT(\"PageRating\".\"Rating\") RatingCount");
        $sqlQuery->setFrom("\"PageRating\" ");
        if ($this->onlyShowApprovedPageRatings()) {
            $sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID." AND \"PageRating\".\"IsApproved\" = 1");
        } else {
            $sqlQuery->setWhere("\"ParentID\" = ".$this->owner->ID."");
        }
        $sqlQuery->setOrderBy("RatingCount ASC");
        $sqlQuery->setGroupBy("\"ParentID\"");
        $sqlQuery->setLimit(1);
        $data = $sqlQuery->execute();
        if ($data) {
            foreach ($data as $record) {
                return $record["RatingCount"];
            }
        }
        return 0;
    }


    /**
     * rating for this page ...
     * @return ArrayList
     */
    public function PageRatingResults()
    {
        $sqlQuery = new SQLQuery();
        $sqlQuery->setSelect("AVG(\"PageRating\".\"Rating\") RatingAverage, ParentID");
        $sqlQuery->setFrom("\"PageRating\" ");
        if ($this->onlyShowApprovedPageRatings()) {
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
    public function CurrentUserRating()
    {
        $sqlQuery = new SQLQuery();
        $sqlQuery->setSelect("AVG(\"PageRating\".\"Rating\") RatingAverage, ParentID");
        $sqlQuery->setFrom("\"PageRating\" ");
        if ($this->onlyShowApprovedPageRatings()) {
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
     * @param $data $sqlQuery | DataList
     * @param string $method
     *
     * @return ArrayList
     */
    public function turnPageRaterSQLIntoArrayList($data, $method = "unknown")
    {
        if ($data instanceof SQLQuery) {
            $data = $data->execute();
        }
        $al = new ArrayList();
        if ($data) {
            foreach ($data as $record) {
                if ($record instanceof PageRating) {
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

    public function onlyShowApprovedPageRatings()
    {
        return Config::inst()->get("PageRaterExtension_Controller", "only_show_approved");
    }
}
