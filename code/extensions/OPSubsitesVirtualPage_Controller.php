<?php

/**
 * @see https://github.com/silverstripe/silverstripe-subsites/issues/235
 */
class OPSubsitesVirtualPage_Controller extends SubsitesVirtualPage_Controller {

	public function getViewer($action) {
		$controller = ModelAsController::controller_for($this->CopyContentFrom());
		$controller->init();
		return $controller->getViewer($action);
	}

}
