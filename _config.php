<?php

define('SUBSITES_DOMAINS_DIR', ltrim(Director::makeRelative(realpath(__DIR__)), DIRECTORY_SEPARATOR));


Object::add_extension('HtmlEditorField_Toolbar', 'HtmlEditorField_ToolbarExtension');
ShortcodeParser::get('default')->register('subsite_link', array('HtmlEditorField_ToolbarExtension', 'link_shortcode_handler'));
