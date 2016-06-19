<?php

class PageRaterStarField extends FormField {

    private static $extra_form_selector = '';

    private static $allow_comments = false;

    private static $allow_name = false;

    private static $allow_title = false;

    protected $rating = 0;

    protected $starOptions = 5;

    protected $moreFieldsArray = array();

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

    protected $_field_value = null;

    function Field($properties = array()) {
        if( ! $this->_field_value) {

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

            if($this->Config()->get("allow_name")) {
                $fieldID = $id."_Name";
                $nameField = TextField::create($fieldID, _t("PageRaterStarField.NAME_LABEL", "Your Name"));
                $nameField->addExtraClass("additionalComments");
                $html .= $nameField->FieldHolder();
                $this->moreFieldsArray[] = $fieldID;
            }

            if($this->Config()->get("allow_title")) {
                $fieldID = $id."_Title";
                $titleField = TextField::create($fieldID, _t("PageRaterStarField.TITLE_LABEL", "Comment Header"));
                $titleField->addExtraClass("additionalComments");
                $html .= $titleField->FieldHolder();
                $this->moreFieldsArray[] = $fieldID;
            }

            if($this->Config()->get("allow_comments")) {
                $fieldID = $id."_Comment";
                $commentField = TextareaField::create($fieldID, _t("PageRaterStarField.COMMENTS_LABEL", "Any comments you may have"));
                $commentField->addExtraClass("additionalComments");
                $html .= $commentField->FieldHolder();
                $this->moreFieldsArray[] = $fieldID;
            }
            $moreFieldsAsString = implode("', '", $this->moreFieldsArray);
            Requirements::customScript('
            if(typeof PageRaterVariables === "undefined") {
                var PageRaterVariables = [];
            }
            PageRaterVariables.push(
                {
                    id: \''.$id.'\',
                    fields: [\''.$moreFieldsAsString.'\']
                }
            );
            ');
            $this->_field_value = $html;
        }
        return $this->_field_value;
    }

    function getRequiredFields(){
        $this->Field();
        return $this->moreFieldsArray;
    }

}
