<?php
/**
 * CodeIgniter
 *
 * 一款开源的PHP应用开发框架
 *
 * （注：以下为 MIT协议声明 原文）
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * URI Class
 * URI 类
 *
 * Parses URIs and determines routing
 * 解析 URI 并确定路由
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	URI
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/libraries/uri.html
 */
class CI_URI {

	/**
	 * List of cached URI segments
     * 缓存的 URI 段列表
	 *
	 * @var	array
	 */
	public $keyval = array();

	/**
	 * Current URI string
     * 当前 URI 字符串
	 *
	 * @var	string
	 */
	public $uri_string = '';

	/**
	 * List of URI segments
     * URI 段列表
	 *
	 * Starts at 1 instead of 0.
     * 从 1 开始，而不是 0。
	 *
	 * @var	array
	 */
	public $segments = array();

	/**
	 * List of routed URI segments
     * 路由后的 URI 段列表
	 *
	 * Starts at 1 instead of 0.
     * 从 1 开始，而不是 0。
	 *
	 * @var	array
	 */
	public $rsegments = array();

	/**
	 * Permitted URI chars
     * 合法的 URI 字符
	 *
	 * PCRE character group allowed in URI segments
     * 在 URI 段中允许使用 PCRE 字符组
	 *
	 * @var	string
	 */
	protected $_permitted_uri_chars;

	/**
	 * Class constructor
     * 类构造函数
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->config =& load_class('Config', 'core');

		// If query strings are enabled, we don't need to parse any segments.
        // 如果查询字符串被启用，我们不需要解析任何段。
		// However, they don't make sense under CLI.
        // 然而，他们在 CLI 模式下不起作用，
		if (is_cli() OR $this->config->item('enable_query_strings') !== TRUE)
		{
			$this->_permitted_uri_chars = $this->config->item('permitted_uri_chars');

			// If it's a CLI request, ignore the configuration
            // 如果是一个 CLI 请求，那么忽略配置项
			if (is_cli())
			{
				$uri = $this->_parse_argv();
			}
			else
			{
				$protocol = $this->config->item('uri_protocol');
				empty($protocol) && $protocol = 'REQUEST_URI';

				switch ($protocol)
				{
					case 'AUTO': // For BC purposes only    只是为了 BC （注：不确定这里指的是不是 BC 数学函数）
					case 'REQUEST_URI':
						$uri = $this->_parse_request_uri();
						break;
					case 'QUERY_STRING':
						$uri = $this->_parse_query_string();
						break;
					case 'PATH_INFO':
					default:
						$uri = isset($_SERVER[$protocol])
							? $_SERVER[$protocol]
							: $this->_parse_request_uri();
						break;
				}
			}

			$this->_set_uri_string($uri);
		}

		log_message('info', 'URI Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Set URI String
     * 设置 URI 字符串
	 *
	 * @param 	string	$str
	 * @return	void
	 */
	protected function _set_uri_string($str)
	{
		// Filter out control characters and trim slashes
        // 过滤掉控制字符并去除首尾的斜线
		$this->uri_string = trim(remove_invisible_characters($str, FALSE), '/');

		if ($this->uri_string !== '')
		{
			// Remove the URL suffix, if present
            // 删除 URL 后缀（如果存在的话）
			if (($suffix = (string) $this->config->item('url_suffix')) !== '')
			{
				$slen = strlen($suffix);

				if (substr($this->uri_string, -$slen) === $suffix)
				{
					$this->uri_string = substr($this->uri_string, 0, -$slen);
				}
			}

			$this->segments[0] = NULL;
			// Populate the segments array
            // 构建分段数组
			foreach (explode('/', trim($this->uri_string, '/')) as $val)
			{
				$val = trim($val);
				// Filter segments for security
                // 对分段进行安全过滤
				$this->filter_uri($val);

				if ($val !== '')
				{
					$this->segments[] = $val;
				}
			}

			unset($this->segments[0]);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Parse REQUEST_URI
     * 解析 REQUEST_URI
	 *
	 * Will parse REQUEST_URI and automatically detect the URI from it,
	 * while fixing the query string if necessary.
     * 将会解析 REQUEST_URI 并自动检测 URI ，
     * 必要的话会补全查询字符串
	 *
	 * @return	string
	 */
	protected function _parse_request_uri()
	{
		if ( ! isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']))
		{
			return '';
		}

		// parse_url() returns false if no host is present, but the path or query string
		// contains a colon followed by a number
        // 如果没有主机部分，parse_url() 将会返回 false，
        // 但是 path 或 查询字符串 模式下会返回冒号加一个数字
		$uri = parse_url('http://dummy'.$_SERVER['REQUEST_URI']);
		$query = isset($uri['query']) ? $uri['query'] : '';
		$uri = isset($uri['path']) ? $uri['path'] : '';

		if (isset($_SERVER['SCRIPT_NAME'][0]))
		{
			if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
			{
				$uri = (string) substr($uri, strlen($_SERVER['SCRIPT_NAME']));
			}
			elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
			{
				$uri = (string) substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
			}
		}

		// This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
		// URI is found, and also fixes the QUERY_STRING server var and $_GET array.
        // 这个部分确保即使在 URI 位于查询字符串中的服务器（Nginx）上，也能找到正确的 URI，
        // 并同时修正 QUERY_STRING 服务器变量和 $_GET 数组.
        // （注：该部分是为了兼容形如 index.php?/controller/method?param=value 形式的 URL ）
		if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0)
		{
			$query = explode('?', $query, 2);
			$uri = $query[0];
			$_SERVER['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
		}
		else
		{
			$_SERVER['QUERY_STRING'] = $query;
		}

		parse_str($_SERVER['QUERY_STRING'], $_GET);

		if ($uri === '/' OR $uri === '')
		{
			return '/';
		}

		// Do some final cleaning of the URI and return it
        // 对 URI 做一些最终的清理工作，并将其返回
		return $this->_remove_relative_directory($uri);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse QUERY_STRING
     * 解析 QUERY_STRING
	 *
	 * Will parse QUERY_STRING and automatically detect the URI from it.
     * 将会解析 QUERY_STRING ，并自动检测 URI。
     *
	 * @return	string
	 */
	protected function _parse_query_string()
	{
		$uri = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');

		if (trim($uri, '/') === '')
		{
			return '';
		}
		elseif (strncmp($uri, '/', 1) === 0)
		{
			$uri = explode('?', $uri, 2);
			$_SERVER['QUERY_STRING'] = isset($uri[1]) ? $uri[1] : '';
			$uri = $uri[0];
		}

		parse_str($_SERVER['QUERY_STRING'], $_GET);

		return $this->_remove_relative_directory($uri);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse CLI arguments
     * 解析 CLI 参数
	 *
	 * Take each command line argument and assume it is a URI segment.
     * 提取每个命令行参数，并将它们作为 URI 分段
	 *
	 * @return	string
	 */
	protected function _parse_argv()
	{
		$args = array_slice($_SERVER['argv'], 1);
		return $args ? implode('/', $args) : '';
	}

	// --------------------------------------------------------------------

	/**
	 * Remove relative directory (../) and multi slashes (///)
     * 修改相对路径(../)和多斜线(///)
	 *
	 * Do some final cleaning of the URI and return it, currently only used in self::_parse_request_uri()
     * 对 URI 做一些最终的清理工作，并将其返回，当前仅在 self::_parse_request_uri() 中调用
	 *
	 * @param	string	$uri
	 * @return	string
	 */
	protected function _remove_relative_directory($uri)
	{
		$uris = array();
		$tok = strtok($uri, '/');
		while ($tok !== FALSE)
		{
			if (( ! empty($tok) OR $tok === '0') && $tok !== '..')
			{
				$uris[] = $tok;
			}
			$tok = strtok('/');
		}

		return implode('/', $uris);
	}

	// --------------------------------------------------------------------

	/**
	 * Filter URI
     * 过滤 URI
	 *
	 * Filters segments for malicious characters.
     * 对分段中的恶意字符进行过滤
	 *
	 * @param	string	$str
	 * @return	void
	 */
	public function filter_uri(&$str)
	{
		if ( ! empty($str) && ! empty($this->_permitted_uri_chars) && ! preg_match('/^['.$this->_permitted_uri_chars.']+$/i'.(UTF8_ENABLED ? 'u' : ''), $str))
		{
			show_error('The URI you submitted has disallowed characters.', 400);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch URI Segment
     * 提取 URI 段
	 *
	 * @see		CI_URI::$segments
	 * @param	int		$n		Index   索引
	 * @param	mixed		$no_result	What to return if the segment index is not found    索引指向的段没有找到时返回的值
	 * @return	mixed
	 */
	public function segment($n, $no_result = NULL)
	{
		return isset($this->segments[$n]) ? $this->segments[$n] : $no_result;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch URI "routed" Segment
     * 提取 URI "路由"（"routed"） 后的段
	 *
	 * Returns the re-routed URI segment (assuming routing rules are used)
	 * based on the index provided. If there is no routing, will return
	 * the same result as CI_URI::segment().
     * 根据提供的索引返回重新路由的 URI 段（假设使用了路由规则）。
     * 如果没有使用路由，那么将会返回和 CI_URI::segment() 相同的结果。
	 *
	 * @see		CI_URI::$rsegments
	 * @see		CI_URI::segment()
	 * @param	int		$n		Index       索引
	 * @param	mixed		$no_result	What to return if the segment index is not found    索引指向的段没有找到时返回的值
	 * @return	mixed
	 */
	public function rsegment($n, $no_result = NULL)
	{
		return isset($this->rsegments[$n]) ? $this->rsegments[$n] : $no_result;
	}

	// --------------------------------------------------------------------

	/**
	 * URI to assoc
     * URI 转关联数组
	 *
	 * Generates an associative array of URI data starting at the supplied
	 * segment index. For example, if this is your URI:
     * 使用提供的 URI 数据的段索引生成一个关联数组。
     * 例如，如果这是你的 URI：
	 *
	 *	example.com/user/search/name/joe/location/UK/gender/male
	 *
	 * You can use this method to generate an array with this prototype:
     * 你可以使用该方法生成一个这样的数组：
	 *
	 *	array (
	 *		name => joe
	 *		location => UK
	 *		gender => male
	 *	 )
	 *
	 * @param	int	$n		Index (default: 3)      索引（默认为3）
	 * @param	array	$default	Default values      默认值
	 * @return	array
	 */
	public function uri_to_assoc($n = 3, $default = array())
	{
		return $this->_uri_to_assoc($n, $default, 'segment');
	}

	// --------------------------------------------------------------------

	/**
	 * Routed URI to assoc
     * 路由后的 URI 转关联数组
	 *
	 * Identical to CI_URI::uri_to_assoc(), only it uses the re-routed
	 * segment array.
     * 和 CI_URI::uri_to_assoc() 完全相同，只是使用重新路由后的 URI 段数组
	 *
	 * @see		CI_URI::uri_to_assoc()
	 * @param 	int	$n		Index (default: 3)          索引（默认为3）
	 * @param 	array	$default	Default values      默认值
	 * @return 	array
	 */
	public function ruri_to_assoc($n = 3, $default = array())
	{
		return $this->_uri_to_assoc($n, $default, 'rsegment');
	}

	// --------------------------------------------------------------------

	/**
	 * Internal URI-to-assoc
     * 内置的 URI 转关联数组
	 *
	 * Generates a key/value pair from the URI string or re-routed URI string.
     * 根据 URI 字符串或路由后的 URI 字符串，生成一个 键/值 对
	 *
	 * @used-by	CI_URI::uri_to_assoc()
	 * @used-by	CI_URI::ruri_to_assoc()
	 * @param	int	$n		Index (default: 3)                              索引（默认：3）
	 * @param	array	$default	Default values                          默认值
	 * @param	string	$which		Array name ('segment' or 'rsegment')    数组名（'segment' 或 'rsegment'）
	 * @return	array
	 */
	protected function _uri_to_assoc($n = 3, $default = array(), $which = 'segment')
	{
		if ( ! is_numeric($n))
		{
			return $default;
		}

		if (isset($this->keyval[$which], $this->keyval[$which][$n]))
		{
			return $this->keyval[$which][$n];
		}

		$total_segments = "total_{$which}s";
		$segment_array = "{$which}_array";

		if ($this->$total_segments() < $n)
		{
			return (count($default) === 0)
				? array()
				: array_fill_keys($default, NULL);
		}

		$segments = array_slice($this->$segment_array(), ($n - 1));
		$i = 0;
		$lastval = '';
		$retval = array();
		foreach ($segments as $seg)
		{
			if ($i % 2)
			{
				$retval[$lastval] = $seg;
			}
			else
			{
				$retval[$seg] = NULL;
				$lastval = $seg;
			}

			$i++;
		}

		if (count($default) > 0)
		{
			foreach ($default as $val)
			{
				if ( ! array_key_exists($val, $retval))
				{
					$retval[$val] = NULL;
				}
			}
		}

		// Cache the array for reuse
        // 将得到的数组缓存
		isset($this->keyval[$which]) OR $this->keyval[$which] = array();
		$this->keyval[$which][$n] = $retval;
		return $retval;
	}

	// --------------------------------------------------------------------

	/**
	 * Assoc to URI
     * 关联数组转 URI
	 *
	 * Generates a URI string from an associative array
     * 根据一个关联数组，生成 URI 字符串
	 *
	 * @param	array	$array	Input array of key/value pairs      输入的键/值对数组
	 * @return	string	URI string                                  URI 字符串
	 */
	public function assoc_to_uri($array)
	{
		$temp = array();
		foreach ((array) $array as $key => $val)
		{
			$temp[] = $key;
			$temp[] = $val;
		}

		return implode('/', $temp);
	}

	// --------------------------------------------------------------------

	/**
	 * Slash segment
     * 斜线分段
	 *
	 * Fetches an URI segment with a slash.
     * 获取一个带斜线的 URI 段
	 *
	 * @param	int	$n	Index       索引
	 * @param	string	$where	Where to add the slash ('trailing' or 'leading')        在哪里添加斜线（'trailing' 或 'leading'）
	 * @return	string
	 */
	public function slash_segment($n, $where = 'trailing')
	{
		return $this->_slash_segment($n, $where, 'segment');
	}

	// --------------------------------------------------------------------

	/**
	 * Slash routed segment
     * 带斜线的重路由段
	 *
	 * Fetches an URI routed segment with a slash.
     * 返回一个带斜线的 URI 路由段
	 *
	 * @param	int	$n	Index                                                       索引
	 * @param	string	$where	Where to add the slash ('trailing' or 'leading')    在哪里添加斜线（'trailing' 或 'leading'）
	 * @return	string
	 */
	public function slash_rsegment($n, $where = 'trailing')
	{
		return $this->_slash_segment($n, $where, 'rsegment');
	}

	// --------------------------------------------------------------------

	/**
	 * Internal Slash segment
	 * 内置的斜线分段方法
     *
	 * Fetches an URI Segment and adds a slash to it.
     * 获取一个 URI 段，并添加一根斜线
	 *
	 * @used-by	CI_URI::slash_segment()
	 * @used-by	CI_URI::slash_rsegment()
	 *
	 * @param	int	$n	Index           索引
	 * @param	string	$where	Where to add the slash ('trailing' or 'leading')        在哪里添加斜线（'trailing' 或 'leading'）
	 * @param	string	$which	Array name ('segment' or 'rsegment')                    数组名（'segment' 或 'rsegment'）
	 * @return	string
	 */
	protected function _slash_segment($n, $where = 'trailing', $which = 'segment')
	{
		$leading = $trailing = '/';

		if ($where === 'trailing')
		{
			$leading	= '';
		}
		elseif ($where === 'leading')
		{
			$trailing	= '';
		}

		return $leading.$this->$which($n).$trailing;
	}

	// --------------------------------------------------------------------

	/**
	 * Segment Array
     * 分段数组
	 *
	 * @return	array	CI_URI::$segments
	 */
	public function segment_array()
	{
		return $this->segments;
	}

	// --------------------------------------------------------------------

	/**
	 * Routed Segment Array
     * 重路由后的分段数组
	 *
	 * @return	array	CI_URI::$rsegments
	 */
	public function rsegment_array()
	{
		return $this->rsegments;
	}

	// --------------------------------------------------------------------

	/**
	 * Total number of segments
     * 分段总数
	 *
	 * @return	int
	 */
	public function total_segments()
	{
		return count($this->segments);
	}

	// --------------------------------------------------------------------

	/**
	 * Total number of routed segments
     * 路由后的分段总数
	 *
	 * @return	int
	 */
	public function total_rsegments()
	{
		return count($this->rsegments);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch URI string
     * 获取 URI 字符串
	 *
	 * @return	string	CI_URI::$uri_string
	 */
	public function uri_string()
	{
		return $this->uri_string;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Re-routed URI string
     * 获取重路由后的 URI 字符串
	 *
	 * @return	string
	 */
	public function ruri_string()
	{
		return ltrim(load_class('Router', 'core')->directory, '/').implode('/', $this->rsegments);
	}

}
