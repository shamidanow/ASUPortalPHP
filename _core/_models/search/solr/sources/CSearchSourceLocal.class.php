<?php
/**
 * Created by PhpStorm.
 * User: abarmin
 * Date: 14.04.16
 * Time: 20:24
 */

class CSearchSourceLocal extends CComponent implements ISearchSource {
    public $path;
    public $id;
    public $suffix;

    private function scanDirectory() {
        return CFileUtils::getListFiles($this->path);
    }

    /**
     * Получение файлов для индексирования
     *
     * @param CSearchSettings $coreId
     */
    public function getFilesToIndex(CSearchSettings $coreId) {
    	/**
    	 * Получаем настройки коллекции Solr
    	 */
    	foreach ($coreId->getSearchSettingsList() as $setting) {
    		if ($setting->getAlias() == $this->suffix) {
    			$this->suffix = $setting->getValue();
    		}
    		if ($setting->getAlias() == $this->path) {
    			$this->path = $setting->getValue();
    		}
    	}
        $files = $this->scanDirectory();
        $filelist = array();
        $suffixes = explode(";", $this->suffix);
        foreach ($files as $file) {
        	$extension = end(explode(".", $file));
        	if (in_array($extension, $suffixes)) {
        		$filelist[] = $file;
        	}
        }
        return new CSearchSourceLocalIterator($filelist, $this);
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * @return CSearchFile
     */
    public function getFile(CSearchFile $fileDescriptor) {
        return $fileDescriptor;
    }

}