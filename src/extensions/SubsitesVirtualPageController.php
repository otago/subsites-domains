<?php

namespace OP;

use SilverStripe\CMS\Controllers\ModelAsController;
use GraphQL\Type\Definition\Config;
use SilverStripe\Control\Controller;

/**
 * @see https://github.com/silverstripe/silverstripe-subsites/issues/235
 */
class SubsitesVirtualPageController extends Controller {

    public function getViewer($action) {
        $controller = ModelAsController::controller_for($this->CopyContentFrom());
        $controller->init();
        return $controller->getViewer($action);
    }

    public function allowedActions($limitToClass = null) {
        $classname = get_class(ModelAsController::controller_for($this->CopyContentFrom()));
        $extraactions = Config::inst()->get(
                $classname, 'allowed_actions', Config::UNINHERITED | Config::EXCLUDE_EXTRA_SOURCES
        );
        return array_merge(parent::allowedActions($limitToClass), $extraactions);
    }

}
