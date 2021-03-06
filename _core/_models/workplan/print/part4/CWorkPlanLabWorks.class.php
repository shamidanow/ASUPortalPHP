<?php

class CWorkPlanLabWorks extends CAbstractPrintClassField {
    public function getFieldName()
    {
        return "Лабораторные работы занятия дисциплины";
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
        return self::FIELD_TABLE;
    }

    public function execute($contextObject)
    {
        $result = array();
        $discipline = CCorriculumsManager::getDiscipline($contextObject->corriculum_discipline_id);
        $sum = 0;
        $sumLabwork = 0;
        foreach ($discipline->sections->getItems() as $section) {
        	foreach ($section->labors->getItems() as $labor) {
        		if ($labor->type->getAlias() == CWorkPlanLoadTypeConstants::CURRICULUM_LABOR_LAB_WORK) {
        			$sumLabwork += $labor->value;
        		}
        	}
        }
        if (!$contextObject->getLabWorks()->isEmpty() and $sumLabwork != 0) {
        	foreach ($contextObject->getLabWorks()->getItems() as $termData) {
        		foreach ($termData as $row) {
        			$dataRow = array();
        			$dataRow[0] = count($result) + 1;
        			$dataRow[1] = $row->load->section->sectionIndex;
        			$dataRow[2] = $row->title;
        			$dataRow[3] = $row->value;
        			$result[] = $dataRow;
        			$sum += $row->value;
        		}
        	}
        	$total = array();
        	$total[0] = "";
        	$total[1] = "";
        	$total[2] = "Итого";
        	$total[3] = $sum;
        } elseif ($sumLabwork != 0) {
        	$countItems = $sumLabwork/4;
        	for ($i = 1; $i <= $countItems; $i++) {
        		$dataRow = array();
        		$dataRow[0] = $i;
        		$dataRow[1] = $i;
        		$dataRow[2] = "Лабораторная работа №".$i;
        		$dataRow[3] = "4";
        		$result[] = $dataRow;
        		 
        	}
        	$total = array();
        	$total[0] = "";
        	$total[1] = "";
        	$total[2] = "Итого";
        	$total[3] = $sumLabwork;
        }
        if ($sum != 0 or $sumLabwork != 0) {
        	$result[] = $total;
        }
        return $result;
    }
}