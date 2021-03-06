<?php
/**
 * Created by PhpStorm.
 * User: abarmin
 * Date: 13.03.15
 * Time: 21:49
 */

class CWorkPlanController extends CFlowController{
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
        $this->setPageTitle("Рабочие программы");

        parent::__construct();
    }

    /**
     * Добавление плана из представления.
     * Сначала надо выбрать учебный план
     */
    public function actionAddFromView() {
        $items = new CArrayList();
        /**
         * @var $corriculum CCorriculum
         */
        foreach (CCorriculumsManager::getAllCorriculums()->getItems() as $corriculum) {
            $items->add($corriculum->getId(), $corriculum->title);
        }
        $this->setData("items", $items);
        $this->renderView("_corriculum/_workplan/workplan/addWorkplanFromView.tpl");
        //$this->renderView("_flow/pickList.tpl", get_class($this), "AddFromView_SelectDiscipline");
    }

    /**
     * Добавление плана из представления
     * Выбор дисциплины в указанном учебном плане
     */
    public function actionAddFromView_SelectDiscipline() {
        $selected = CRequest::getArray("selected");
        $items = new CArrayList();
        $corriculum = CCorriculumsManager::getCorriculum($selected[0]);
        /**
         * @var $cycle CCorriculumCycle
         */
        foreach ($corriculum->getDisciplines() as $discipline) {
            $items->add($discipline->getId(), $discipline->discipline->getValue());
        }
        $this->setData("items", $items);
        $this->renderView("_corriculum/_workplan/workplan/addWorkplanFromViewSelectDiscipline.tpl");
        //$this->renderView("_flow/pickList.tpl", get_class($this), "AddFromView_CreateWorkPlan");
    }

    /**
     * Добавление плана из представления
     * Переадресация на стандартную функцию создания
     */
    public function actionAddFromView_CreateWorkPlan() {
        $selected = CRequest::getArray("selected");
        $this->redirect("workplans.php?action=add&id=".$selected[0]);
    }
    public function actionIndex() {
        $currentPerson = null;
        $currentCorriculum = null;
        $set = new CRecordSet();
        $query = new CQuery();
        $set->setQuery($query);
        $query->select("distinct wp.*")
            ->from(TABLE_WORK_PLANS." as wp")
            ->leftJoin(TABLE_DISCIPLINES." as discipline", "wp.discipline_id=discipline.id")
            ->leftJoin(TABLE_CORRICULUM_DISCIPLINES." as corr_discipline", "wp.corriculum_discipline_id=corr_discipline.id")
            ->leftJoin(TABLE_CORRICULUM_CYCLES." as corr_cycle", "corr_discipline.cycle_id=corr_cycle.id")
            ->leftJoin(TABLE_CORRICULUMS." as corriculum", "corr_cycle.corriculum_id=corriculum.id")
            ->leftJoin(TABLE_WORK_PLAN_PROFILES." as profile", "wp.id=profile.plan_id")
            ->leftJoin(TABLE_TAXONOMY_TERMS." as term", "profile.profile_id=term.id")
            ->leftJoin(TABLE_WORK_PLAN_AUTHORS." as author", "wp.id=author.plan_id")
            ->leftJoin(TABLE_PERSON." as person", "author.person_id=person.id")
            ->condition("wp.is_archive = 0")
            ->order("wp.id desc");
        $authorsQuery = new CQuery();
        $authorsQuery->select("person.*")
	        ->from(TABLE_PERSON." as person")
	        ->order("person.fio asc")
	        ->innerJoin(TABLE_WORK_PLAN_AUTHORS." as author", "person.id = author.person_id");
        $corriculumsQuery = new CQuery();
        $corriculumsQuery->select("corriculum.*")
	        ->from(TABLE_CORRICULUMS." as corriculum")
	        ->order("corriculum.title asc")
	        ->innerJoin(TABLE_CORRICULUM_CYCLES." as corr_cycle", "corr_cycle.corriculum_id=corriculum.id")
	        ->innerJoin(TABLE_CORRICULUM_DISCIPLINES." as corr_discipline", "corr_discipline.cycle_id=corr_cycle.id")
	        ->innerJoin(TABLE_WORK_PLANS." as wp", "wp.corriculum_discipline_id=corr_discipline.id");
        $isArchive = false;
        $isApprove = (CRequest::getString("isApprove") == "1");
        if (CRequest::getInt("isArchive") == "1") {
            $isArchive = true;
            $titleArchive = "Убрать из архива";
            $actionArchive = "outArchiv";
        } else {
            $titleArchive = "Переместить в архив";
            $actionArchive = "inArchiv";
        }
        if ($isArchive) {
            $query->condition("wp.is_archive = 1");
        }
        if (CRequest::getString("order") == "discipline.name") {
        	$direction = "asc";
        	if (CRequest::getString("direction") != "") {
        		$direction = CRequest::getString("direction");}
        		$query->order("discipline.name ".$direction);
        } elseif (CRequest::getString("order") == "corriculum.title") {
        	$direction = "asc";
        	if (CRequest::getString("direction") != "") {
        		$direction = CRequest::getString("direction");}
        		$query->order("corriculum.title ".$direction);
        } elseif (CRequest::getString("order") == "term.name") {
        	$direction = "asc";
        	if (CRequest::getString("direction") != "") {
        		$direction = CRequest::getString("direction");}
        		$query->order("term.name ".$direction);
        } elseif (CRequest::getString("order") == "person.fio") {
        	$direction = "asc";
        	if (CRequest::getString("direction") != "") {
        		$direction = CRequest::getString("direction");}
        		$query->order("person.fio ".$direction);
        } elseif (CRequest::getString("order") == "year") {
        	$direction = "asc";
        	if (CRequest::getString("direction") != "") {
        		$direction = CRequest::getString("direction");}
        	$query->order('STR_TO_DATE(wp.year, "%Y") '.$direction);
        }
        // фильтр по автору
        if (!is_null(CRequest::getFilter("person.id"))) {
        	$currentPerson = CRequest::getFilter("person.id");
        	$query->innerJoin(TABLE_WORK_PLAN_AUTHORS." as authors", "wp.id=authors.plan_id");
        	$query->innerJoin(TABLE_PERSON." as persons", "authors.person_id=persons.id and persons.id = ".$currentPerson);
        	// фильтруем еще и учебные планы
        	$corriculumsQuery->innerJoin(TABLE_WORK_PLAN_AUTHORS." as author", "wp.id=author.plan_id");
        	$corriculumsQuery->innerJoin(TABLE_PERSON." as person", "author.person_id=person.id and person.id = ".$currentPerson);
        }
        // фильтр по учебному плану
        if (!is_null(CRequest::getFilter("corriculum.id"))) {
        	$currentCorriculum = CRequest::getFilter("corriculum.id");
        	$query->innerJoin(TABLE_CORRICULUM_DISCIPLINES." as corr_disciplines", "wp.corriculum_discipline_id=corr_disciplines.id");
        	$query->innerJoin(TABLE_CORRICULUM_CYCLES." as corr_cycles", "corr_disciplines.cycle_id=corr_cycles.id");
        	$query->innerJoin(TABLE_CORRICULUMS." as corriculums", "corr_cycles.corriculum_id=corriculums.id and corriculums.id = ".$currentCorriculum);
        	// фильтруем еще и авторов
        	$authorsQuery->innerJoin(TABLE_WORK_PLANS." as wp", "wp.id=author.plan_id");
        	$authorsQuery->innerJoin(TABLE_CORRICULUM_DISCIPLINES." as corr_disciplines", "wp.corriculum_discipline_id=corr_disciplines.id");
        	$authorsQuery->innerJoin(TABLE_CORRICULUM_CYCLES." as corr_cycles", "corr_disciplines.cycle_id=corr_cycles.id");
        	$authorsQuery->innerJoin(TABLE_CORRICULUMS." as corriculums", "corr_cycles.corriculum_id=corriculums.id and corriculums.id = ".$currentCorriculum);
        }
        $term = CRequest::getString("textSearch");
        if ($term != "") {
        	//поиск по отображаемому наименованию, дисциплине, учебному плану, году, профилю, автору и наименованию
        	$query->condition("wp.title_display like '%".$term."%' or
        			discipline.name like '%".$term."%' or
        			corriculum.title like '%".$term."%' or
        			wp.year like '%".$term."%' or
        			term.name like '%".$term."%' or
        			person.fio like '%".$term."%' or
        			wp.title like '%".$term."%'");
        }
        if (CRequest::getInt("corriculumId") != 0) {
        	$query->condition("corr_cycle.corriculum_id = ".CRequest::getString("corriculumId"));
        }
        if (CSession::getCurrentUser()->getLevelForCurrentTask() == ACCESS_LEVEL_READ_OWN_ONLY or
        	CSession::getCurrentUser()->getLevelForCurrentTask() == ACCESS_LEVEL_WRITE_OWN_ONLY) {
        		$query->innerJoin(TABLE_WORK_PLAN_AUTHORS." as authors", "wp.id=authors.plan_id");
        		$query->innerJoin(TABLE_PERSON." as kadri", "authors.person_id=kadri.id and kadri.id = ".CSession::getCurrentPerson()->getId());
        }
        $authors = array();
        foreach ($authorsQuery->execute()->getItems() as $ar) {
        	$author = new CPerson(new CActiveRecord($ar));
        	$authors[$author->getId()] = $author->getName();
        }
        $corriculums = array();
        foreach ($corriculumsQuery->execute()->getItems() as $ar) {
        	$corriculum = new CCorriculum(new CActiveRecord($ar));
        	$corriculums[$corriculum->getId()] = $corriculum->title;
        }
        $paginated = new CArrayList();
        foreach ($set->getPaginated()->getItems() as $ar) {
            $plan = new CWorkPlan($ar);
            $paginated->add($plan->getId(), $plan);
        }
        $this->addActionsMenuItem(array(
        	array(
				"title" => "Групповые операции",
				"link" => "#",
				"icon" => "apps/utilities-terminal.png",
				"child" => array(
					array(
						"title" => "Удалить выделенные",
						"icon" => "actions/edit-delete.png",
						"form" => "#MainView",
						"link" => "workplans.php",
						"action" => "delete"
					),
					array(
						"title" => $titleArchive,
						"icon" => "devices/media-floppy.png",
						"form" => "#MainView",
						"link" => "workplans.php",
						"action" => $actionArchive
					),
					array(
						"title" => "Сменить учебный план",
						"icon" => "actions/edit-redo.png",
						"link" => "#",
						"onclick" => "selectedForCorriculumChange()"
					),
					array(
						"title" => "Копировать в другой учебный план",
						"icon" => "actions/edit-copy.png",
						"link" => "#",
						"onclick" => "selectedForCorriculumCopy()"
					)
				)
			)
        ));
        /**
         * Параметры для групповой печати по шаблону
         */
        $this->setData("templateWorkplans", "formset_workplans");
        $this->setData("selectedDoc", true);
        $this->setData("url", null);
        $this->setData("actionGetWorkplans", null);
        $this->setData("id", null);
        /**
         * Параметры для печати по шаблону списка рабочих программ
         */
        $template = "formset_workplans_list";
        $formset = CPrintManager::getFormset($template);
        $this->setData("formset", $formset);
        $this->setData("template", $template);
        
        $this->setData("isArchive", $isArchive);
        $this->setData("isApprove", $isApprove);
        $this->setData("plans", $paginated);
        $this->setData("workplanAuthors", $authors);
        $this->setData("currentPerson", $currentPerson);
        $this->setData("workplanCorriculums", $corriculums);
        $this->setData("currentCorriculum", $currentCorriculum);
        $this->setData("paginator", $set->getPaginator());
        if (!$isApprove) {
        	$requestParams = array();
        	foreach (CRequest::getGlobalRequestVariables()->getItems() as $key=>$value) {
        		$requestParams[] = $key."=".$value;
        	}
        	$requestParams[] = "isApprove=1";
        	$this->addActionsMenuItem(array(
        		array(
        			"title" => "Утверждение статусов рабочих программ",
        			"link" => "?".implode("&", $requestParams),
        			"icon" => "actions/bookmark-new.png"
        		),
        	));
        	$this->renderView("_corriculum/_workplan/workplan/index.tpl");
        } else {
        	$requestParams = array();
        	foreach (CRequest::getGlobalRequestVariables()->getItems() as $key=>$value) {
        		if ($key != "isApprove") {
        			$requestParams[] = $key."=".$value;
        		}
        	}
        	$this->addActionsMenuItem(array(
        		array(
        			"title" => "Список рабочих программ",
        			"link" => "?".implode("&", $requestParams),
        			"icon" => "actions/format-justify-center.png"
        		),
        	));
        	$this->renderView("_corriculum/_workplan/workplan/approve.tpl");
        }
    }
    public function actionDelete() {
        $plan = CWorkPlanManager::getWorkplan(CRequest::getInt("id"));
        if (!is_null($plan)) {
        	$plan->remove();
        }
        $items = CRequest::getArray("selectedDoc");
        foreach ($items as $id){
        	$plan = CWorkPlanManager::getWorkplan($id);
        	$plan->remove();
        }
        $this->redirect("workplans.php");
    }
    public function actionInArchiv() {
    	$items = CRequest::getArray("selectedDoc");
    	foreach ($items as $id){
    		$plan = CWorkPlanManager::getWorkplan($id);
    		$plan->is_archive = 1;
    		$plan->save();
    	}
    	$this->redirect("workplans.php");
    }
    public function actionOutArchiv() {
    	$items = CRequest::getArray("selectedDoc");
    	foreach ($items as $id){
    		$plan = CWorkPlanManager::getWorkplan($id);
    		$plan->is_archive = 0;
    		$plan->save();
    	}
    	$this->redirect("workplans.php");
    }
    public function actionAdd() {
        /**
         * получим дисциплину, по которой делаем рабочую программу
         * @var CCorriculumDiscipline $discipline
         * @var CCorriculum $corriculum
         */
        $discipline = CCorriculumsManager::getDiscipline(CRequest::getInt("id"));
        $corriculum = $discipline->cycle->corriculum;
        //
        $plan = new CWorkPlan();
        $plan->title = "Наименование не указано";
        $plan->title_display = $plan->title;
        // название кафедры
        $plan->department_id = CSettingsManager::getSettingValue("department_id");
        // уровень подготовки
        $plan->level_id = CSettingsManager::getSettingValue("level_training_id");;
        // дисциплина из учебного плана
        $plan->corriculum_discipline_id = $discipline->getId();
        // дисциплина из справочника
        if (!is_null($discipline->discipline)) {
            $plan->discipline_id = $discipline->discipline->getId();
        }
        // копируем информацию из учебного плана
        if (!is_null($corriculum)) {
            $plan->direction_id = $corriculum->speciality_direction_id;
            $plan->qualification_id = $corriculum->qualification_id;
            $plan->education_form_id = $corriculum->form_id;
        }
        $plan->date_of_formation = date("Y-m-d");
        $plan->year = date("Y");
        $plan->authors = new CArrayList();
        $plan->authors->add(CSession::getCurrentPerson()->getId(), CSession::getCurrentPerson()->getId());
        // место дисциплины в структуре плана
        if (!is_null($discipline->cycle)) {
            $plan->position = "Дисциплина относится к базовой части учебного цикла ".$discipline->cycle->title ;
        }
        // утверждающий и должность утверждающего - значение по умолчанию из справочника
        $plan->approver_post = CTaxonomyManager::getTaxonomy("approver_workplan_posts")->getTerm("defaultPost")->getId();
        $plan->approver_name = CTaxonomyManager::getTaxonomy("approver_workplan_names")->getTerm("defaultName")->getId();
        $plan->save();
        /**
         * Скопируем компетенции из плана
         * @var CCorriculumDisciplineCompetention $competention
         */
        foreach ($discipline->competentions->getItems() as $competention) {
            $planCompetention = new CWorkPlanCompetention();
            $planCompetention->plan_id = $plan->getId();
            $planCompetention->allow_delete = 0;
            $planCompetention->competention_id = $competention->competention_id;
            $planCompetention->level_id = $competention->level_id;
            $planCompetention->save();
            foreach ($competention->knowledges->getItems() as $knowledge) {
            	$planCompetentionKnowledge = new CWorkPlanCompetentionKnowledge();
            	$planCompetentionKnowledge->competention_id = $planCompetention->getId();
            	$planCompetentionKnowledge->knowledge_id = $knowledge->getId();
            	$planCompetentionKnowledge->save();
            }
            foreach ($competention->skills->getItems() as $skill) {
            	$planCompetentionSkill = new CWorkPlanCompetentionSkill();
            	$planCompetentionSkill->competention_id = $planCompetention->getId();
            	$planCompetentionSkill->skill_id = $skill->getId();
            	$planCompetentionSkill->save();
            }
            foreach ($competention->experiences->getItems() as $experience) {
            	$planCompetentionExperience = new CWorkPlanCompetentionExperience();
            	$planCompetentionExperience->competention_id = $planCompetention->getId();
            	$planCompetentionExperience->experience_id = $experience->getId();
            	$planCompetentionExperience->save();
            }
        }
        $category = new CWorkPlanContentCategory();
        $category->plan_id = $plan->getId();
        $category->order = 1;
        $category->title = "Пустая категория";
        $category->save();
        $this->redirect("?action=edit&id=".$plan->getId());
    }
    public function actionEdit() {
        $plan = CWorkPlanManager::getWorkplan(CRequest::getInt("id"));
        $plan->date_of_formation = date("d.m.Y", strtotime($plan->date_of_formation));
        $this->addActionsMenuItem(array(
            array(
                "title" => "Назад",
                "link" => "workplans.php?action=index",
                "icon" => "actions/edit-undo.png"
            ),
        	array(
        		"title" => "К дисциплине уч. плана",
        		"link" => "disciplines.php?action=edit&id=".$plan->corriculum_discipline_id,
        		"icon" => "actions/edit-undo.png"
        	),
        	array(
        		"title" => "Печать по шаблону",
        		"link" => "#",
        		"icon" => "devices/printer.png",
        		"template" => "formset_workplans"
        	),
        	array(
        		"title" => "Копировать рабочую программу",
        		"link" => "workplans.php?action=selectCorriculum&id=".$plan->getId(),
        		"icon" => "actions/edit-copy.png"
        	),
        	array(
        		"title" => "Добавить литературу",
        		"link" => "workplans.php?action=addLiterature&plan_id=".$plan->getId(),
        		"icon" => "actions/list-add.png"
        	)
        ));
        $this->setData("plan", $plan);

        //$this->addJSInclude(JQUERY_UI_JS_PATH);
        $this->addCSSInclude(JQUERY_UI_CSS_PATH);
        $this->addCSSInclude("_modules/_redactor/redactor.css");
        $this->addJSInclude("_modules/_redactor/redactor.min.js");

        $this->renderView("_corriculum/_workplan/workplan/edit.tpl");
    }
    public function actionSave() {
        $plan = new CWorkPlan();
        $plan->setAttributes(CRequest::getArray($plan->getClassName()));
        if ($plan->validate()) {
        	$plan->date_of_formation = date("Y-m-d", strtotime($plan->date_of_formation));
            $plan->save();
            if ($this->continueEdit()) {
                $this->redirect("workplans.php?action=edit&id=".$plan->getId());
            } else {
                $this->redirect("disciplines.php?action=edit&id=".$plan->corriculum_discipline_id);
            }
            return true;
        }
        $plan->date_of_formation = date("d.m.Y", strtotime($plan->date_of_formation));
        $this->setData("plan", $plan);
        $this->renderView("_corriculum/_workplan/workplan/edit.tpl");
    }
    public function actionSearch() {
        $res = array();
        $term = CRequest::getString("query");
        /**
         * Сначала поищем по учебного плана
         */
        $query = new CQuery();
        $query->select("distinct(wp.id) as id, wp.title as title")
            ->from(TABLE_WORK_PLANS." as wp")
            ->condition("wp.title like '%".$term."%'")
            ->limit(0, 5);
        foreach ($query->execute()->getItems() as $item) {
            $res[] = array(
                "field" => "wp.id",
                "value" => $item["id"],
                "label" => $item["title"],
                "class" => "CWorkPlan"
            );
        }
        echo json_encode($res);
    }
    /**
     * Смена учебного плана для списка рабочих программ.
     * Выбор учебного плана
     */
    public function actionCorriculumToChange() {
    	$items = array();
    	foreach (CCorriculumsManager::getAllCorriculums()->getItems() as $corriculum) {
    		$items[$corriculum->getId()] = $corriculum->title;
    	}
    	$this->addActionsMenuItem(array(
    		array(
    			"title" => "Назад",
    			"link" => "workplans.php?action=index",
    			"icon" => "actions/edit-undo.png"
    		)
    	));
    	$this->setData("items", $items);
    	$this->setData("plans", CRequest::getString("selected"));
    	$this->renderView("_corriculum/_workplan/workplan/changeCorriculum.tpl");
    }
    /**
     * Смена учебного плана для списка рабочих программ
     */
    public function actionChangeCorriculum() {
    	$plans = explode(":", CRequest::getString("plans"));
    	$corriculum = CCorriculumsManager::getCorriculum(CRequest::getInt("corriculum_id"));
    	foreach ($plans as $id) {
    		$plan = CWorkPlanManager::getWorkplan($id);
    		foreach ($corriculum->getDisciplines() as $discipline) {
    			if ($plan->corriculumDiscipline->discipline->getValue() == $discipline->discipline->getValue()) {
    				$plan->corriculum_discipline_id = $discipline->getId();
    				$plan->save();
    			}
    		}
    	}
    	$this->redirect("workplans.php");
    }
    /**
     * Выбор учебного плана для копирования одной рабочей программы
     */
    public function actionSelectCorriculum() {
    	$plan = CWorkPlanManager::getWorkplan(CRequest::getInt("id"));
    	$items = array();
    	foreach (CCorriculumsManager::getAllCorriculums()->getItems() as $corriculum) {
    		$items[$corriculum->getId()] = $corriculum->title;
    	}
    	$this->addActionsMenuItem(array(
    		array(
    			"title" => "Назад",
    			"link" => "workplans.php?action=edit&id=".CRequest::getInt("id"),
    			"icon" => "actions/edit-undo.png"
    		)
    	));
    	$this->setData("items", $items);
    	$this->setData("plan", $plan);
    	$this->renderView("_corriculum/_workplan/workplan/select.tpl");
    }
    /**
     * Выбор дисциплины из выбранного учебного плана 
     * для копирования одной рабочей программы
     */
    public function actionCopyWorkPlan() {
    	$plan = CWorkPlanManager::getWorkplan(CRequest::getInt("id"));
    	$corriculum = CCorriculumsManager::getCorriculum(CRequest::getInt("corriculum_id"));
    	$items = array();
    	foreach ($corriculum->getDisciplines() as $discipline) {
    		$items[$discipline->getId()] = $discipline->discipline->getValue();
    	}
    	$this->addActionsMenuItem(array(
    		array(
    			"title" => "Назад",
    			"link" => "workplans.php?action=selectCorriculum&id=".CRequest::getInt("id"),
    			"icon" => "actions/edit-undo.png"
    		)
    	));
    	$this->setData("items", $items);
    	$this->setData("plan", $plan);
    	$this->renderView("_corriculum/_workplan/workplan/copy.tpl");
    }
    /**
     * Копирование одной выбранной рабочей программы
     */
    public function actionCopy() {
    	$plan = CWorkPlanManager::getWorkplan(CRequest::getInt("id"));
    	$newPlan = $plan->copy();
    	$newPlan->corriculum_discipline_id = CRequest::getInt("corriculum_discipline_id");
    	$discipline = CCorriculumsManager::getDiscipline(CRequest::getInt("corriculum_discipline_id"));
    	if (!is_null($discipline->discipline)) {
    		$newPlan->discipline_id = $discipline->discipline->getId();
    	}
    	$newPlan->save();
    	/**
    	 * Редирект на страницу со списком
    	 */
    	$this->redirect("workplans.php?action=index");
    }
    /**
     * Копирование списка рабочих программ в другой учебный план.
     * Выбор учебного плана
     */
    public function actionCorriculumToCopy() {
    	$items = array();
    	foreach (CCorriculumsManager::getAllCorriculums()->getItems() as $corriculum) {
    		$items[$corriculum->getId()] = $corriculum->title;
    	}
    	$this->addActionsMenuItem(array(
    		array(
    			"title" => "Назад",
    			"link" => "workplans.php?action=index",
    			"icon" => "actions/edit-undo.png"
    		)
    	));
    	$this->setData("items", $items);
    	$this->setData("plans", CRequest::getString("selected"));
    	$this->renderView("_corriculum/_workplan/workplan/copyCorriculum.tpl");
    }
    /**
     * Копирование списка рабочих программ в другой учебный план
     */
    public function actionCopyInCorriculum() {
    	$plans = explode(":", CRequest::getString("plans"));
    	$corriculum = CCorriculumsManager::getCorriculum(CRequest::getInt("corriculum_id"));
    	foreach ($plans as $id) {
    		$plan = CWorkPlanManager::getWorkplan($id);
    		foreach ($corriculum->getDisciplines() as $discipline) {
    			if ($plan->corriculumDiscipline->discipline->getValue() == $discipline->discipline->getValue()) {
    				$newPlan = $plan->copy();
    				$newPlan->corriculum_discipline_id = $discipline->getId();
    				$newPlan->save();
    			}
    		}
    	}
    	$this->redirect("workplans.php");
    }
    /**
     * Добавление литературы.
     * Сначала надо выбрать тип литературы
     */
    public function actionAddLiterature() {
    	$items = new CArrayList();
    	$result = array(1=>"Основная литература", 2=>"Дополнительная литература", 3=>"Интернет-ресурсы");
    	foreach ($result as $key=>$value) {
    		$items->add($key, $value);
    	}
    	$this->addActionsMenuItem(array(
    		array(
    			"title" => "Назад",
    			"link" => "workplans.php?action=edit&id=".CRequest::getInt("plan_id"),
    			"icon" => "actions/edit-undo.png"
    		)
    	));
    	$this->setData("items", $items);
    	$this->setData("plan_id", CRequest::getInt("plan_id"));
    	$this->renderView("_corriculum/_workplan/workplan/addLiterature.tpl");
    	//$this->renderView("_flow/pickList.tpl", get_class($this), "AddLiterature_Select");
    }
    /**
     * Добавление литературы.
     * Выбор литературы
     */
    public function actionAddLiterature_Select() {
    	$selected = CRequest::getArray("selected");
    	$type = $selected[0];
    	$plan = CWorkPlanManager::getWorkplan(CRequest::getInt("plan"));
    	$codeDiscipl = $plan->corriculumDiscipline->discipline->getId();
    	$result = array();
    	$query = new CQuery();
    	$query->select("books.id as id, books.book_name as name")
	    	->from(TABLE_CORRICULUM_BOOKS." as books")
	    	->innerJoin(TABLE_DISCIPLINES_BOOKS." as disc_books", "books.id = disc_books.book_id")
	    	->condition("disc_books.subject_id = ".$codeDiscipl);
    	foreach ($query->execute()->getItems() as $item) {
    		$result[$item["id"]] = $item["name"];
    	}
    	$items = new CArrayList();
    	foreach ($result as $key=>$value) {
    		$items->add($key, $value);
    	}
    	$this->addActionsMenuItem(array(
    		array(
    			"title" => "Назад",
    			"link" => "workplans.php?action=addLiterature&plan_id=".CRequest::getInt("plan"),
    			"icon" => "actions/edit-undo.png"
    		)
    	));
    	$this->setData("plan", CRequest::getInt("plan"));
    	$this->setData("type", $type);
    	$this->setData("items", $items);
    	$this->renderView("_corriculum/_workplan/workplan/addLiteratureSelect.tpl");
    	//$this->renderView("_flow/pickList.tpl", get_class($this), "SaveLiterature");
    }
    public function actionSaveLiterature() {
    	$selected = CRequest::getArray("selected");
    	foreach ($selected as $literature) {
    		$object = new CWorkPlanLiterature();
    		$object->plan_id = CRequest::getInt("plan");
    		$object->type = CRequest::getInt("type");
    		$plan = CWorkPlanManager::getWorkplan(CRequest::getInt("plan"));
    		if ($object->type == 1) {
    			$object->ordering = $plan->baseLiterature->getCount() + 1;
    		} elseif($object->type == 2) {
    			$object->ordering = $plan->additionalLiterature->getCount() + 1;
    		} elseif($object->type == 3) {
    			$object->ordering = $plan->internetResources->getCount() + 1;
    		}
    		$object->book_id = $literature;
    		$object->save();
    	}
    	$this->redirect("workplans.php?action=edit&id=".CRequest::getInt("plan"));
    }
    /**
     * Смена статуса комментария к файлу рабочей программы
     */
    public function actionUpdateCommentFile() {
    	$result = CWorkPlanManager::updateStatusWorkplan(CRequest::getInt("id"), "comment_file_workplan", "comment_file", "commentFile");
    	echo json_encode($result);
    }
    /**
     * Смена статуса на портале рабочей программы
     */
    public function actionUpdateStatusOnPortal() {
    	$result = CWorkPlanManager::updateStatusWorkplan(CRequest::getInt("id"), "status_workplan_on_portal", "status_on_portal", "statusOnPortal");
    	echo json_encode($result);
    }
    /**
     * Смена статуса библиотеки рабочей программы
     */
    public function actionUpdateStatusWorkPlanLibrary() {
    	$result = CWorkPlanManager::updateStatusWorkplan(CRequest::getInt("id"), "status_workplan_library", "status_workplan_library", "statusWorkplanLibrary");
    	echo json_encode($result);
    }
    /**
     * Смена статуса преподавателя рабочей программы
     */
    public function actionUpdateStatusWorkPlanLecturer() {
    	$result = CWorkPlanManager::updateStatusWorkplan(CRequest::getInt("id"), "status_workplan", "status_workplan_lecturer", "statusWorkplanLecturer");
    	echo json_encode($result);
    }
    /**
     * Смена статуса зав. каф. рабочей программы
     */
    public function actionUpdateStatusWorkPlanHeadOfDepartment() {
    	$result = CWorkPlanManager::updateStatusWorkplan(CRequest::getInt("id"), "status_workplan", "status_workplan_head_of_department", "statusWorkplanHeadOfDepartment");
    	echo json_encode($result);
    }
    /**
     * Смена статуса НМС рабочей программы
     */
    public function actionUpdateStatusWorkPlanNMS() {
    	$result = CWorkPlanManager::updateStatusWorkplan(CRequest::getInt("id"), "status_workplan", "status_workplan_nms", "statusWorkplanNMS");
    	echo json_encode($result);
    }
    /**
     * Смена статуса декана рабочей программы
     */
    public function actionUpdateStatusWorkPlanDean() {
    	$result = CWorkPlanManager::updateStatusWorkplan(CRequest::getInt("id"), "status_workplan", "status_workplan_dean", "statusWorkplanDean");
    	echo json_encode($result);
    }
    /**
     * Смена статуса проректора рабочей программы
     */
    public function actionUpdateStatusWorkPlanProrektor() {
    	$result = CWorkPlanManager::updateStatusWorkplan(CRequest::getInt("id"), "status_workplan", "status_workplan_prorektor", "statusWorkplanProrektor");
    	echo json_encode($result);
    }
}