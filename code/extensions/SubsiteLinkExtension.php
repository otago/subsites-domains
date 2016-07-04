<?php

class SubsiteLinkExtension extends DataExtension {

	private static $has_one = array(
		'TestDomain' => 'SubsiteDomain',
		'DevDomain' => 'SubsiteDomain'
	);

	public function updateCMSFields(FieldList $fields) {

		if ($this->owner->ID != 0) {
			$domains = $this->owner->Domains()->map('ID', 'Domain');

			if (Director::isDev() || Director::isTest()) {
				if (defined('DEV_SUBSITE_' . $this->owner->ID)) {
					$subsiteurl = 'DEV_SUBSITE_' . $this->owner->ID;
					$wardingfield = LiteralField::create("WarningDomain", "<p class=\"message warning\">While running in dev or test mode,"
									. " the current domain will be used: <strong>" . constant($subsiteurl) . "</strong></p>");
					$fields->addFieldToTab("Root.DomainEnvironments", $wardingfield);
				}
			}

			$fields->addFieldToTab('Root.DomainEnvironments', TextField::create('Live', 'Live', $this->owner->domain())->setReadonly(true));
			$fields->addFieldToTab('Root.DomainEnvironments', DropdownField::create('TestDomainID', 'Test', $domains));
			$fields->addFieldToTab('Root.DomainEnvironments', DropdownField::create('DevDomainID', 'Dev', $domains));
			$fields->makeFieldReadonly('Live');
		}
	}

}
