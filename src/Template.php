<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Library\Exceptions\TemplateException;
use Tripsy\Library\Standard\ArrayTools;
use Tripsy\Library\Standard\StringTools;

class Template
{
    private Config $cfg;

    private array $tags = [];
    private array $iterations = [];
    private string $html = '';
    private array $inject = [
        'prepend' => '', // insert content at the beginning of $this->html
        'append' => '', // insert content at the end of $this->html
    ];

    /**
     * @param Config $cfg
     */
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    /**
     * @param string $source
     * @param string $type
     * @return $this
     * @throws TemplateException
     */
    public function load(string $source, string $type = 'file_relative'): self
    {
        switch ($type) {
            case 'file_relative': //'modules/article/list'
                $this->html = $this->getContent($this->cfg->get('path.template') . '/' . $source . '.html');
                break;
            case 'file_absolute': //public_html/templates/default/email/sys_report.html'
                $this->html = $this->getContent($source);
                break;
            case 'html': //string
                $this->html = $source;
                break;
            default:
                throw new TemplateException('Data source is not defined (eg: ' . $type . ')');
        }

        return $this;
    }

    /**
     * @return string
     * @throws TemplateException
     */
    public function parse(): string
    {
        $this->processConditions();

        if (count($this->iterations) > 0) {
            $this->process_iterations();
        }

        $this->processTags();

        $this->process_injects();

        return $this->html;
    }

    /**
     * @return void
     * @throws TemplateException
     */
    public function output()
    {
        print($this->parse());
    }

    /**
     * Notes:
     *
     *      If $param is array => $data ~ prefix
     *      If $param is array => $k ~ template tag name
     *      If $param is array => $v ~ template tag value
     *
     *      If $value is array and first key is integer add iteration name $param
     *      If $value is array and first key is strings add tags with name $param.$k
     *
     * @param string|array $param
     * @param mixed|null $data
     * @return $this
     */
    public function assign(string|array $param, mixed $data = null): self
    {
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                $this->assign($data . $k, $v);
            }
        } elseif (is_array($data)) {
            $key = ArrayTools::key_by_index($data);

            if ((is_int($key) || !$key)) {
                $this->iterations[$param] = $data;
            } else {
                foreach ($data as $k => $v) {
                    $this->tags[$param . '.' . $k] = $v;
                }
            }
        } else {
            $this->tags[$param] = $data;
        }

        return $this;
    }

    /**
     * @param string $string
     * @param string $position
     * @return $this
     */
    public function inject(string $string, string $position = 'prepend'): self
    {
        $this->inject[$position] .= $string;

        return $this;
    }

    /**
     * @return string
     * @throws TemplateException
     */
    private function processForeach(): string
    {
        $this->processConditions();

        if (count($this->iterations) > 0) {
            $this->process_iterations();
        }

        $this->processTags();

        return $this->html;
    }

    /**
     * @param string $iteration_code
     * @param array $iteration_entry
     * @return string
     * @throws TemplateException
     */
    private function iteration(string $iteration_code, array $iteration_entry): string
    {
        $template = new self($this->cfg);
        $template->load($iteration_code, 'html');
        $template->assign($iteration_entry);

        return $template->processForeach();
    }

    /**
     * @return void
     * @throws TemplateException
     */
    private function process_iterations()
    {
        foreach ($this->iterations as $iteration_name => $iteration_values) {
            $iteration_data = $this->getContentBetweenTags($this->html, 'foreach', 'foreach ' . $iteration_name);

            if ($iteration_data['content']) {
                $iteration_content = '';

                foreach ($iteration_values as $iteration_entry) {
                    if (is_array($iteration_entry) === false) {
                        $iteration_entry = [
                            'value' => $iteration_entry
                        ];
                    }

                    $iteration_content .= $this->iteration($iteration_data['content'], $iteration_entry);
                }

                $this->html = substr_replace($this->html, $iteration_content, $iteration_data['start'], $iteration_data['length']);
            }
        }
    }

    /**
     * @return void
     */
    private function processConditions(): void
    {
        preg_match_all('/{condition name="(.*?)" (value|is)="(.*?)"}/is', $this->html, $output);
        //output[0] -> {condition name="color" value="red"}
        //output[1] -> color
        //output[2] -> value
        //output[3] -> red

        foreach ($output[1] as $condition_key => $condition_name) {

            switch ($output[2][$condition_key]) {
                case 'is':
                    if ((!array_key_exists($condition_name, $this->tags) && !array_key_exists($condition_name, $this->iterations)) || strpos($this->html, $output[0][$condition_key]) === false)
                        continue 2;

                    $condition_value = $output[3][$condition_key];

                    $condition_data = $this->getContentBetweenTags($this->html, 'condition', 'condition name="' . $condition_name . '" is="' . $condition_value . '"');
                    $template_value = !empty($this->tags[$condition_name]) || !empty($this->iterations[$condition_name]) ? 'true' : 'false';

                    $condition_content = ($template_value == $condition_value) ? $condition_data['content'] : null;

                    $this->html = substr_replace($this->html, $condition_content, $condition_data['start'], $condition_data['length']);
                    break;

                case 'value':
                    if (!array_key_exists($condition_name, $this->tags) || !str_contains($this->html, $output[0][$condition_key]))
                        continue 2;

                    $condition_value = $output[3][$condition_key];
                    $condition_data = $this->getContentBetweenTags($this->html, 'condition', 'condition name="' . $condition_name . '" value="' . $condition_value . '"');

                    if (is_bool($this->tags[$condition_name])) {
                        $template_value = $this->tags[$condition_name] ? 'true' : 'false';
                    } else {
                        $template_value = $this->tags[$condition_name];
                    }

                    $condition_content = ($template_value == $condition_value) ? $condition_data['content'] : null;

                    $this->html = substr_replace($this->html, $condition_content, $condition_data['start'], $condition_data['length']);
                    break;
            }
        }
    }

    /**
     * @return void
     */
    private function processTags()
    {
        $this->html = StringTools::interpolate($this->html, $this->tags);
    }

    /**
     * @return void
     */
    private function process_injects()
    {
        $this->html = $this->inject['prepend'] . $this->html . $this->inject['append'];
    }

    /**
     * @param string $data
     * @return string
     * @throws TemplateException
     */
    private function getContent(string $data): string
    {
        if (file_exists($data)) {
            return file_get_contents($data);
        } else {
            throw new TemplateException('The template file <strong>' . $data . '</strong> does not exist');
        }
    }

    /**
     * @param string $source
     * @param string $tag
     * @param string $idTag
     * @return array
     */
    private function getContentBetweenTags(string $source, string $tag, string $idTag): array
    {
        $delimiter_start = '{';
        $delimiter_end = '}';
        $result = [
            'start' => '',
            'length' => '',
            'content' => ''
        ];

        $startTag = $delimiter_start . $tag;
        $startTagLength = strlen($startTag);
        $endTag = $delimiter_start . '/' . $tag . $delimiter_end;
        $endTagLength = strlen($endTag);
        $idTag = $delimiter_start . $idTag . $delimiter_end;
        $idTagLength = strlen($idTag);
        $startPosition = strpos($source, $idTag);
        $endPosition = 0;

        if ($startPosition !== false) {
            $stop = false;
            $startTagPos = $startPosition;
            $endTagPos = strpos($source, $endTag, $startTagPos + $startTagLength);

            while ($stop === false) {
                $startTagPos = strpos($source, $startTag, $startTagPos + $startTagLength);

                if ($startTagPos === false || $startTagPos > $endTagPos) {
                    $stop = true;
                    $endPosition = $endTagPos + $endTagLength;
                } else {
                    $endTagPos = strpos($source, $endTag, $endTagPos + $startTagLength);
                }
            }

            $result['start'] = $startPosition;
            $result['length'] = $endPosition - $startPosition;
            $result['content'] = substr($source, $startPosition + $idTagLength, $endPosition - $startPosition - $idTagLength - $endTagLength);
        }

        return $result;
    }
}
