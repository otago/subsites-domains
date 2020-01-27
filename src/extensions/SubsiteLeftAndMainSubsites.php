<?php

namespace OP;

use SilverStripe\Subsites\State\SubsiteState;
use SilverStripe\Control\Controller;
use SilverStripe\Subsites\Extensions\LeftAndMainSubsites;

/**
 * Hack for Single Sign On and subsites. Authentication can only work on one domain,
 * so we check to see if it's on a subsite, and if it is point it to where $_FILE_TO_URL_MAPPING
 * lives.
 */
class SubsiteLeftAndMainSubsites extends LeftAndMainSubsites {

    /**
     * Detect if you're trying to access the admin section from the non main site
     * @global string $_FILE_TO_URL_MAPPING 
     * @global string $_SERVER['REQUEST_URI'] this will be modified (bad practice warning)
     * @return redirect location
     */
    public function onBeforeInit() {
        if (!$this->owner->canAccess()) {
            global $_FILE_TO_URL_MAPPING;
            // redirect to domain.com/admin etc if there's the base path is set & you're in a subsite
            if (SubsiteState::singleton()->getSubsiteId()  != 0 && isset($_FILE_TO_URL_MAPPING[BASE_PATH])) {
                $serverurl = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING);
                $controller = Controller::curr();

                if (strpos($serverurl, '/admin') === 0 && !$controller->getRequest()->getVar('SubsiteID')) {

                    // redirect to the correct site admin location
                    $url = Controller::join_links($_FILE_TO_URL_MAPPING[BASE_PATH],
                                    $serverurl,
                                    "?SubsiteID=" . SubsiteState::singleton()->getSubsiteId() );
                    return Controller::curr()->redirect($url);
                }
            }
        }
        if (Controller::curr()->redirectedTo()) return null;
        return parent::onBeforeInit();
    }

}
