<?php
class CIndPlanWorkController extends CBaseController{
    public function __construct() {
        if (!CSession::isAuth()) {
            $action = CRequest::getString("action");
            if ($action == "") {
                $action = "index";
            }
            if (!in_array($action, $this->allowedAnonymous)) {
                $this->redirectNoAccess();
            }
        }

        $this->_smartyEnabled = true;
        $this->setPageTitle("Управление нагрузкой");

        parent::__construct();
    }
    public function actionAdd() {
    	$year = CRequest::getInt("year");
        if (CRequest::getInt("type") == "1") {
            $load = CIndPlanManager::getLoad(CRequest::getInt("id"));
            $object = $load->getStudyLoadTable();
            $this->addJSInclude("_modules/_individual_plan/plan.js");
            $this->addActionsMenuItem(
            		array(
            				"title" => "Назад",
            				"link" => "load.php?action=view&id=".$object->getLoad()->person_id."&year=".$year,
            				"icon" => "actions/edit-undo.png"
            		)
            );
        } else {
            $object = new CIndPlanPersonWork();
            $object->load_id = CRequest::getInt("id");
            $object->work_type = CRequest::getInt("type");
            $this->addActionsMenuItem(
            		array(
            				"title" => "Назад",
            				"link" => "load.php?action=view&id=".$object->load->person_id."&year=".$year,
            				"icon" => "actions/edit-undo.png"
            		)
            );
        }
        $this->setData("object", $object);
        $this->renderView("_individual_plan/work/add.tpl");
    }
    public function actionEdit() {
    	$year = CRequest::getInt("year");
    	if (CRequest::getInt("type") == "1") {
    		$load = CIndPlanManager::getLoad(CRequest::getInt("id"));
    		$object = $load->getStudyLoadTable();
    		$this->addJSInclude("_modules/_individual_plan/plan.js");
    		$this->addActionsMenuItem(
    				array(
    						"title" => "Назад",
    						"link" => "load.php?action=view&id=".$object->getLoad()->person_id."&year=".$year,
    						"icon" => "actions/edit-undo.png"
    				)
    		);
    		$this->setData("restrictionAttribute", $load->restrictionAttribute());
    	} else {
        	$object = CIndPlanManager::getWork(CRequest::getInt("id"));
        	$object->_edit_restriction = $object->load->_edit_restriction;
    		$this->addActionsMenuItem(
    				array(
    						"title" => "Назад",
    						"link" => "load.php?action=view&id=".$object->load->person_id."&year=".$year,
    						"icon" => "actions/edit-undo.png"
    				)
    		);
    	}
        $this->setData("object", $object);
        $this->renderView("_individual_plan/work/edit.tpl");
    }
    public function actionDelete() {
        $object = CIndPlanManager::getWork(CRequest::getInt("id"));
        $id = $object->load->person_id;
        $year = $object->load->year_id;
        $object->remove();
        $this->redirect("load.php?action=view&id=".$id."&year=".$year);
    }
    public function actionSave() {
        $arr = CRequest::getArray("CModel");
        if ($arr["work_type"] == "1") {
            $load = CIndPlanManager::getLoad($arr["load_id"]);
            $object = new CIndPlanPersonLoadTable($load);
            $object->setAttributes(CRequest::getArray($object::getClassName()));
            if ($object->validate()) {
                $object->save();
                if ($this->continueEdit()) {
                    $this->redirect("work.php?action=add&id=".$object->getLoad()->getId()."&type=1"."&year=".$object->getLoad()->year_id);
                } else {
                    $this->redirect("load.php?action=view&id=".$object->getLoad()->person_id."&year=".$object->getLoad()->year_id);
                }
                return true;
            }
        } else {
            $object = new CIndPlanPersonWork();
            $object->setAttributes(CRequest::getArray($object::getClassName()));
            if ($object->validate()) {
                $object->save();
                if ($this->continueEdit()) {
                    $this->redirect("work.php?action=edit&id=".$object->getId()."&year=".$object->load->year_id);
                } else {
                    $this->redirect("load.php?action=view&id=".$object->load->person_id."&year=".$object->load->year_id);
                }
                return true;
            }
        }
        $this->setData("object", $object);
        $this->renderView("_individual_plan/work/edit.tpl");
    }
    public function actionGetDataForAutofill() {
        // получаем объект учебной нагрузки, который будем заполнять
        $load = CIndPlanManager::getLoad(CRequest::getInt("load_id"));
        $loadTable = $load->getStudyLoadTable();
        echo json_encode($loadTable->getAutoFillData(
            CRequest::getInt("type_1") == 1,
            CRequest::getInt("type_2") == 1,
            CRequest::getInt("type_3") == 1,
            CRequest::getInt("type_4") == 1,
            CRequest::getInt("filials") == 1
        ));
    }
}