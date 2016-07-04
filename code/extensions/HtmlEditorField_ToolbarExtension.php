<?php

class HtmlEditorField_ToolbarExtension extends Extension {

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
		$subsiteID = Controller::curr()?  Controller::curr()->getRequest()->getVar('HTMLEditorSubsite_SubsiteID') : 0;
		
		$pageSelectionField->setSubsiteID($subsiteID);
		$pageSelectionField->setForm($form);
		$pageSelectionField->addExtraClass('subsiteSubSelectionTree');
		$fields->insertAfter($pageSelectionField, 'Description');
		$fields->insertAfter(DropdownField::create('HTMLEditorSubsite_SubsiteID', 'Link across subsite', $this->listSubSites()), 'Description');

		$fields->push(LiteralField::create('Script', '<script>' . $this->returnCustomJS() . '</script>'));
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
	 * insert the HTML into the template via a literal field. Not the best way,
	 * but the only way to do this.
	 * @return string
	 */
	public function returnCustomJS() {
		return <<<EOT
(function($) {
	$.entwine('ss', function($) {
		var currentSubsiteID = null;
		$('form.htmleditorfield-linkform').entwine({
			// when the Insert Link button is clicked
			redraw: function() {
				this._super();
				var linkType = this.find(':input[name=LinkType]:checked').val();
			
				this.find('[name=HTMLEditorSubsite_SubsiteID]').closest('.field').hide();
				this.find('[name=HTMLEditorSubsite]').closest('.field').hide();
		
				if(linkType == 'subsite') {
					$(this).find('.tree-holder *').remove();
					this.find('[name=HTMLEditorSubsite_SubsiteID]').closest('.field').show();
					this.find('[name=HTMLEditorSubsite]').closest('.field').show();
				}
			},
			// checks to see if the type of link is a subsite link
			getLinkAttributes: function () {
				result = this._super();
				var href, target = null;
				if(this.find(':input[name=TargetBlank]').is(':checked')) target = '_blank';
				
				var subsiteid = this.find('[name=HTMLEditorSubsite_SubsiteID]').val();
				var subsitepageid = this.find('[name=HTMLEditorSubsite]').val();
		
				if(this.find(':input[name=LinkType]:checked').val() == 'subsite' && subsitepageid) {
					$(this).find('.treedropdownfield-title').html("");
					return {
						href : '[subsite_link,id=' + subsiteid + '-' + subsitepageid + ']', 
						target : target, 
						title : this.find(':input[name=Description]').val()
					};
				}
				return result;
			},
			// extract the current subsiteid and page id from the content
			getCurrentLink:function () {
				currentLink = this._super();
				var selectedEl = this.getSelection();
				var linkDataSource = null;
				var href = null;
				if(selectedEl.length) {
					if(selectedEl.is('a')) {
						linkDataSource = selectedEl;
					} else {
						linkDataSource = selectedEl = selectedEl.parents('a:first');
					}
				}
				if(linkDataSource && linkDataSource.length) this.modifySelection(function(ed){
					ed.selectNode(linkDataSource[0]);
				});

				if (linkDataSource) {
					href = linkDataSource.attr('href');
		
					if(href) {
						href = this.getEditor().cleanLink(href, linkDataSource);
					}
					title = linkDataSource.attr('title');
					target = linkDataSource.attr('target');
				}
				if (!linkDataSource.attr('href')) linkDataSource = null;
		
				if(href) {
					if(href.match(/^\[subsite_link(?:\s*|%20|,)?id=?([0-9]+)\-?([0-9]+)\]?(#.*)?$/i)) {
						subsiteid = RegExp.$1 ? RegExp.$1 : '';
						pageid = RegExp.$2 ? RegExp.$2 : '';
		
						$(this).find('[name=HTMLEditorSubsite_SubsiteID]').val(subsiteid);
						$(this).find('[name=HTMLEditorSubsite]').val(pageid);
		
						currentSubsiteID = subsiteid;
						return {
							LinkType: 'subsite',
							Subsite: subsiteid,
							SubsiteLink: pageid,
							Description: title,
							TargetBlank: target ? true : false
						};
					}
				}
				return currentLink;
			}
		});
	});
})(jQuery);
EOT;
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
			$page = DataObject::get_by_id('SiteTree', $id);   // Get the current page by ID.
			if (!$page) {
				$page = Versioned::get_latest_version('SiteTree', $id); // Attempt link to old version.
			}
		} else {
			$page = DataObject::get_one('ErrorPage', '"ErrorPage"."ErrorCode" = \'404\''); // Link to 404 page.
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
			if ( Director::isTest()) {
				$currenturl = $currentSubsite->TestDomainID ? $currentSubsite->TestDomain() : null;
			}
			if (!$currenturl) {
				$currenturl = $currentSubsite->getPrimarySubsiteDomain();
			}
			$currenturl = $currenturl->getFullProtocol() . $currenturl->Domain;
			
			// override
			if (Director::isDev() || Director::isTest()) {
				if (defined('DEV_SUBSITE_' . (int) $subsiteid)) {
					$subsiteurl = 'DEV_SUBSITE_' . (int) $subsiteid;
					$currenturl = constant($subsiteurl);
				}
			}
		}
		

		$link = Convert::raw2att($page->Link());

		if ($content) {
			return sprintf('<a href="%s">%s</a>', $currenturl . $link, $parser->parse($content));
		} else {
			return $currenturl . $link;
		}
	}

}
