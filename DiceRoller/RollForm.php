<?php

namespace DiceRoller;

class RollForm
{
    protected function __construct()
    {
    }

    public static function generate($inputFieldDefault)
    {
        $formHtml = "<form action=\"\">";
        $formHtml .= self::createInput("text", $inputFieldDefault, "roll");
        $formHtml .= self::createInput("submit", "Roll it!");
        $formHtml .= self::createHiddenFields();
        $formHtml .= "</form>";
        return $formHtml;
    }

    protected static function createHiddenFields()
    {
        $hiddenFields = "";
        foreach ($_GET as $name => $value) {
            $name = htmlspecialchars(strip_tags($name));
            if ($name != "roll") {
                $value = htmlspecialchars(strip_tags($value));
                $hiddenFields .= self::createInput("hidden", $name, $value);
            }
        }
        return $hiddenFields;
    }

    protected static function createInput($inputType, $inputValue, $inputName = null)
    {
        $inputType = htmlspecialchars(strip_tags($inputType));
        $inputValue = htmlspecialchars(strip_tags($inputValue));
        $generatedInput = "<input type=\"$inputType\"";
        if ($inputName != null && ($inputName = strip_tags($inputName)) != '') {
            $generatedInput .= " name=\"$inputName\"";
        }
        if ($inputValue != '') {
            $generatedInput .= " value=\"$inputValue\"";
        }
        $generatedInput .= "/>";
        return $generatedInput;
    }
}