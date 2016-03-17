<?php

class PageRaterStarField extends FormField {

	protected $rating = 0;

	protected $starOptions = 5;

	private static $extra_form_selector = '';

	private static $allow_comments = false;

	/**
	 * Returns an input field, class="start field" and type="hidden" with an optional maxlength
	 */
	function __construct($name, $title = null, $value = "", $starOptions = null, $form = null) {

		if($starOptions) {
			$this->starOptions = $starOptions;
		}
		else {
			$this->starOptions = PageRating::get_number_of_stars();
		}

		parent::__construct($name, $title, $value, $form);
	}

	function Field($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR .'/jquery-form/jquery.form.js');
		Requirements::javascript('pagerater/javascript/jquery.rating.pack.js');
		Requirements::javascript('pagerater/javascript/PageRater.js');
		if($this->Config()->get("extra_form_selector")) {
			Requirements::customScript("PageRater.set_extra_form_selector('".$this->Config()->get("extra_form_selector")."');");
		}
		Requirements::themedCSS('jquery.rating', "pagerater");
		$html = "";

		$name = $this->getName();
		$id = $this->id();
		for($i = 1; $i < $this->starOptions + 1; $i++) {
			if($i == $this->Value()) {
				$html .= "<input name='$id' class='$id' type='radio' checked='checked' value='$i' />";
			}
			else{
				$html .= "<input name='$id' class='$id' type='radio' value='$i' />";
			}
		}
		$html .= "<input name='$name' type='hidden' id='$id' />";
		$jsForComments = '';
		if($this->Config()->get("allow_comments")) {
			$commentBoxID = $id."_Comment";
			$textField = TextareaField::create($commentBoxID, "Any comments you may have");
			$textField->addExtraClass("additionalComments");
			$html .= $textField->FieldHolder();
			$jsForComments = "jQuery('#".$commentBoxID."').fadeIn();";
		}
		Requirements::customScript(<<<JS
			jQuery('.$id').rating({
				required: true,
				callback: function(value, link) {
					jQuery('#$id').val(value);
					$jsForComments
				}
			});
JS
);
		return $html;
	}

}
