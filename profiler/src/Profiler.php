<?php

declare(strict_types=1);

namespace Kodhe\Framework\Profiler;

use Kodhe\Framework\Profiler\Contracts\ProfilerInterface;
use Kodhe\Framework\Profiler\Contracts\CollectorInterface;
use Kodhe\Framework\Profiler\Factory\CollectorFactory;
use Kodhe\Framework\Profiler\Factory\RendererFactory;
use Kodhe\Framework\Profiler\Support\ProfilerConfig;
use Kodhe\Framework\Profiler\Support\DataNormalizer;
use Kodhe\Framework\Profiler\ValueObjects\ProfileSection;

/**
 * CodeIgniter Profiler Class
 *
 * This class enables you to display benchmark, query, and other data
 * in order to help with debugging and optimization.
 *
 * Note: At some point it would be good to move all the HTML in this class
 * into a set of template files in order to allow customization.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/general/profiling.html
 */
class Profiler implements ProfilerInterface
{

	/**
	 * List of profiler sections available to show
	 *
	 * @var array
	 */
	protected $_available_sections = array(
		'benchmarks',
		'get',
		'memory_usage',
		'post',
		'uri_string',
		'controller_info',
		'queries',
		'http_headers',
		'session_data',
		'config'
	);

	/**
	 * Number of queries to show before making the additional queries togglable
	 *
	 * @var int
	 */
	protected $_query_toggle_count = 25;

	/**
	 * Reference to the CodeIgniter singleton
	 *
	 * @var object
	 */
	protected $CI;

	// Internal modular components
	protected ProfilerConfig $config;
	protected CollectorFactory $collectorFactory;
	protected RendererFactory $rendererFactory;
	protected DataNormalizer $normalizer;
	protected array $sections = [];
	protected array $customCollectors = [];
	protected bool $initialized = false;

	// Cache for compiled data (lazy collection)
	protected array $compiledCache = [];

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Initialize Profiler
	 *
	 * @param	array	$config	Parameters
	 */
	public function __construct($config = array())
	{
		$this->CI = kodhe();
		$this->CI->load->language('profiler');

		// Initialize modular components
		$this->config = new ProfilerConfig($config);
		$this->normalizer = new DataNormalizer(config_item('charset') ?? 'UTF-8');
		$this->collectorFactory = new CollectorFactory($this->CI);
		$this->rendererFactory = new RendererFactory($this->CI->lang);

		// Initialize sections
		$this->initializeSections();

		// Handle query toggle count
		if (isset($config['query_toggle_count'])) {
			$this->_query_toggle_count = (int) $config['query_toggle_count'];
		}

		$this->set_sections($config);
		
		$this->initialized = true;
		log_message('info', 'Profiler Class Initialized');
	}

	/**
	 * Initialize section objects
	 */
	protected function initializeSections(): void
	{
		foreach ($this->_available_sections as $section) {
			$enabled = $this->config->isSectionEnabled($section);
			$this->sections[$section] = new ProfileSection($section, $enabled);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set Sections
	 *
	 * Sets the private _compile_* properties to enable/disable Profiler sections
	 *
	 * @param	mixed	$config
	 * @return	void
	 */
	public function set_sections($config)
	{
		if (isset($config['query_toggle_count'])) {
			$this->_query_toggle_count = (int) $config['query_toggle_count'];
			unset($config['query_toggle_count']);
		}

		foreach ($config as $method => $enable) {
			if (in_array($method, $this->_available_sections)) {
				$shouldCompile = ($enable !== FALSE);
				$this->{`_compile_{$method}`} = $shouldCompile;
				
				if (isset($this->sections[$method])) {
					$this->sections[$method]->setEnabled($shouldCompile);
				}
				
				$this->config->setSections([$method => $enable]);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Enable a specific section (PSR interface method)
	 *
	 * @param string $section
	 * @return void
	 */
	public function enableSection(string $section): void
	{
		$this->config->enableSection($section);
		if (isset($this->sections[$section])) {
			$this->sections[$section]->setEnabled(true);
		}
		$this->{`_compile_{$section}`} = TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Disable a specific section (PSR interface method)
	 *
	 * @param string $section
	 * @return void
	 */
	public function disableSection(string $section): void
	{
		$this->config->disableSection($section);
		if (isset($this->sections[$section])) {
			$this->sections[$section]->setEnabled(false);
		}
		$this->{`_compile_{$section}`} = FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Check if a section is enabled (PSR interface method)
	 *
	 * @param string $section
	 * @return bool
	 */
	public function isSectionEnabled(string $section): bool
	{
		return $this->config->isSectionEnabled($section);
	}

	// --------------------------------------------------------------------

	/**
	 * Register a custom collector (PSR interface method)
	 *
	 * @param string $name
	 * @param CollectorInterface $collector
	 * @return void
	 */
	public function addCollector(string $name, CollectorInterface $collector): void
	{
		$this->customCollectors[$name] = $collector;
		$collector->setDependencies($this->CI);
		
		if (!in_array($name, $this->_available_sections)) {
			$this->_available_sections[] = $name;
		}
		
		if (!isset($this->sections[$name])) {
			$this->sections[$name] = new ProfileSection($name, true, get_class($collector));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get all available sections (PSR interface method)
	 *
	 * @return array
	 */
	public function getAvailableSections(): array
	{
		return $this->_available_sections;
	}

	// --------------------------------------------------------------------

	/**
	 * Auto Profiler - Compile Benchmarks
	 *
	 * This function cycles through the entire array of mark points and
	 * matches any two points that are named identically (ending in "_start"
	 * and "_end" respectively).  It then compiles the execution times for
	 * all points and returns it as an array
	 *
	 * @return	array
	 */
	protected function _compile_benchmarks()
	{
		if (isset($this->compiledCache['benchmarks'])) {
			return $this->compiledCache['benchmarks'];
		}

		$profile = array();
		foreach ($this->CI->benchmark->marker as $key => $val) {
			// We match the "end" marker so that the list ends
			// up in the order that it was defined
			if (preg_match('/(.+?)_end$/i', $key, $match)
				&& isset($this->CI->benchmark->marker[$match[1].'_end'], $this->CI->benchmark->marker[$match[1].'_start'])) {
				$profile[$match[1]] = $this->CI->benchmark->elapsed_time($match[1].'_start', $key);
			}
		}

		$this->compiledCache['benchmarks'] = $profile;
		return $profile;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile Queries
	 *
	 * @return	string
	 */
	protected function _compile_queries()
	{
		if (isset($this->compiledCache['queries'])) {
			return $this->compiledCache['queries'];
		}

		$dbs = array();

		// Let's determine which databases are currently connected to
		foreach (get_object_vars($this->CI) as $name => $cobject) {
			if (is_object($cobject)) {
				if ($cobject instanceof \Kodhe\Framework\Database\DB) {
					$dbs[get_class($this->CI) . ':$' . $name] = $cobject;
				} elseif ($cobject instanceof CI_Model) {
					foreach (get_object_vars($cobject) as $mname => $mobject) {
						if ($mobject instanceof \Kodhe\Framework\Database\DB) {
							$dbs[get_class($cobject) . ':$' . $mname] = $mobject;
						}
					}
				}
			}
		}

		if (count($dbs) === 0) {
			$output = "\n\n"
				. '<fieldset id="ci_profiler_queries" style="border:1px solid #0000FF;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
				. "\n"
				. '<legend style="color:#0000FF;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_queries') . '&nbsp;&nbsp;</legend>'
				. "\n\n\n<table style=\"border:none; width:100%;\">\n"
				. '<tr><td style="width:100%;color:#0000FF;font-weight:normal;background-color:#eee;padding:5px;">'
				. $this->CI->lang->line('profiler_no_db')
				. "</td></tr>\n</table>\n</fieldset>";

			$this->compiledCache['queries'] = $output;
			return $output;
		}

		// Load the text helper so we can highlight the SQL
		$this->CI->load->helper('text');

		// Key words we want bolded
		$highlight = array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')');

		$output = "\n\n";
		$count = 0;

		foreach ($dbs as $name => $db) {
			$hide_queries = (count($db->queries) > $this->_query_toggle_count) ? ' display:none' : '';
			$total_time = number_format(array_sum($db->query_times), 4) . ' ' . $this->CI->lang->line('profiler_seconds');

			$show_hide_js = '(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_queries_db_' . $count . '\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=thisinnerHTML==\'' . $this->CI->lang->line('profiler_section_hide') . '\'?\'' . $this->CI->lang->line('profiler_section_show') . '\':\'' . $this->CI->lang->line('profiler_section_hide') . '\';">' . $this->CI->lang->line('profiler_section_hide') . '</span>)';

			if ($hide_queries !== '') {
				$show_hide_js = '(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_queries_db_' . $count . '\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'' . $this->CI->lang->line('profiler_section_show') . '\'?\'' . $this->CI->lang->line('profiler_section_hide') . '\':\'' . $this->CI->lang->line('profiler_section_show') . '\';">' . $this->CI->lang->line('profiler_section_show') . '</span>)';
			}

			$output .= '<fieldset style="border:1px solid #0000FF;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
				. "\n"
				. '<legend style="color:#0000FF;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_database')
				. ':&nbsp; ' . $db->database . ' (' . $name . ')&nbsp;&nbsp;&nbsp;' . $this->CI->lang->line('profiler_queries')
				. ': ' . count($db->queries) . ' (' . $total_time . ')&nbsp;&nbsp;' . $show_hide_js . "</legend>\n\n\n"
				. '<table style="width:100%;' . $hide_queries . '" id="ci_profiler_queries_db_' . $count . "\">\n";

			if (count($db->queries) === 0) {
				$output .= '<tr><td style="width:100%;color:#0000FF;font-weight:normal;background-color:#eee;padding:5px;">'
					. $this->CI->lang->line('profiler_no_queries') . "</td></tr>\n";
			} else {
				foreach ($db->queries as $key => $val) {
					$time = number_format($db->query_times[$key], 4);
					$val = highlight_code($val);

					foreach ($highlight as $bold) {
						$val = str_replace($bold, '<strong>' . $bold . '</strong>', $val);
					}

					$output .= '<tr><td style="padding:5px;vertical-align:top;width:1%;color:#900;font-weight:normal;background-color:#ddd;">'
						. $time . '&nbsp;&nbsp;</td><td style="padding:5px;color:#000;font-weight:normal;background-color:#ddd;">'
						. $val . "</td></tr>\n";
				}
			}

			$output .= "</table>\n</fieldset>";
			$count++;
		}

		$this->compiledCache['queries'] = $output;
		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile $_GET Data
	 *
	 * @return	string
	 */
	protected function _compile_get()
	{
		if (isset($this->compiledCache['get'])) {
			return $this->compiledCache['get'];
		}

		$output = "\n\n"
			. '<fieldset id="ci_profiler_get" style="border:1px solid #cd6e00;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
			. "\n"
			. '<legend style="color:#cd6e00;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_get_data') . "&nbsp;&nbsp;</legend>\n";

		if (count($_GET) === 0) {
			$output .= '<div style="color:#cd6e00;font-weight:normal;padding:4px 0 4px 0;">' . $this->CI->lang->line('profiler_no_get') . '</div>';
		} else {
			$output .= "\n\n<table style=\"width:100%;border:none;\">\n";

			foreach ($_GET as $key => $val) {
				is_int($key) OR $key = "'" . htmlspecialchars($key, ENT_QUOTES, config_item('charset')) . "'";
				$val = (is_array($val) OR is_object($val))
					? '<pre>' . htmlspecialchars(print_r($val, TRUE), ENT_QUOTES, config_item('charset')) . '</pre>'
					: htmlspecialchars($val, ENT_QUOTES, config_item('charset'));

				$output .= '<tr><td style="width:50%;color:#000;background-color:#ddd;padding:5px;">&#36;_GET['
					. $key . ']&nbsp;&nbsp; </td><td style="width:50%;padding:5px;color:#cd6e00;font-weight:normal;background-color:#ddd;">'
					. $val . "</td></tr>\n";
			}

			$output .= "</table>\n";
		}

		$result = $output . '</fieldset>';
		$this->compiledCache['get'] = $result;
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile $_POST Data
	 *
	 * @return	string
	 */
	protected function _compile_post()
	{
		if (isset($this->compiledCache['post'])) {
			return $this->compiledCache['post'];
		}

		$output = "\n\n"
			. '<fieldset id="ci_profiler_post" style="border:1px solid #009900;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
			. "\n"
			. '<legend style="color:#009900;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_post_data') . "&nbsp;&nbsp;</legend>\n";

		if (count($_POST) === 0 && count($_FILES) === 0) {
			$output .= '<div style="color:#009900;font-weight:normal;padding:4px 0 4px 0;">' . $this->CI->lang->line('profiler_no_post') . '</div>';
		} else {
			$output .= "\n\n<table style=\"width:100%;\">\n";

			foreach ($_POST as $key => $val) {
				is_int($key) OR $key = "'" . htmlspecialchars($key, ENT_QUOTES, config_item('charset')) . "'";
				$val = (is_array($val) OR is_object($val))
					? '<pre>' . htmlspecialchars(print_r($val, TRUE), ENT_QUOTES, config_item('charset')) . '</pre>'
					: htmlspecialchars($val, ENT_QUOTES, config_item('charset'));

				$output .= '<tr><td style="width:50%;padding:5px;color:#000;background-color:#ddd;">&#36;_POST['
					. $key . ']&nbsp;&nbsp; </td><td style="width:50%;padding:5px;color:#009900;font-weight:normal;background-color:#ddd;">'
					. $val . "</td></tr>\n";
			}

			foreach ($_FILES as $key => $val) {
				is_int($key) OR $key = "'" . htmlspecialchars($key, ENT_QUOTES, config_item('charset')) . "'";
				$val = (is_array($val) OR is_object($val))
					? '<pre>' . htmlspecialchars(print_r($val, TRUE), ENT_QUOTES, config_item('charset')) . '</pre>'
					: htmlspecialchars($val, ENT_QUOTES, config_item('charset'));

				$output .= '<tr><td style="width:50%;padding:5px;color:#000;background-color:#ddd;">&#36;_FILES['
					. $key . ']&nbsp;&nbsp; </td><td style="width:50%;padding:5px;color:#009900;font-weight:normal;background-color:#ddd;">'
					. $val . "</td></tr>\n";
			}

			$output .= "</table>\n";
		}

		$result = $output . '</fieldset>';
		$this->compiledCache['post'] = $result;
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Show query string
	 *
	 * @return	string
	 */
	protected function _compile_uri_string()
	{
		if (isset($this->compiledCache['uri_string'])) {
			return $this->compiledCache['uri_string'];
		}

		$result = "\n\n"
			. '<fieldset id="ci_profiler_uri_string" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
			. "\n"
			. '<legend style="color:#000;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_uri_string') . "&nbsp;&nbsp;</legend>\n"
			. '<div style="color:#000;font-weight:normal;padding:4px 0 4px 0;">'
			. ($this->CI->uri->uri_string === '' ? $this->CI->lang->line('profiler_no_uri') : $this->CI->uri->uri_string)
			. '</div></fieldset>';

		$this->compiledCache['uri_string'] = $result;
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Show the controller and function that were called
	 *
	 * @return	string
	 */
	protected function _compile_controller_info()
	{
		if (isset($this->compiledCache['controller_info'])) {
			return $this->compiledCache['controller_info'];
		}

		$result = "\n\n"
			. '<fieldset id="ci_profiler_controller_info" style="border:1px solid #995300;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
			. "\n"
			. '<legend style="color:#995300;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_controller_info') . "&nbsp;&nbsp;</legend>\n"
			. '<div style="color:#995300;font-weight:normal;padding:4px 0 4px 0;">' . $this->CI->router->class . '/' . $this->CI->router->method
			. '</div></fieldset>';

		$this->compiledCache['controller_info'] = $result;
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile memory usage
	 *
	 * Display total used memory
	 *
	 * @return	string
	 */
	protected function _compile_memory_usage()
	{
		if (isset($this->compiledCache['memory_usage'])) {
			return $this->compiledCache['memory_usage'];
		}

		$result = "\n\n"
			. '<fieldset id="ci_profiler_memory_usage" style="border:1px solid #5a0099;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
			. "\n"
			. '<legend style="color:#5a0099;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_memory_usage') . "&nbsp;&nbsp;</legend>\n"
			. '<div style="color:#5a0099;font-weight:normal;padding:4px 0 4px 0;">'
			. (($usage = memory_get_usage()) != '' ? number_format($usage) . ' bytes' : $this->CI->lang->line('profiler_no_memory'))
			. '</div></fieldset>';

		$this->compiledCache['memory_usage'] = $result;
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile header information
	 *
	 * Lists HTTP headers
	 *
	 * @return	string
	 */
	protected function _compile_http_headers()
	{
		if (isset($this->compiledCache['http_headers'])) {
			return $this->compiledCache['http_headers'];
		}

		$output = "\n\n"
			. '<fieldset id="ci_profiler_http_headers" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
			. "\n"
			. '<legend style="color:#000;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_headers')
			. '&nbsp;&nbsp;(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_httpheaders_table\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'' . $this->CI->lang->line('profiler_section_show') . '\'?\'' . $this->CI->lang->line('profiler_section_hide') . '\':\'' . $this->CI->lang->line('profiler_section_show') . '\';">' . $this->CI->lang->line('profiler_section_show') . "</span>)</legend>\n\n\n"
			. '<table style="width:100%;display:none;" id="ci_profiler_httpheaders_table">' . "\n";

		foreach (array('HTTP_ACCEPT', 'HTTP_USER_AGENT', 'HTTP_CONNECTION', 'SERVER_PORT', 'SERVER_NAME', 'REMOTE_ADDR', 'SERVER_SOFTWARE', 'HTTP_ACCEPT_LANGUAGE', 'SCRIPT_NAME', 'REQUEST_METHOD', ' HTTP_HOST', 'REMOTE_HOST', 'CONTENT_TYPE', 'SERVER_PROTOCOL', 'QUERY_STRING', 'HTTP_ACCEPT_ENCODING', 'HTTP_X_FORWARDED_FOR', 'HTTP_DNT') as $header) {
			$val = isset($_SERVER[$header]) ? htmlspecialchars($_SERVER[$header], ENT_QUOTES, config_item('charset')) : '';
			$output .= '<tr><td style="vertical-align:top;width:50%;padding:5px;color:#900;background-color:#ddd;">'
				. $header . '&nbsp;&nbsp;</td><td style="width:50%;padding:5px;color:#000;background-color:#ddd;">' . $val . "</td></tr>\n";
		}

		$result = $output . "</table>\n</fieldset>";
		$this->compiledCache['http_headers'] = $result;
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile config information
	 *
	 * Lists developer config variables
	 *
	 * @return	string
	 */
	protected function _compile_config()
	{
		if (isset($this->compiledCache['config'])) {
			return $this->compiledCache['config'];
		}

		$output = "\n\n"
			. '<fieldset id="ci_profiler_config" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
			. "\n"
			. '<legend style="color:#000;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_config') . '&nbsp;&nbsp;(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_config_table\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'' . $this->CI->lang->line('profiler_section_show') . '\'?\'' . $this->CI->lang->line('profiler_section_hide') . '\':\'' . $this->CI->lang->line('profiler_section_show') . '\';">' . $this->CI->lang->line('profiler_section_show') . "</span>)</legend>\n\n\n"
			. '<table style="width:100%;display:none;" id="ci_profiler_config_table">' . "\n";

		foreach ($this->CI->config->config as $config => $val) {
			$pre       = '';
			$pre_close = '';

			if (is_array($val) OR is_object($val)) {
				$val = print_r($val, TRUE);

				$pre       = '<pre>';
				$pre_close = '</pre>';
			}

			$output .= '<tr><td style="padding:5px;vertical-align:top;color:#900;background-color:#ddd;">'
				. $config . '&nbsp;&nbsp;</td><td style="padding:5px;color:#000;background-color:#ddd;">' . $pre . htmlspecialchars($val, ENT_QUOTES, config_item('charset')) . $pre_close . "</td></tr>\n";
		}

		$result = $output . "</table>\n</fieldset>";
		$this->compiledCache['config'] = $result;
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile session userdata
	 *
	 * @return 	string
	 */
	protected function _compile_session_data()
	{
		if (isset($this->compiledCache['session_data'])) {
			return $this->compiledCache['session_data'];
		}

		if (!isset($this->CI->session)) {
			return '';
		}

		$output = '<fieldset id="ci_profiler_csession" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
			. '<legend style="color:#000;">&nbsp;&nbsp;' . $this->CI->lang->line('profiler_session_data') . '&nbsp;&nbsp;(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_session_data\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'' . $this->CI->lang->line('profiler_section_show') . '\'?\'' . $this->CI->lang->line('profiler_section_hide') . '\':\'' . $this->CI->lang->line('profiler_section_show') . '\';">' . $this->CI->lang->line('profiler_section_show') . '</span>)</legend>'
			. '<table style="width:100%;display:none;" id="ci_profiler_session_data">';

		foreach ($this->CI->session->userdata() as $key => $val) {
			$pre       = '';
			$pre_close = '';

			if (is_array($val) OR is_object($val)) {
				$val = print_r($val, TRUE);

				$pre       = '<pre>';
				$pre_close = '</pre>';
			}

			$output .= '<tr><td style="padding:5px;vertical-align:top;color:#900;background-color:#ddd;">'
				. $key . '&nbsp;&nbsp;</td><td style="padding:5px;color:#000;background-color:#ddd;">' . $pre . htmlspecialchars($val, ENT_QUOTES, config_item('charset')) . $pre_close . "</td></tr>\n";
		}

		$result = $output . "</table>\n</fieldset>";
		$this->compiledCache['session_data'] = $result;
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Run the Profiler
	 *
	 * @return	string
	 */
	public function run()
	{
		$output = '<div id="codeigniter_profiler" style="clear:both;background-color:#fff;padding:10px;">';
		$fields_displayed = 0;

		foreach ($this->_available_sections as $section) {
			// Check if section should be compiled using both old and new system
			$shouldCompile = isset($this->{`_compile_{$section}`}) ? $this->{`_compile_{$section}`} : $this->config->isSectionEnabled($section);
			
			if ($shouldCompile !== FALSE) {
				$func = '_compile_' . $section;
				
				// Check for custom collector first
				if (isset($this->customCollectors[$section])) {
					$collector = $this->customCollectors[$section];
					if ($collector->hasData()) {
						$data = $collector->collect();
						$output .= $this->renderCustomSection($section, $data);
						$fields_displayed++;
					}
				} elseif (method_exists($this, $func)) {
					$result = $this->{$func}();
					if ($result !== null && $result !== '') {
						$output .= $result;
						$fields_displayed++;
					}
				}
			}
		}

		if ($fields_displayed === 0) {
			$output .= '<p style="border:1px solid #5a0099;padding:10px;margin:20px 0;background-color:#eee;">'
				. $this->CI->lang->line('profiler_no_profiles') . '</p>';
		}

		return $output . '</div>';
	}

	/**
	 * Render custom collector section
	 */
	protected function renderCustomSection(string $section, array $data): string
	{
		// Use HTML renderer for custom sections
		$renderer = $this->rendererFactory->create('html');
		
		$sectionData = [
			$section => [
				'enabled' => true,
				'data' => $data
			]
		];
		
		return $renderer->render($sectionData);
	}
}
