<?php

/**
 *@author nicolaas [at] sunnysideup . co . nz
 *
 **/

class PageRating extends DataObject {

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
    protected static function get_stars(){
        return Config::inst()->get("PageRating", "stars");
    }

    /**
     * returns something like OneStar
     * @param int $value
     *
     * @return string
     */
    public static function get_star_entry_code($value) {
        $stars = self::get_stars();
        if(isset($stars[$value]["Code"])) {
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
    public static function get_star_entry_name($value) {
        $stars = self::get_stars();
        if(isset($stars[$value]["Title"])) {
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
    public static function get_star_dropdowndown() {
        $stars = self::get_stars();
        $newArray = array();
        if(count($stars)) {
            foreach($stars as $key => $star) {
                $newArray[$key] = $star["Title"];
            }
        }
        return $newArray;
    }

    /**
     * return int
     */
    public static function get_number_of_stars() {return count(self::get_stars());}

    private static $db = array(
         "Rating" => "Int",
         "Title" => "Varchar(100)",
         "Name" => "Varchar(100)",
         "Comment" => "Text",
         "IsDefault" => "Boolean"
    );

    private static $has_one = array(
        "Parent" => "Page"
    );

    private static $summary_fields = array(
        "Score" => "Rating",
        "Page" => "Parent.Title",
        "Comment" => "Comment"
    );

    private static $field_labels = array(
        "Rating" => "Score",
        "Parent.Title" => "Page",
        "IsDefault" => "Default Entry Only",
        "Parent" => "Rating for"
    );

    private static $default_sort = "Created DESC";

    private static $singular_name = 'Page Rating';

    private static $plural_name = 'Page Ratings';


    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $labels = $this->FieldLabels();
        $fields->replaceField("Rating", OptionSetField::create("Rating", $labels["Rating"], self::get_star_dropdowndown()));
        //$fields->removeFieldFromTab("Root.Main", "Comment");
        $fields->removeFieldFromTab("Root.Main", "ParentID");
        if($this->ParentID && $this->Parent() && $this->Parent()->exists()) {
            $fields->addFieldToTab("Root.Main", $readonlyField = ReadonlyField::create("ParentDescription", $labels["Parent"], "<p><a href=\"".$this->Parent()->CMSEditLink()."\">".$this->Parent()->Title."</a></p>"));
        }
        $readonlyField->dontEscape = true;
        $fields->makeFieldReadonly("IsDefault");
        return $fields;
    }

    public static function update_ratings($siteTreeID = 0) {
        if($siteTreeID) {
            $where = "PageRating.ParentID = ".$siteTreeID;
        }
        else {
            $where = "PageRating.ParentID > 0";
        }
        DB::query("DELETE FROM PageRating_TEMP;");
        DB::query("
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
            SET SiteTree.PageRating = (PageRating_TEMP.Rating / 100);");
        DB::query("
            UPDATE SiteTree_Live
                INNER JOIN PageRating_TEMP ON SiteTree_Live.ID = PageRating_TEMP.ParentID
            SET SiteTree_Live.PageRating = (PageRating_TEMP.Rating / 100);");
    }

    function onBeforeWrite() {
        parent::onBeforeWrite();
        if($this->ParentID) {
            self::update_ratings($this->ParentID);
        }
    }

    function requireDefaultRecords() {
        parent::requireDefaultRecords();
        DB::query("DROP TABLE IF EXISTS PageRating_TEMP;");
        DB::query("CREATE TABLE PageRating_TEMP (ParentID INTEGER(11), Rating INTEGER);");
        DB::query("ALTER TABLE \"PageRating_TEMP\" ADD INDEX ( \"ParentID\" ) ");
        DB::query("ALTER TABLE \"PageRating_TEMP\" ADD INDEX ( \"Rating\" ) ");
        DB::alteration_message("create PageRating_TEMP", "created");
    }

    function canCreate($member = null) {
        return false;
    }

    function canDelete($member = null) {
        return false;
    }

    function canEdit($member = null) {
        return false;
    }

}
