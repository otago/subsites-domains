<?php

namespace OP;

use SilverStripe\View\Parsers\ShortcodeParser;

ShortcodeParser::get('default')->register('subsite_link', [ModalControllerExtension::class, 'link_shortcode_handler']);

