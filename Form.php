<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Exceptions\TemplateException;
use Tripsy\Library\Standard\ArrayTools;
use Tripsy\Library\Standard\StringTools;

class Form
{
    /**
     * Build <option> list for <select> element
     *
     * @param array $data
     * @param array $config
     * @return string
     */
    public function selectOption(array $data, array $config = []): string
    {
        $config = (object)array_merge(array(
            'placeholder' => false,
            'selected' => '',
            'default' => '',
        ), $config);

        $config->selected = $config->selected ?: $config->default;

        $output = $config->placeholder ? '<option value=""></option>' : '';

        foreach ($data as $k => $v) {
            $output .= '<option value="' . $k . '">' . $v . '</option>';
        }

        if ($config->selected) {
            if (is_array($config->selected)) {
                foreach ($config->selected as $v) {
                    $output = str_replace('<option value="' . $v . '">', '<option value="' . $v . '" selected="selected">', $output);
                }
            } else {
                $output = str_replace('<option value="' . $config->selected . '">', '<option value="' . $config->selected . '" selected="selected">', $output);
            }
        }

        return $output;
    }

    public function radioOption(array $data, array $config = []): array
    {
        $config = (object)array_merge(array(
            'selected' => '',
            'default' => '',
            'require_selected' => true
        ), $config);

        $config->selected = $config->selected ?: $config->default;

        if ($config->require_selected) {
            $config->selected = $config->selected ?: ArrayTools::key_by_index($data, 0); //use first key if selected is empty
        }

        $radio = [];
        $i = 0;

        foreach ($data as $value => $text) {
            $i++;

            $radio[$i]['i'] = $i;
            $radio[$i]['value'] = $value;
            $radio[$i]['text'] = $text;
            $radio[$i]['checked'] = ($config->selected == $value) ? 'checked="checked"' : '';
        }

        return $radio;
    }

    /**
     * Return html code for csrf input & add session key with csrf value
     *
     * @param string $case
     * @return string
     */
    public function csrf(string $case): string
    {
        $csrf_code = StringTools::random_code(20);

        (Session::init())->set('csrf.' . $case, $csrf_code);

        return '<input type="hidden" name="csrf_' . $case . '" value="' . $csrf_code . '" />';
    }

    /**
     * Return html code for captcha verification
     *
     * @param Config $cfg
     * @param Url $url
     * @param $case
     * @return string
     * @throws ConfigException
     * @throws TemplateException
     */
    public function captcha(Config $cfg, Url $url, $case): string
    {
        $captcha_type = $cfg->get($case . '.captcha');

        if (empty($captcha_type) === true) {
            return '';
        }

        if ($captcha_type == 'standard') {
            return template('captcha_standard')
                ->assign('case', $case)
                ->assign('url', $url->route('captcha', [
                    'key' => StringTools::random_code(6)
                ])->return()
                )
                ->parse();
        }

        if ($captcha_type == 'recaptcha') {
            return template('captcha_recaptcha')
                ->assign('site_key', $cfg->get('captcha.recaptcha.site_key'))
                ->parse();
        }

        throw new ConfigException('Captcha "mode" (eg: ' . $captcha_type . ') is not configured for case "' . $case . '"');
    }

    /*
    function form_result_option_sql($vars)
    {
        //default vars
        $vars = array_merge(array(
            'query'       => null,
            'bind'        => [],
            'option_id'   => 'id',
            'option_name' => 'name',
        ), $vars);

        //extract
        extract($vars);

        //query -> select
		$sql_res = $this->sql->custom(array(
			'query'  => $query,
			'return' => 'select',
			'bind'   => $bind
        ));

        //init vars
        $array = [];

        //loop
        foreach ($sql_res as $sql_row)
        {
            //extract
            extract($sql_row);

            //vars
            $array[$$option_id] = $$option_name;
        }

        //return
        return $array;
    }

	function checkbox_status($source_value, $compare_value = null)
	{
		if ($compare_value)
			return $source_value == $compare_value ? 'checked="checked"' : null;
		else
			return $source_value ? 'checked="checked"' : null;
	}





    */
}
