<?php
/**
 *@ author nicolaas[at] sunny side up .co .nz
 *
 *
 *
 **/
class PageRatingAdmin extends ModelAdmin
{
    private static $managed_models = array(
        'PageRating'
    );

    private static $url_segment = 'page-rating';

    private static $menu_title = 'Ratings';

    private static $menu_icon = 'pagerater/images/treeicons/PageRatingAdmin.png';
}
