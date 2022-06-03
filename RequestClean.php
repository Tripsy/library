<?php
/**
 *
 * @version 1.0.0
 * @author n/a
 *
 */

namespace Tripsy\Library;

class RequestClean
{
    /*
     * Sanitizes data so that Cross Site Scripting Hacks can be prevented. This function is not 100% foolproof but is designed
     * to prevent even the most obscure XSS attempts. This function should only be used to deal with data upon submission.
     * It's not something that should be used for general runtime processing.
     */

    public function xss_clean($str)
    {
        if (is_array($str)) {
            foreach ($str as $_key => $_value)
                $str[$_key] = $this->xss_clean($_value);

            return $str;
        }

        //procedure
        $str = $this->remove_invisible_characters($str);
        $str = $this->_validate_entities($str);
        $str = rawurldecode($str);
        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);
        $str = preg_replace_callback('/<\w+.*?(?=>|<|$)/si', array($this, '_decode_entity'), $str);
        $str = $this->remove_invisible_characters($str);

        if (strpos($str, "\t") !== false) //convert tabs to spaces
            $str = str_replace("\t", ' ', $str);

        $str = $this->_remove_disabled($str);

        //compact exploded words
        $words = array('javascript', 'expression', 'vbscript', 'script', 'base64', 'applet', 'alert', 'document', 'write', 'cookie', 'window');

        foreach ($words as $word) {
            $temp = '';

            for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++) {
                $temp .= substr($word, $i, 1) . '\s*';
            }

            $str = preg_replace_callback('#(' . substr($temp, 0, -3) . ')(\W)#is', array($this, '_compact_exploded_words'), $str);
        }

        //remove disallowed Javascript in links or img tags
        do {
            $original = $str;

            if (preg_match('/<a/i', $str))
                $str = preg_replace_callback('#<a\s+([^>]*?)(>|$)#si', array($this, '_js_link_removal'), $str);

            if (preg_match('/<img/i', $str))
                $str = preg_replace_callback('#<img\s+([^>]*?)(\s?/?>|$)#si', array($this, '_js_img_removal'), $str);

            if (preg_match('/script/i', $str) or preg_match('/xss/i', $str))
                $str = preg_replace('#<(/*)(script|xss)(.*?)\>#si', '[removed]', $str);
        } while ($original != $str);

        unset($original);

        //remove evil attributes such as style, onclick and xmlns
        $str = $this->_remove_attributes($str);

        //sanitize naughty HTML elements
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $str = preg_replace_callback('#<(/*\s*)(' . $naughty . ')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

        //sanitize naughty scripting elements
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);

        //return
        return $this->_remove_disabled($str);
    }

    public function remove_invisible_characters($str, $url_encoded = true)
    {
        //vars
        $non_displayables = [];

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/';
            $non_displayables[] = '/%1[0-9a-f]/';
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';

        //replace
        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        //return
        return $str;
    }

    public function entity_decode($str, $charset = 'UTF-8')
    {
        /*
         * This function is a replacement for html_entity_decode()
         *
         * The reason we are not using html_entity_decode() by itself is because while it is not technically correct to leave
         * out the semicolon at the end of an entity most browsers will still interpret the entity correctly.
         * html_entity_decode() does not convert entities without semicolons
         */

        if (stristr($str, '&') === false)
            return $str;

        return html_entity_decode($str, ENT_COMPAT, $charset);
    }

    protected function _validate_entities($str)
    {
        //vars
        $xss_hash = md5(time());

        //protect GET variables in URLs
        $str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $xss_hash . "\\1=\\2", $str);

        //validate standard character entities
        $str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);

        //validate UTF16 two byte encoding (x00)
        $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $str);

        return str_replace($xss_hash, '&', $str);
    }

    protected function _decode_entity($match)
    {
        return $this->entity_decode($match[0]);
    }

    protected function _compact_exploded_words($matches) //callback function for xss_clean() to remove whitespace from things like j a v a s c r i p t
    {
        return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
    }

    protected function _sanitize_naughty_html($matches) //callback function for xss_clean() to remove naughty HTML elements
    {
        // encode opening brace
        $str = '&lt;' . $matches[1] . $matches[2] . $matches[3];

        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace(array('>', '<'), array('&gt;', '&lt;'),
            $matches[4]);

        return $str;
    }

    protected function _js_link_removal($match) //callback function for xss_clean() to sanitize links
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
                '',
                $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]
        );
    }

    protected function _js_img_removal($match) //callback function for xss_clean() to sanitize image tags
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
                '',
                $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]
        );
    }

    protected function _filter_attributes($str) //filters tag attributes for consistency and safety
    {
        $out = '';

        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
            foreach ($matches[0] as $match) {
                $out .= preg_replace('#/\*.*?\*/#s', '', $match);
            }
        }

        return $out;
    }

    protected function _convert_attribute($match) //callback for xss_clean() for attribute conversion
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }

    protected function _remove_attributes($str)
    {
        //vars
        $evil_attributes = array('on\w*', 'style', 'xmlns', 'formaction');

        do {
            $count = 0;
            $attribs = [];

            preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // find occurrences of illegal attribute strings without quotes
            preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }

            //replace illegal attribute strings that are inside a html tag
            if (count($attribs) > 0) {
                $str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)(' . implode('|', $attribs) . ')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
            }
        } while ($count);

        //return
        return $str;
    }

    protected function _remove_disabled($str)
    {
        $disabled_str = array(
            'document.cookie' => '[disabled]',
            'document.write' => '[disabled]',
            '.parentNode' => '[disabled]',
            '.innerHTML' => '[disabled]',
            'window.location' => '[disabled]',
            '-moz-binding' => '[disabled]',
            '<!--' => '&lt;!--',
            '-->' => '--&gt;',
            '<![CDATA[' => '&lt;![CDATA[',
            '<comment>' => '&lt;comment&gt;'
        );

        $disabled_regex = array(
            'javascript\s*:',
            'expression\s*(\(|&\#40;)', // CSS and IE
            'vbscript\s*:', // IE, surprise!
            'Redirect\s+302',
            "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
        );

        $str = str_replace(array_keys($disabled_str), $disabled_str, $str);

        foreach ($disabled_regex as $regex) {
            $str = preg_replace('#' . $regex . '#is', '[disabled]', $str);
        }

        return $str;
    }
}
