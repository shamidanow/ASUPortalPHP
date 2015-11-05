<?php
/**
 * Created by PhpStorm.
 * User: abarmin
 * Date: 13.03.15
 * Time: 21:51
 */

class CWorkPlanManager {
	private static $_cacheWorkPlans = null;
	private static $_cacheWorkPlansInit = false;
    /**
     * @param $id
     * @return CWorkPlan
     */
    public static function getWorkplan($id) {
        $plan = null;
        $ar = CActiveRecordProvider::getById(TABLE_WORK_PLANS, $id);
        if (!is_null($ar)) {
            $plan = new CWorkPlan($ar);
        }
        return $plan;
    }

    /**
     * @param $id
     * @return CWorkPlanCompetention
     */
    public static function getWorkplanCompetention($id) {
        $competention = null;
        $ar = CActiveRecordProvider::getById(TABLE_WORK_PLAN_COMPETENTIONS, $id);
        if (!is_null($ar)) {
            $competention = new CWorkPlanCompetention($ar);
        }
        return $competention;
    }
    /**
     * Кэш рабочих программ
     * @return CArrayList
     */
    public static function getCacheWorkPlans() {
    	if (is_null(self::$_cacheWorkPlans)) {
    		self::$_cacheWorkPlans = new CArrayList();
    	}
    	return self::$_cacheWorkPlans;
    }
    /**
     * Все рабочие программы
     * @return CArrayList
     */
    public static function getAllWorkPlans() {
    	if (!self::$_cacheWorkPlansInit) {
    		self::$_cacheWorkPlansInit = true;
    		foreach (CActiveRecordProvider::getAllFromTable(TABLE_WORK_PLANS, "title asc")->getItems() as $ar) {
    			$plan = new CWorkPlan($ar);
    			self::getCacheWorkPlans()->add($plan->getId(), $plan);
    		}
    	}
    	return self::getCacheWorkPlans();
    }
}