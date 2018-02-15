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
}
