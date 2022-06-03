<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Library\Exceptions\ConfigException;

class Url
{
    private Routing $routing;

    private string $routeKey;
    private array $routeData = [];
    private string $href;
    private array $params = [];
    private string $anchor;
    private array $attributes = [];

    public function __construct(Routing $routing)
    {
        $this->routing = $routing;
    }

    public function route(string $routeKey, array $routeData = []): self
    {
        $this->routeKey($routeKey);
        $this->routeData($routeData);

        return $this;
    }

    public function routeKey(string $string): self
    {
        $this->routeKey = $string;

        return $this;
    }

    public function routeData(array $array): self
    {
        foreach ($array as $k => $v) {
            $this->addRouteData($k, $v);
        }

        return $this;
    }

    public function addRouteData(string $key, string $value): self
    {
        $this->routeData[$key] = $value;

        return $this;
    }

    public function params(array $array): self
    {
        foreach ($array as $k => $v) {
            $this->addParam($k, $v);
        }

        return $this;
    }

    public function addParam(string $key, string $value): self
    {
        $this->params[$key] = $value;

        return $this;
    }

    public function href(string $string): self
    {
        $this->href = $string;

        return $this;
    }

    public function module_link(string $pattern = ''): string
    {
        return $this->routing->module_link($pattern);
    }

    public function module_url(string $controller, string $method, string $id = null, array $params = []): self
    {
        $this->href($this->routing->module_link() . '/' . $controller . '/' . $method . '/' . $id);
        $this->params($params);

        return $this;
    }

    public function anchor(string $string): self
    {
        $this->anchor = $string;

        return $this;
    }

    public function attributes(array $array): self
    {
        foreach ($array as $k => $v) {
            $this->addAttribute($k, $v);
        }

        return $this;
    }

    public function addAttribute(string $key, string $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function title(string $string): self
    {
        $this->addAttribute('title', $string);

        return $this;
    }

    public function class(string $string): self
    {
        $this->addAttribute('class', $string);

        return $this;
    }

    public function css(string $string): self
    {
        $this->addAttribute('css', $string);

        return $this;
    }

    /**
     * @throws ConfigException
     */
    public function return(bool $reset = true): string
    {
        if (empty($this->href) === true) {
            $this->href = $this->routing->link($this->routeKey, $this->routeData);
        }

        if ($this->params) {
            $this->href .= '?' . http_build_query($this->params);
        }

        if (empty($this->anchor) === true) {
            $output = $this->href;
        } else {
            $output = '<a href="' . $this->href . '"' . $this->generateAttributesCode() . '>' . $this->anchor . '</a>';
        }

        if ($reset === true) {
            $this->reset();
        }

        return $output;
    }

    private function generateAttributesCode(): string
    {
        if (empty($this->attributes) === true) {
            return '';
        }

        $attributes = $this->attributes;

        $output = array_map(function ($k) use ($attributes) {
            if (is_bool($attributes[$k])) {
                return $k;
            }

            if (empty($attributes[$k]) === false) {
                return $k . '="' . $attributes[$k] . '"';
            } else {
                return null;
            }
        }, array_keys($attributes));
        $output = array_filter($output);
        $output = implode(' ', $output);

        return ' ' . $output;
    }

    private function reset(): void
    {
        $this->routeKey = '';
        $this->routeData = [];
        $this->href = '';
        $this->params = [];
        $this->anchor = '';
        $this->attributes = [];
    }

    /**
     * @throws ConfigException
     */
    public function __toString()
    {
        return $this->return();
    }

    /*
	public function clink($controller, $method, $id = null, $extra = null)
	{
        //init vars
        $link = $this->cfg->get('link.module').'/'.$controller.'/'.$method.'/';

        //condition
        if ($id) {
            $link .= $id;
        }

        //condition
        if ($extra) {
            $link .= '?'.(is_array($extra) ? http_build_query($extra) : $extra);
        }

		//return
		return $link;
	}

	public function alink($type, array $vars)
	{
        //case
		switch ($type)
		{
			case 'function':
				//default vars
				$vars = array_merge(array(
                    'function' => null,
					'title'    => null,
					'anchor'   => null,
                    'class'    => null,
				), $vars);

                //extract
				extract($vars);

				//vars
				$title  = $title  ? $title : $this->language($controller, 'method.'.$method);
				$anchor = $anchor ? $anchor : $this->icon($method);

				//return
				return '<a href="javascript: '.$function.';" title="'.$title.'" data-type="'.$type.'" class="'.$class.'">'.$anchor.'</a>';
			break;

			case 'action':
			case 'action_list':
				//default vars
				$vars = array_merge(array(
                    'url'        => null,
                    'controller' => null,
                    'method'     => null,
                    'id'         => null,
                    'extra'      => null,
					'title'          => null,
					'anchor'         => null,
					'dialog_title'   => null,
					'dialog_message' => null,
                    'class'          => null,
				), $vars);

                //extract
				extract($vars);

				//vars
                $url            = $url            ? $url            : $this->clink($controller, $method, $id, $extra);
				$title          = $title          ? $title          : $this->language($controller, 'method.'.$method);
				$anchor         = $anchor         ? $anchor         : $this->icon($method);
				$dialog_title   = $dialog_title   ? $dialog_title   : $title;
				$dialog_message = $dialog_message ? $dialog_message : $this->language($controller, 'action.'.$method);

				//return
				return '<a href="'.$url.'" title="'.$title.'" data-type="'.$type.'" dialog-title="'.$dialog_title.'" dialog-message="'.$dialog_message.'" class="'.$class.'">'.$anchor.'</a>';
			break;

			case 'loader':
			case 'loader_list':
				//default vars
				$vars = array_merge(array(
                    'url'        => null,
                    'controller' => null,
                    'method'     => null,
                    'id'         => null,
                    'extra'      => null,
					'title'  => null,
					'anchor' => null,
                    'class'  => null,
				), $vars);

                //extract
				extract($vars);

				//vars
                $url    = $url    ? $url    : $this->clink($controller, $method, $id, $extra);
				$title  = $title  ? $title  : $this->language($controller, 'method.'.$method);
				$anchor = is_null($anchor) ? $this->icon($method) : $anchor;

				//return
				return '<a href="'.$url.'" title="'.$title.'" data-type="'.$type.'" class="'.$class.'">'.$anchor.'</a>';
            break;

			case 'popup':
				//default vars
				$vars = array_merge(array(
                    'url'        => null,
                    'controller' => null,
                    'method'     => null,
                    'id'         => null,
                    'extra'      => null,
					'title'  => null,
					'anchor' => null,
				), $vars);

                //extract
				extract($vars);

				//vars
                $url = $url ? $url : $this->clink($controller, $method, $id, $extra);

				//return
				return '<a href="'.$url.'" title="'.$title.'" data-type="popup">'.$anchor.'</a>';
			break;

			case 'standard':
				//default vars
				$vars = array_merge(array(
                    'url'        => null,
                    'controller' => null,
                    'method'     => null,
                    'id'         => null,
                    'extra'      => null,
					'title'  => null,
					'anchor' => null,
				), $vars);

                //extract
				extract($vars);

				//vars
                $url    = $url    ? $url    : $this->clink($controller, $method, $id, $extra);
				$title  = $title  ? $title  : $this->language($controller, 'method.'.$method);
                $anchor = $anchor ? $anchor : $this->icon($controller.' '.$method);

				//return
				return '<a href="'.$url.'" title="'.$title.'" data-type="standard">'.$anchor.'</a>';
			break;

			default:
				//trigger error
				trigger_error('Url type (eg: '.$type.') not defined', E_USER_ERROR);
		}
	}
    */
}

?>
