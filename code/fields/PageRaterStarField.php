<?php

class PageRaterStarField extends FormField
{

    protected $rating = 0;

    protected $starOptions = 5;

    private static $extra_form_selector = '';

    /**
     * Returns an input field, class="start field" and type="hidden" with an optional maxlength
     */
    public function __construct($name, $title = null, $value = "", $starOptions, $form = null)
    {
        $this->starOptions = $starOptions;

        parent::__construct($name, $title, $value, $form);
    }

    public function Field($properties = array())
    {
        Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');
        Requirements::javascript(THIRDPARTY_DIR .'/jquery-form/jquery.form.js');
        Requirements::javascript('pagerater/javascript/jquery.rating.pack.js');
        Requirements::javascript('pagerater/javascript/PageRater.js');
        if ($this->Config()->get("extra_form_selector")) {
            Requirements::customScript("PageRater.set_extra_form_selector('".$this->Config()->get("extra_form_selector")."');");
        }
        Requirements::themedCSS('jquery.rating', "pagerater");
        $html = "";

        $name = $this->getName();
        $id = $this->id();
        for ($i = 1; $i < $this->starOptions + 1; $i++) {
            if ($i == $this->Value()) {
                $html .= "<input name='$id' class='$id' type='radio' checked='checked' value='$i' />";
            } else {
                $html .= "<input name='$id' class='$id' type='radio' value='$i' />";
            }
        }
        $html .= "<input name='$name' type='hidden' id='$id' />";

        Requirements::customScript(<<<JS
			jQuery('.$id').rating({
				required: true,
				callback: function(value, link) {
					jQuery('#$id').val(value);
				}
			});
JS
);

        return $html;
    }
}
