<?php

class PostImportStepExtension extends Extension {
	public $importedPages = array();

	public function onAfterImport($result) {
		$this->importedPages[] = $result->page;
	}
}