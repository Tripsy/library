<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Library\Exceptions\SystemException;

class Language
{
    private Config $cfg;
    private Session $session;

    private string $pattern;

    /**
     * @param Config $cfg
     * @param Session $session
     */
    public function __construct(Config $cfg, Session $session)
    {
        $this->cfg = $cfg;
        $this->session = $session;

        $this->pattern = $this->cfg->get('site.pattern');

        if ($this->cfg->get('debug.status') === true) {
            $this->session->set('lang', []);
        }
    }

    /**
     * Check if ang.{pattern}.{key} exists
     *
     * @param string $key
     * @param string $pattern
     *
     * @return bool
     * @throws SystemException
     */
    public function has(string $key, string $pattern = ''): bool
    {
        $pattern = $pattern ?: $this->pattern;

        $this->load($pattern);

        return $this->session->has('lang' . $pattern . '.' . $key);
    }

    /**
     * Return lang.{pattern}.{key_1}.{key_2} IF exists ELSE lang.{pattern}.general.{key_2} ELSE default IF set ELSE throws error
     *
     * @param string $key
     * @param $default
     * @param string $pattern
     * @return mixed|null
     * @throws SystemException
     */
    public function get(string $key, $default = null, string $pattern = '')
    {
        $pattern = $pattern ?: $this->pattern;

        $this->load($pattern);

        $output = $this->session->get('lang.' . $pattern . '.' . $key);

        if ($output) {
            return $output;
        }

        $key = preg_replace('/(\w+)\.(\w.+)/i', 'general.$2', $key);

        $output = $this->session->get('lang.' . $pattern . '.' . $key);

        if ($output) {
            return $output;
        }

        if ($default) {
            return $default;
        }

        if (empty($output)) {
            throw new SystemException('Language var (eg: ' . $pattern . '.' . $key . ') not defined');
        }

        return $output;
    }

    /**
     * Load language file
     *
     * @param string $pattern
     * @return void
     * @throws SystemException
     */
    private function load(string $pattern): void
    {
        if ($this->session->has('lang.' . $pattern) === true) {
            return;
        }

        $file_path = $this->cfg->get('path.config') . '/lang-' . $this->cfg->get('pattern.' . $pattern . '.language') . '.xml';

        if (file_exists($file_path) === false) {
            throw new SystemException('Language file (eg: ' . $file_path . ') not found');
        }

        $this->session->set('lang.' . $pattern, $this->parse_xml($file_path));
    }

    /**
     * Parse language file
     *
     * @param string $lang_path
     * @return array
     * @throws SystemException
     */
    private function parse_xml(string $lang_path): array
    {
        try {
            $lang_xml = new \SimpleXMLElement($lang_path, 0, true);
        } catch (\Exception $e) {
            throw new SystemException('XML file (eg: ' . $lang_path . ') could not be parsed: ' . $e->getMessage());
        }

        $lang_arr = [];

        foreach ($lang_xml->children() as $box) {
            $box_id = (string)$box['id'];

            if (!$box_id) {
                throw new SystemException('Check lang file (eg: ' . $lang_path . ') and make sure every box has <strong>id</strong> attribute');
            }

            if (array_key_exists($box_id, $lang_arr)) {
                throw new SystemException('Duplicate box id (eg: ' . $box_id . ') found in lang file (eg: ' . $lang_path . ')');
            }

            $lang_arr[$box_id] = [];

            foreach ($box->children() as $section) {
                $section_id = trim((string)$section['id']);

                if (!$section_id) {
                    throw new SystemException('Check lang file (eg: ' . $lang_path . ') and make sure every section (eg: ' . $box_id . ') has <strong>id</strong> attribute');
                }

                if (array_key_exists($section_id, $lang_arr[$box_id])) {
                    throw new SystemException('Duplicate section id (eg: ' . $box_id . ' -> ' . $section_id . ') found in lang file (eg: ' . $lang_path . ')');
                }

                if ($section->subsection) {
                    $lang_arr[$box_id][$section_id] = [];

                    foreach ($section->subsection as $subsection) {
                        $subsection_id = (string)$subsection['id'];

                        if (!$subsection_id) {
                            throw new SystemException('Check lang file (eg: ' . $lang_path . ') and make sure every section (eg: ' . $box_id . ' -> ' . $section_id . ') has <strong>id</strong> attribute');
                        }

                        if (array_key_exists($subsection_id, $lang_arr[$box_id][$section_id])) {
                            throw new SystemException('Duplicate subsection id (eg: ' . $box_id . ' -> ' . $section_id . ' -> ' . $subsection_id . ') found in lang file (eg: ' . $lang_path . ')');
                        }

                        $lang_arr[$box_id][$section_id][$subsection_id] = trim((string)$subsection);
                    }
                } else {
                    $lang_arr[$box_id][$section_id] = (string)$section;
                }
            }
        }

        return $lang_arr;
    }
}
