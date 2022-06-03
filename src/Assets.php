<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Library\Exceptions\TemplateException;

class Assets
{
    private Config $cfg;

    private array $result = array(
        'head' => array(
            'js' => [],
            'css' => []
        ),
        'body' => array(
            'js' => [],
            'css' => []
        ),
        'footer' => array(
            'js' => [],
            'css' => []
        ),
    );

    /**
     * @param Config $cfg
     */
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    /**
     * Check asset list
     *
     * @return void
     * @throws TemplateException
     */
    public function check(): void
    {
        $arr_not_found = [];

        $arr_css = array_merge($this->result['head']['css'], $this->result['body']['css']);
        $arr_js = array_merge($this->result['head']['js'], $this->result['body']['js']);

        foreach ($arr_css as $file_url) {
            $file_path = str_replace($this->cfg->get('link.site'), $this->cfg->get('folder.root'), $file_url);

            if (!file_exists($file_path)) {
                $arr_not_found[] = $file_url;
            }
        }

        foreach ($arr_js as $file_url) {
            $file_path = str_replace($this->cfg->get('link.site'), $this->cfg->get('folder.root'), $file_url);

            if (!file_exists($file_path)) {
                $arr_not_found[] = $file_url;
            }
        }

        if ($arr_not_found) {
            throw new TemplateException('Assets not found: <br /><br />' . implode('<br />', $arr_not_found));
        }
    }

    /**
     * Add asset
     *
     * @param $data
     * @param string $type
     * @param string $section
     * @return void
     * @throws TemplateException
     */
    public function add($data, string $type = 'cfg', string $section = 'head'): void
    {
        $data = is_array($data) ? $data : array($data);

        foreach ($data as $v) {
            switch ($type) {
                case 'cfg':
                    $asset = $this->cfg->get('assets.' . $v);

                    if ($asset) {
                        foreach ($asset as $_type => $_v) {
                            if (is_array($_v)) {
                                foreach ($_v as $_e)
                                    $this->add($_e, $_type, $section);
                            } else {
                                $this->add($_v, $_type, $section);
                            }
                        }
                    } else {
                        throw new TemplateException('Asset is not defined (eg: ' . $v . ')');
                    }
                    break;

                case 'css':
                case 'js':
                    switch ($section) {
                        case 'head':
                        case 'footer':
                            $this->result[$section][$type][] = $v;
                            break;

                        case 'body':
                            if (!in_array($v, $this->result['head'][$type])) {
                                $this->result['body'][$type][] = $v;
                            }
                            break;
                    }
                    break;

                default:
                    throw new TemplateException('Asset type is not supported (eg: ' . $type . ' => ' . $v . ')');
            }
        }
    }

    /*
	public function remove($data, $type = 'cfg', $section = 'head')
	{
        //vars
		$data = is_array($data) ? $data : array($data);

        //loop
		foreach ($data as $v)
		{
            //case
			switch ($type)
			{
				case 'cfg':
					$asset = $this->cfg->get('assets.'.$v);

					if($asset)
					{
						foreach ($asset as $_type => $_v)
						{
                            if (is_array($_v))
                            {
                                foreach ($_v as $_e)
                                    $this->remove($_e, $_type, $section);
                            }
                            else
                            {
							    $this->remove($_v, $_type, $section);
                            }
						}
					}
					else
					{
						trigger_error('Asset is not defined (eg: '.$v.')', E_USER_ERROR);
					}
				break;

				case 'css':
				case 'js':
					ArrayTools::remove($this->result[$section][$type], $v); ?? doesn't exists
				break;

				default:
					trigger_error('Asset type is not supported (eg: '.$type.' => '.$v.')', E_USER_ERROR);
			}
		}
	}
    */

    /**
     * Html output for assets
     *
     * @param string $version
     * @param string $section
     * @return string
     */
    public function output(string $version = '', string $section = 'head'): string
    {
        $output = [];

        switch ($section) {
            case 'body':
                $arr_css = $this->result['body']['css'];
                $arr_js = $this->result['body']['js'];
                break;

            case 'footer':
                $arr_css = $this->result['footer']['css'];
                $arr_js = $this->result['footer']['js'];
                break;

            default: //header
                $arr_css = array_merge($this->result['head']['css'], $this->result['body']['css']);
                $arr_js = array_merge($this->result['head']['js'], $this->result['body']['js']);
                break;
        }

        foreach ($arr_css as $v) {
            if ($version) {
                $v .= '?v=' . $version;
            }

            $output[] = '<link rel="stylesheet" type="text/css" href="' . $v . '" />';
        }

        foreach ($arr_js as $v) {
            if ($version) {
                $v .= '?v=' . $version;
            }

            $output[] = '<script src="' . $v . '"></script>';
        }

        return implode("\n", $output);
    }
}
