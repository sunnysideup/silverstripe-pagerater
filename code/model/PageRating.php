<?php

/**
 *@author nicolaas [at] sunnysideup . co . nz
 *
 **/

class PageRating extends DataObject
{

    private static $stars = array(
        '1' => array("Code" => 'OneStar', "Title" => "One Star"),
        '2' => array("Code" => 'TwoStar', "Title" => "Two Stars"),
        '3' => array("Code" => 'ThreeStar', "Title" => "Three Stars"),
        '4' => array("Code" => 'FourStar', "Title" => "Four Stars"),
        '5' => array("Code" => 'FiveStar', "Title" => "Five Stars")
    );


    public static function get_star_entry_code($value)
    {
        if (isset(self::$stars[$value]["Code"])) {
            return self::$stars[$value]["Code"];
        }
        return "NA";
    }
    public static function get_star_entry_name($value)
    {
        if (isset(self::$stars[$value]["Title"])) {
            return self::$stars[$value]["Title"];
        }
        return "NA";
    }

    public static function get_star_dropdowndown()
    {
        $array = self::get_stars();
        $newArray = array();
        if (count($array)) {
            foreach ($array as $key => $star) {
                $newArray[$key] = $star["Title"];
            }
        }
        return $newArray;
    }
    public static function get_number_of_stars()
    {
        return count(self::$stars);
    }

    private static $db = array(
        "Rating" => "Int",
        "IsDefault" => "Boolean"
    );

    private static $has_one = array(
        "Parent" => "Page"
    );

    private static $summary_fields = array("Rating", "Parent.Title");

    private static $default_sort = "Created DESC";

    private static $singular_name = 'Page Rating';

    private static $plural_name = 'Page Ratings';

    public static function update_ratings($SiteTreeID = 0)
    {
        $bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
        if ($SiteTreeID) {
            $where = "PageRating.ParentID = ".$SiteTreeID;
        } else {
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
        $bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
        DB::query("DROP TABLE IF EXISTS PageRating_TEMP;");
        DB::query("CREATE TABLE PageRating_TEMP (ParentID INTEGER(11), Rating INTEGER);");
        DB::query("ALTER TABLE {$bt}PageRating_TEMP{$bt} ADD INDEX ( {$bt}ParentID{$bt} ) ");
        DB::query("ALTER TABLE {$bt}PageRating_TEMP{$bt} ADD INDEX ( {$bt}Rating{$bt} ) ");
        DB::alteration_message("create PageRating_TEMP", "created");
    }
}
