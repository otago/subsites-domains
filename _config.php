<?php

use SilverStripe\View\Parsers\ShortcodeParser;

//Object::add_extension('HtmlEditorField_Toolbar', 'HtmlEditorField_ToolbarExtension');
ShortcodeParser::get('default')->register('subsite_link', '\\OP\\ModalControllerExtension::link_shortcode_handler');



