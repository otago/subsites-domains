<?php

namespace OP;

use SilverStripe\Forms\TextField;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Control\Director;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;
use SilverStripe\Core\Environment;
use SilverStripe\Subsites\State\SubsiteState;

class SubsiteSiteTreeURLSegmentField extends SiteTreeURLSegmentField {

    public function getAttributes() {
        if ($this->getPage()->isMainSite()) {
            return parent::getAttributes();
        }
        return array_merge(
                TextField::getAttributes(),
                array(
                    'data-prefix' => $this->getURLPrefix(),
                    'data-suffix' => '?stage=',
                    'data-default-url' => $this->getDefaultURL()
                )
        );
    }

    public function getURLPrefix() {
        $url = parent::getURLPrefix();

        if (Director::isDev() || Director::isTest()) {
            $urlarray = parse_url($url);

            // define override
            if (Environment::getEnv('DEV_SUBSITE_' . SubsiteState::singleton()->getSubsiteId())) {
                $subsiteurl = 'DEV_SUBSITE_' . SubsiteState::singleton()->getSubsiteId();
                return Environment::getEnv('DEV_SUBSITE_' . SubsiteState::singleton()->getSubsiteId()) . $urlarray['path'];
            }

            if (!(Subsite::currentSubsite() instanceof Subsite)) {
                return $url;
            }

            // if set in config settings
            $currentDomain = Subsite::currentSubsite()->getPrimarySubsiteDomain();

            if (Director::isTest()) {
                $currentDomain = Subsite::currentSubsite()->TestDomainID ? Subsite::currentSubsite()->TestDomain() : $currentDomain;
            }
            if (Director::isDev()) {
                $currentDomain = Subsite::currentSubsite()->DevDomainID ? Subsite::currentSubsite()->DevDomain() : $currentDomain;
            }

            if (!$currentDomain) {
                return $url;
            }

            return $currentDomain->getFullProtocol() . $currentDomain->Domain . $urlarray['path'];
        }
        return $url;
    }

}
