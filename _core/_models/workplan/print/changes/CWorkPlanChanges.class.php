<?php

class CWorkPlanChanges extends CAbstractPrintClassField {
    public function getFieldName()
    {
        return "Изменения в рабочей программе";
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
		$result = $contextObject->changes;
        return $result;
    }
}