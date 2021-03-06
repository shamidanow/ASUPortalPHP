<?php
/**
 * Created by JetBrains PhpStorm.
 * User: aleksandr
 * Date: 21.11.12
 * Time: 21:06
 * To change this template use File | Settings | File Templates.
 */
class CPrintManager {
    private static $_cacheFormset = null;
    private static $_cacheFormsetInit = false;
    private static $_cacheForm = null;
    private static $_cacheField = null;
    private static $_cacheFieldInit = false;

    /**
     * @return CArrayList|null
     */
    private static function getCacheFormset() {
        if (is_null(self::$_cacheFormset)) {
            self::$_cacheFormset = new CArrayList();
        }
        return self::$_cacheFormset;
    }

    /**
     * @param $key
     * @return CPrintFormset
     */
    public static function getFormset($key) {
        if (!self::getCacheFormset()->hasElement($key)) {
        	$item = null;
            if (is_numeric($key)) {
                $item = CActiveRecordProvider::getById(TABLE_PRINT_FORMSETS, $key);
            } elseif (is_string($key)) {
                foreach (CActiveRecordProvider::getWithCondition(TABLE_PRINT_FORMSETS, "alias = '".$key."'")->getItems() as $i) {
                    $item = $i;
                }
            }
            if (!is_null($item)) {
                $formset = new CPrintFormset($item);
                self::getCacheFormset()->add($formset->getId(), $formset);
                self::getCacheFormset()->add($formset->alias, $formset);
            }
        }
        return self::getCacheFormset()->getItem($key);
    }

    /**
     * @return CArrayList|null
     */
    public static function getAllFormsets() {
        if (!self::$_cacheFormsetInit) {
            self::$_cacheFormsetInit = true;
            foreach (CActiveRecordProvider::getAllFromTable(TABLE_PRINT_FORMSETS)->getItems() as $item) {
                $formset = new CPrintFormset($item);
                self::getCacheFormset()->add($formset->getId(), $formset);
                self::getCacheFormset()->add($formset->alias, $formset);
            }
        }
        return self::getCacheFormset();
    }

    /**
     * @return CArrayList|null
     */
    private static function getCacheForm() {
        if (is_null(self::$_cacheForm)) {
            self::$_cacheForm = new CArrayList();
        }
        return self::$_cacheForm;
    }

    /**
     * @param $key
     * @return CPrintForm
     */
    public static function getForm($key) {
        if (!self::getCacheForm()->hasElement($key)) {
            if (is_numeric($key)) {
                $item = CActiveRecordProvider::getById(TABLE_PRINT_FORMS, $key);
            }
            if (!is_null($item)) {
                $form = new CPrintForm($item);
                self::getCacheForm()->add($form->getId(), $form);
            }
        }
        return self::getCacheForm()->getItem($key);
    }
    
    /**
     * Получить список всех печатных форм
     * 
     * @return CArrayList|null
     */
    public static function getAllForms() {
		$allForms = new CArrayList();
		foreach (CActiveRecordProvider::getAllFromTable(TABLE_PRINT_FORMS)->getItems() as $item) {
            $form = new CPrintForm($item);
            $allForms->add($form->getId(), $form);
		}
		return $allForms;
    }

    /**
     * @return CArrayList|null
     */
    private static function getCacheField() {
        if (is_null(self::$_cacheField)) {
            self::$_cacheField = new CArrayList();
        }
        return self::$_cacheField;
    }

    /**
     * @param $key
     * @return CPrintField
     */
    public static function getField($key) {
        if (!self::getCacheField()->hasElement($key)) {
            $item = null;
            if (is_numeric($key)) {
                $item = CActiveRecordProvider::getById(TABLE_PRINT_FIELDS, $key);
            } elseif (is_string($key)) {
                foreach (CActiveRecordProvider::getWithCondition(TABLE_PRINT_FIELDS, "LCASE(alias) = '".mb_strtolower($key)."'")->getItems() as $i) {
                    $item = $i;
                }
            }
            if (!is_null($item)) {
                $field = new CPrintField($item);
                self::getCacheField()->add($field->getId(), $field);
                self::getCacheField()->add($field->alias, $field);
            }
        }
        return self::getCacheField()->getItem($key);
    }

    /**
     * Все поля
     *
     * @return CArrayList
     */
    public static function getAllFields() {
        if (!self::$_cacheFieldInit) {
            self::$_cacheFieldInit = true;
            foreach (CActiveRecordProvider::getAllFromTable(TABLE_PRINT_FIELDS)->getItems() as $item) {
                $field = new CPrintField($item);
                self::getCacheField()->add($field->getId(), $field);
            }
        }
        return self::getCacheField();
    }
    
    /**
     * Получить список описателей по id набора печатной формы
     *
     * @param $key
     * @return CArrayList
     */
    public static function getListFieldsByFormset($key) {
    	$listFields = new CArrayList();
    	foreach (CActiveRecordProvider::getWithCondition(TABLE_PRINT_FIELDS, "formset_id = ".$key)->getItems() as $item) {
    		$field = new CPrintField($item);
    		$listFields->add($field->getId(), $field);
    	}
    	return $listFields;
    }
    
    /**
     * Получить объект описателя-класса по названию поля
     *
     * @param String $fieldName
     * @param CModel $object
     * @throws Exception
     * @return IPrintClassField
     */
    public static function getPrintClassField($fieldName, CModel $object) {
        $field = null;
        if (mb_strpos($fieldName, ".class") !== false) {
            $classFieldName = CUtils::strLeft($fieldName, ".class");
            /**
             * @var $classField IPrintClassField
             */
            if (class_exists($classFieldName)) {
                $classField = new $classFieldName();
            } else {
                throw new Exception("Класс ".$classFieldName." не объявлен в системе!");
            }
            if (!is_a($classField, "IPrintClassField")) {
                throw new Exception("Класс ".$classField." не реализует интерфейс IPrintClassField");
            }
            /**
             * Дабы не ломать уже имеющуюся структуру работать будем через
             * класс-адаптер
             */
            $field = new CPrintClassFieldToFieldAdapter($classField, $object);
        }
        return $field;
    }
    
    /**
     * Получить пустой CArrayList с экземпляром CModel
     * (для совместимости с подсистемой печати и при указании в настройках контекста дополнительных параметров)
     *
     * @param int $id - произвольный id
     * @return CArrayList
     */
    public static function getCArrayListWithInstanceCModel($id) {
        $items = new CArrayList();
        $model = new CModel();
        $items->add($id, $model);
        return $items;
    }
}
