<?php

/**
 *@author nicolaas [at] sunnysideup . co . nz
 *
 **/

class PageRating extends DataObject
{

    /**
     * @var bool
     */
    private static $admin_can_create = true;

    /**
     * @var bool
     */
    private static $admin_can_edit = true;

    /**
     * @var bool
     */
    private static $admin_can_delete = true;

    /**
     * @var boolean
     */
    private static $round_rating = true;

    /**
     * @var array
     */
    private static $stars = array(
        '1' => array("Code" => 'OneStar', "Title" => "One Star"),
        '2' => array("Code" => 'TwoStar', "Title" => "Two Stars"),
        '3' => array("Code" => 'ThreeStar', "Title" => "Three Stars"),
        '4' => array("Code" => 'FourStar', "Title" => "Four Stars"),
        '5' => array("Code" => 'FiveStar', "Title" => "Five Stars")
    );

    /**
     *
     * @return array
     */
    protected static function get_stars()
    {
        return Config::inst()->get("PageRating", "stars");
    }

    /**
     * returns something like OneStar
     * @param int $value
     *
     * @return string
     */
    public static function get_star_entry_code($value)
    {
        $stars = self::get_stars();
        if (isset($stars[$value]["Code"])) {
            return $stars[$value]["Code"];
        }
        return "N/A";
    }

    /**
     * returns something like One Star
     * @param int $value
     *
     * @return string
     */
    public static function get_star_entry_name($value)
    {
        $stars = self::get_stars();
        if (isset($stars[$value]["Title"])) {
            return $stars[$value]["Title"];
        }
        return "N/A";
    }

    /**
     * returns something like
     *
     *     1 => One Star,
     *     2 => Two Star
     *
     * @return array
     */
    public static function get_star_dropdowndown()
    {
        $stars = self::get_stars();
        $newArray = array();
        if (count($stars)) {
            foreach ($stars as $key => $star) {
                $newArray[$key] = $star["Title"];
            }
        }
        return $newArray;
    }

    /**
     * return int
     */
    public static function get_number_of_stars()
    {
        return count(self::get_stars());
    }

    private static $_star_details_as_array_data = array();

    /**
     *
     *
     * @return ArrayData
     */
    public static function get_star_details_as_array_data($score, $parentID, $method = "unkown")
    {
        $key = $score."_".$parentID."_".$method;
        if (! isset(self::$_star_details_as_array_data[$key])) {
            $stars = $score;
            if (Config::inst()->get("PageRating", "round_rating")) {
                $stars = round($stars);
            }
            $widthOutOfOneHundredForEachStar = 100 / PageRating::get_number_of_stars();
            $percentage = round($score * $widthOutOfOneHundredForEachStar);
            $roundedPercentage = round($stars * $widthOutOfOneHundredForEachStar);
            $reversePercentage = round(100 - $percentage);
            $reverseRoundedPercentage = round(100 - $roundedPercentage);
            $starClass = PageRating::get_star_entry_code($stars);
            $page = SiteTree::get()->byId($parentID);
            self::$_star_details_as_array_data[$key] = ArrayData::create(
                array(
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
                )
            );
        }
        return self::$_star_details_as_array_data[$key];
    }

    private static $db = array(
        "Rating" => "Int",
        "Name" => "Varchar(100)",
        "Title" => "Varchar(100)",
        "Comment" => "Text",
        "IsApproved" => "Boolean",
        "IsDefault" => "Boolean"
    );

    private static $has_one = array(
        "Parent" => "Page"
    );

    private static $summary_fields = array(
        "Created" => "When",
        "Rating" => "Score",
        "Parent.Title" => "Page",
        "Comment" => "Comment",
        "IsApproved.Nice" => "Approved"
    );

    private static $field_labels = array(
        "Rating" => "Score",
        "Parent.Title" => "Page",
        "IsDefault" => "Default Entry Only",
        "Parent" => "Rating for",
        "IsApproved" => "Approved"
    );

    private static $casting = array(
        'Method' => "Varchar",
        'Stars' => "Float",
        'Percentage' => "Float",
        'RoundedPercentage' => "Int",
        'ReversePercentage' => "Float",
        'ReverseRoundedPercentage' => "Int",
        'StarClass' => "Varchar"
    );

    private static $default_sort = "Created DESC";

    private static $singular_name = 'Page Rating';

    private static $plural_name = 'Page Ratings';


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $labels = $this->FieldLabels();
        $fields->replaceField("Rating", OptionSetField::create("Rating", $labels["Rating"], self::get_star_dropdowndown()));
        //$fields->removeFieldFromTab("Root.Main", "Comment");

        if ($this->ParentID && $this->Parent() && $this->Parent()->exists()) {
            $fields->addFieldToTab("Root.Main", $readonlyField = ReadonlyField::create("ParentDescription", $labels["Parent"], "<p><a href=\"".$this->Parent()->CMSEditLink()."\">".$this->Parent()->Title."</a></p>"));
            $readonlyField->dontEscape = true;
            $fields->removeFieldFromTab("Root.Main", "ParentID");
        } else {
            $fields->replaceField(
                "ParentID",
                TreeDropdownField::create(
                    'ParentID',
                    'Parent',
                    'SiteTree'
                )
            );
        }
        if (Config::inst()->get("PageRaterStarField", "allow_name")) {
            //do nothing
        } else {
            $fields->removeFieldFromTab("Root.Main", "Name");
        }
        if (Config::inst()->get("PageRaterStarField", "allow_title")) {
            //do nothing
        } else {
            $fields->removeFieldFromTab("Root.Main", "Title");
        }
        if (Config::inst()->get("PageRaterStarField", "allow_comments")) {
            //do nothing
        } else {
            $fields->removeFieldFromTab("Root.Main", "Comment");
        }
        if (class_exists('DataObjectOneFieldUpdateController')) {
            $linkForApproval = DataObjectOneFieldUpdateController::popup_link(
                'PageRating',
                'IsApproved',
                "IsApproved = 0",
                '',
                'Mass Approve'
            );
            $linkForDisapproval = DataObjectOneFieldUpdateController::popup_link(
                'PageRating',
                'IsApproved',
                "IsApproved = 1",
                '',
                'Mass Disapprove'
            );
            $fields->addFieldToTab(
                'Root.Batch',
                new LiteralField(
                    'QuickActions',
                    '<p class="message good">'
                        ._t('ProductVariation.QUICK_UPDATE', 'Quick update')
                        .': '
                        ."<span class=\"action ss-ui-button\">$linkForApproval</span> "
                        ."<span class=\"action ss-ui-button\">$linkForDisapproval</span>"
                        .'</p>'
                ),
                'IsDefault'
            );
        }

        $fields->makeFieldReadonly("IsDefault");
        return $fields;
    }

    /**
     * updates Page Rating for a Page and saves it with the Page.
     * Updates SiteTree.PageRating field ...
     *
     * if $siteTreeID is 0 then ALL pages are updated ...
     *
     * @param  integer $siteTreeID
     */
    public static function update_ratings($siteTreeID = 0)
    {
        if ($siteTreeID) {
            $where = "PageRating.ParentID = ".$siteTreeID;
        } else {
            $where = "PageRating.ParentID > 0";
        }
        DB::query("DELETE FROM PageRating_TEMP;");
        DB::query(
            "
            INSERT INTO PageRating_TEMP
            SELECT ParentID, (ROUND(AVG(PageRating.Rating) * 100))
            FROM PageRating
            WHERE $where
            GROUP BY PageRating.ParentID;
            "
        );
        DB::query("
            UPDATE SiteTree
                INNER JOIN PageRating_TEMP ON SiteTree.ID = PageRating_TEMP.ParentID
            SET SiteTree.PageRating = (PageRating_TEMP.Rating / 100);
        ");
        DB::query("
            UPDATE SiteTree_Live
                INNER JOIN PageRating_TEMP ON SiteTree_Live.ID = PageRating_TEMP.ParentID
            SET SiteTree_Live.PageRating = (PageRating_TEMP.Rating / 100);
        ");
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->ParentID) {
            self::update_ratings($this->ParentID);
        }
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        DB::query("DROP TABLE IF EXISTS PageRating_TEMP;");
        DB::query("CREATE TABLE PageRating_TEMP (ParentID INTEGER(11), Rating INTEGER);");
        DB::query("ALTER TABLE \"PageRating_TEMP\" ADD INDEX ( \"ParentID\" ) ");
        DB::query("ALTER TABLE \"PageRating_TEMP\" ADD INDEX ( \"Rating\" ) ");
        DB::alteration_message("create PageRating_TEMP", "created");
    }

    public function canCreate($member = null)
    {
        if ($this->Config()->get("admin_can_create")) {
            return parent::canCreate($member);
        }
        return false;
    }

    public function canDelete($member = null)
    {
        if ($this->Config()->get("admin_can_delete")) {
            return parent::canDelete($member);
        }
        return false;
    }

    public function canEdit($member = null)
    {
        if ($this->Config()->get("admin_can_edit")) {
            return parent::canEdit($member);
        }
        return false;
    }

    /**
     * @alias
     *
     * @return string
     */
    public function Method()
    {
        return $this->getMethod();
    }

    /**
     * casted variable
     *
     * @return string
     */
    public function getMethod()
    {
        $arrayData = self::get_star_details_as_array_data($this->Rating, $this->ParentID);
        return $arrayData->Method;
    }

    /**
     * @alias
     *
     * @return int | float
     */
    public function Stars()
    {
        return $this->getStars();
    }

    /**
     * casted variable
     *
     * @return int | float
     */
    public function getStars()
    {
        $arrayData = self::get_star_details_as_array_data($this->Rating, $this->ParentID);
        return $arrayData->Stars;
    }

    /**
     * @alias
     *
     * @return string
     */
    public function Percentage()
    {
        return $this->getPercentage();
    }

    /**
     * casted variable
     *
     * @return float
     */
    public function getPercentage()
    {
        $arrayData = self::get_star_details_as_array_data($this->Rating, $this->ParentID);
        return $arrayData->Percentage;
    }

    /**
     * @alias
     *
     * @return int
     */
    public function RoundedPercentage()
    {
        return $this->getRoundedPercentage();
    }

    /**
     * casted variable
     *
     * @return int
     */
    public function getRoundedPercentage()
    {
        $arrayData = self::get_star_details_as_array_data($this->Rating, $this->ParentID);
        return $arrayData->RoundedPercentage;
    }

    /**
     * casted alias
     *
     * @return string
     */
    public function ReversePercentage()
    {
        return $this->getReversePercentage();
    }

    /**
     * casted variable
     *
     * @return float
     */
    public function getReversePercentage()
    {
        $arrayData = self::get_star_details_as_array_data($this->Rating, $this->ParentID);
        return $arrayDat->ReversePercentage;
    }

    /**
     * @alias
     *
     * @return int
     */
    public function ReverseRoundedPercentage()
    {
        return $this->getReverseRoundedPercentage();
    }

    /**
     * casted variable
     *
     * @return int
     */
    public function getReverseRoundedPercentage()
    {
        $arrayData = self::get_star_details_as_array_data($this->Rating, $this->ParentID);
        return $arrayData->ReverseRoundedPercentage;
    }

    /**
     * @alias
     *
     * @return string
     */
    public function StarClass()
    {
        return $this->getStarClass();
    }

    /**
     * casted variable
     *
     * @return string
     */
    public function getStarClass()
    {
        $arrayData = self::get_star_details_as_array_data($this->Rating, $this->ParentID);
        return $arrayData->StarClass;
    }
}
