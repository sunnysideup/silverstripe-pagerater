<?php
/**
 *@ author nicolaas[at] sunny side up .co .nz
 *
 *
 *
 **/
class PageRatingAdmin extends ModelAdmin {

	public static $managed_models = array(
		'PageRating'
	);

	static $url_segment = 'page-rating';

	static $menu_title = 'Ratings';

}
