<?php

class CWorkPlanTermSections extends CAbstractPrintClassField {
    public function getFieldName()
    {
        return "Нагрузка по разделам дисциплины с КСР";
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
        if (!is_null($contextObject->terms)) {
        	$termSectionsData = new CArrayList();
        	foreach ($contextObject->terms->getItems() as $term) {
        		$query = new CQuery();
        		$select = array();
        		$select[] = "section.id";
        		$select[] = "section.sectionIndex";
        		$select[] = "section.name";
        		$select[] = "section.content";
        		$select[] = "sum(if(term.alias in ('lecture', 'practice', 'labwork', 'ksr'), l.value, 0)) + sum(ifnull(selfedu.question_hours, 0)) as total";
        		$select[] = "sum(if(term.alias = 'lecture', l.value, 0)) as lecture";
        		$select[] = "sum(if(term.alias = 'practice', l.value, 0)) as practice";
        		$select[] = "sum(if(term.alias = 'labwork', l.value, 0)) as labwork";
        		$select[] = "sum(if(term.alias = 'ksr', l.value, 0)) as ksr";
        		$select[] = "sum(ifnull(selfedu.question_hours, 0)) as selfedu";
        		$query->select(join(", ", $select))
	        		->from(TABLE_WORK_PLAN_CONTENT_SECTIONS." as section")
	        		->innerJoin(TABLE_WORK_PLAN_CONTENT_LOADS." as l", "l.section_id = section.id")
	        		->innerJoin(TABLE_TAXONOMY_TERMS." as term", "term.id = l.load_type_id")
	        		->leftJoin(TABLE_WORK_PLAN_SELFEDUCATION." as selfedu", "selfedu.load_id = l.id")
	        		->group("l.section_id")
	        		->condition("l.term_id = ".$term->getId()." and l._deleted = 0");
        		$items = $query->execute();
        		if ($items->getCount() > 0) {
        			$termSectionsData->add($term->getId(), $items);
        		}
        	}
        	foreach ($termSectionsData->getItems() as $termId=>$termData) {
        		foreach ($termData as $row) {
        			$query = new CQuery();
        			$query->select("tech.technology_id")
	        			->from(TABLE_WORK_PLAN_CONTENT_SECTIONS." as section")
	        			->innerJoin(TABLE_WORK_PLAN_CONTENT_LOADS." as l", "l.section_id = section.id")
	        			->innerJoin(TABLE_WORK_PLAN_CONTENT_TECHNOLOGIES." as tech", "tech.load_id = l.id")
	        			->condition("l.section_id = ".$row["id"]);
        			$technologies = array();
        			foreach ($query->execute()->getItems() as $items) {
        				foreach ($items as $tech) {
        					$technologies[] = CTaxonomyManager::getTerm($tech)->getValue();
        				}
        			}
        			$dataRow = array();
        			$dataRow[0] = $row["sectionIndex"];
        			$dataRow[1] = $row["name"].": ".$row["content"];
        			$dataRow[2] = $row["lecture"];
        			$dataRow[3] = $row["practice"];
        			$dataRow[4] = $row["labwork"];
        			$dataRow[5] = $row["ksr"];
        			$dataRow[6] = $row["selfedu"];
        			$dataRow[7] = $row["total"];
        			$dataRow[8] = "";
        			$dataRow[9] = implode(", ", $technologies);
        			$result[] = $dataRow;
        		}
        	}
        }
        return $result;
    }
}