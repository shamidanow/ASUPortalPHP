<?php

class CWorkPlanEducationForm extends CAbstractPrintClassField {
    public function getFieldName()
    {
        return "Форма обучения";
    }

    public function getFieldDescription()
    {
        return "Используется при печати рабочей программы, принимает параметр id с Id рабочей программы";
    }

    public function getParentClassField()
    {

    }

    public function getFieldType()
    {
        return self::FIELD_TEXT;
    }

    public function execute($contextObject)
    {
		if (!is_null($contextObject->educationForm)) {
			$result = $contextObject->educationForm->getValue();
		}
        return $result;
    }
}