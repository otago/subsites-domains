(function ($) {
	$.entwine('ss', function ($) {
		var currentSubsiteID = null;
		
		// on selecting a subsite
		$('#HTMLEditorSubsite_SubsiteID').entwine({
			onchange:function(){
				$('.subsitestreedropdown .treedropdownfield-panel ul').remove();
				$('.subsitestreedropdown .treedropdownfield-title').html('');
				currentSubsiteID = null;
			}
		});
		
		$('form.htmleditorfield-linkform').entwine({
			// when the Insert Link button is clicked
			redraw: function () {
				this._super();
				var linkType = this.find(':input[name=LinkType]:checked').val();

				this.find('[name=HTMLEditorSubsite_SubsiteID]').closest('.field').hide();
				this.find('[name=HTMLEditorSubsite]').closest('.field').hide();

				if (linkType == 'subsite') {
					$(this).find('.tree-holder *').remove();
					this.find('[name=HTMLEditorSubsite_SubsiteID]').closest('.field').show();
					this.find('[name=HTMLEditorSubsite]').closest('.field').show();
				}
			},
			// checks to see if the type of link is a subsite link
			getLinkAttributes: function () {
				result = this._super();
				var href, target = null;
				if (this.find(':input[name=TargetBlank]').is(':checked'))
					target = '_blank';

				var subsiteid = this.find('[name=HTMLEditorSubsite_SubsiteID]').val();
				var subsitepageid = this.find('[name=HTMLEditorSubsite]').val();

				if (this.find(':input[name=LinkType]:checked').val() == 'subsite' && subsitepageid) {
					$(this).find('.treedropdownfield-title').html("");
					return {
						href: '[subsite_link,id=' + subsiteid + '-' + subsitepageid + ']',
						target: target,
						title: this.find(':input[name=Description]').val()
					};
				}
				return result;
			},
			// extract the current subsiteid and page id from the content
			getCurrentLink: function () {
				currentLink = this._super();
				var selectedEl = this.getSelection();
				var linkDataSource = null;
				var href = null;
				if (selectedEl.length) {
					if (selectedEl.is('a')) {
						linkDataSource = selectedEl;
					} else {
						linkDataSource = selectedEl = selectedEl.parents('a:first');
					}
				}
				if (linkDataSource && linkDataSource.length)
					this.modifySelection(function (ed) {
						ed.selectNode(linkDataSource[0]);
					});

				if (linkDataSource) {
					href = linkDataSource.attr('href');

					if (href) {
						href = this.getEditor().cleanLink(href, linkDataSource);
					}
					title = linkDataSource.attr('title');
					target = linkDataSource.attr('target');
				}
				if (!linkDataSource.attr('href'))
					linkDataSource = null;

				if (href) {
					if (href.match(/^\[subsite_link(?:\s*|%20|,)?id=?([0-9]+)\-?([0-9]+)\]?(#.*)?$/i)) {
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