<?php

class CWorkPlanTermSectionsSecondLabWork extends CAbstractPrintClassField {
    public function getFieldName()
    {
        return "Нагрузка для второго семестра в списке. Лабораторные работы";
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
		if (!is_null($contextObject->terms)) {
        	$terms = array();
        	foreach ($contextObject->terms->getItems() as $term) {
        		$terms[] = $term->number;
        	}
        }
		$termSectionsData = new CArrayList();
        foreach ($contextObject->terms->getItems() as $term) {
            $query = new CQuery();
            $query->select("sum(if(term.alias = 'labwork', l.value, 0)) as labwork")
                ->from(TABLE_WORK_PLAN_CONTENT_SECTIONS." as section")
                ->innerJoin(TABLE_WORK_PLAN_CONTENT_LOADS." as l", "l.section_id = section.id")
                ->innerJoin(TABLE_TAXONOMY_TERMS." as term", "term.id = l.load_type_id")
                ->leftJoin(TABLE_WORK_PLAN_SELFEDUCATION." as selfedu", "selfedu.load_id = l.id")
                ->group("l.section_id")
                ->condition("l.term_id = ".$term->getId());
            $items = $query->execute();
            if ($items->getCount() > 0) {
                $termSectionsData->add($term->getId(), $items);
            }
        }
        foreach ($termSectionsData->getItems() as $termId=>$termData) {
        	if (CBaseManager::getWorkPlanTerm($termId)->number == $terms[1]) {
        		$result = 0;
        		foreach ($termData as $row) {
        			$result += $row["labwork"];	
        		}
        	}
        }
        return $result;
    }
}