<?php

namespace OP;

use SilverStripe\View\Requirements;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Controller;
use SilverStripe\Subsites\Forms\SubsitesTreeDropdownField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Core\Convert;

//  HTMLEditorField_Toolbar was replaced With SilverStripe\Admin\ModalController in SS4.0
class ModalControllerExtension extends Extension {

    /**
     * Add the "another subsite" to the list of link types
     * @param Form $form
     */
    public function updateLinkForm(&$form) {
        $fields = $form->Fields();

        $internaltype = $fields->dataFieldByName('LinkType');
        $sources = $internaltype->getSource();
        $sources['subsite'] = 'Another subsite';
        $internaltype->setSource($sources);
        $pageSelectionField = SubsitesTreeDropdownField::create("HTMLEditorSubsite", _t('VirtualPage.CHOOSE', "Choose a page to link to"), "SiteTree", "ID", "MenuTitle");

        // fetch the subsite paramater from the GET paramater
        $subsiteID = Controller::curr() ? Controller::curr()->getRequest()->getVar('HTMLEditorSubsite_SubsiteID') : 0;

        $pageSelectionField->setSubsiteID($subsiteID);
        $pageSelectionField->setForm($form);
        $pageSelectionField->addExtraClass('subsiteSubSelectionTree');
        $fields->insertAfter($pageSelectionField, 'Description');
        $fields->insertAfter(DropdownField::create('HTMLEditorSubsite_SubsiteID', 'Link across subsite', $this->listSubSites()), 'Description');
        Requirements::javascript('otago/subsites-domains: javascript/subsites-domains.js');
    }

    /**
     * returns an array of Subsite IDs for the dropdown menu for linking
     * @return array
     */
    public function listSubSites() {
        $retarray = array();

        $retarray[0] = 'Default website';

        foreach (Subsite::get() as $subsite) {
            $retarray[$subsite->ID] = $subsite->getTitle();
        }

        return $retarray;
    }

    /**
     * Modify the content to include subsite links
     * @global array $subsiteDomainIDs
     * @param array $arguments
     * @param string $content
     * @param type $parser
     * @return string links
     */
    static public function link_shortcode_handler($arguments, $content = null, $parser = null) {
        if (!isset($arguments['id']))
            return;
        $argumentarray = explode('-', $arguments['id']);

        if (count($argumentarray) != 2)
            return;
        $subsiteid = $argumentarray[0];
        $id = $argumentarray[1];
        $page = null;

        if ($id) {
            $page = DataObject::get_by_id(SiteTree::class, $id);   // Get the current page by ID.
            if (!$page) {
                $page = Versioned::get_latest_version(SiteTree::class, $id); // Attempt link to old version.
            }
        } else {
            $page = DataObject::get_one(ErrorPage::class, '"ErrorPage"."ErrorCode" = \'404\''); // Link to 404 page.
        }

        if (!$page) {
            return; // There were no suitable matches at all.
        }

        $currentSubsite = Subsite::get()->byID((int) $subsiteid);
        $currenturl = null;

        if ($currentSubsite) {
            if (Director::isDev()) {
                $currenturl = $currentSubsite->DevDomainID ? $currentSubsite->DevDomain() : null;
            }
            if (Director::isTest()) {
                $currenturl = $currentSubsite->TestDomainID ? $currentSubsite->TestDomain() : null;
            }
            if (!$currenturl) {
                $currenturl = $currentSubsite->getPrimarySubsiteDomain();
            }
            $currenturl = $currenturl->getFullProtocol() . $currenturl->Domain;

            // override
            if (Director::isDev() || Director::isTest()) {
                if (Environment::getEnv('DEV_SUBSITE_' . (int) $subsiteid)) {
                    $subsiteurl = 'DEV_SUBSITE_' . (int) $subsiteid;
                    $currenturl = Environment::getEnv($subsiteurl);
                }
            }
        }
        if(!$page->Parent()) {
            return "";
        }
        $link = Convert::raw2att($page->Link());

        if ($content) {
            return sprintf('<a href="%s" class="op__link">%s</a>', $currenturl . $link, $parser->parse($content));
        } else {
            return $currenturl . $link;
        }
    }

}
