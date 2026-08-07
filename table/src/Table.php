<?php

declare(strict_types=1);

namespace Kodhe\Table;

use Kodhe\Table\Contracts\TableInterface;
use Kodhe\Table\Contracts\RendererInterface;
use Kodhe\Table\Builder\HeaderBuilder;
use Kodhe\Table\Builder\RowBuilder;
use Kodhe\Table\Support\ColumnNormalizer;
use Kodhe\Table\Support\TemplateResolver;
use Kodhe\Table\Support\TableValidator;
use Kodhe\Table\Templates\TemplateAdapter;
use Kodhe\Table\Renderers\HtmlRenderer;
use Kodhe\Table\Factory\RendererFactory;

/**
 * HTML Table Generating Class
 *
 * Lets you create tables manually or from database result objects, or arrays.
 *
 * @package		Kodhe\Table
 * @subpackage	Libraries
 * @category	HTML Tables
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/libraries/table.html
 */
class Table implements TableInterface
{
	/**
	 * Data for table rows
	 *
	 * @var array
	 */
	public $rows = array();

	/**
	 * Data for table heading
	 *
	 * @var array
	 */
	public $heading = array();

	/**
	 * Whether or not to automatically create the table header
	 *
	 * @var bool
	 */
	public $auto_heading = TRUE;

	/**
	 * Table caption
	 *
	 * @var string
	 */
	public $caption = NULL;

	/**
	 * Table layout template
	 *
	 * @var array
	 */
	public $template = NULL;

	/**
	 * Newline setting
	 *
	 * @var string
	 */
	public $newline = "\n";

	/**
	 * Contents of empty cells
	 *
	 * @var string
	 */
	public $empty_cells = '';

	/**
	 * Callback for custom table layout
	 *
	 * @var callable|null
	 */
	public $function = NULL;

	/**
	 * @var HeaderBuilder Header builder instance
	 */
	private HeaderBuilder $headerBuilder;

	/**
	 * @var RowBuilder Row builder instance
	 */
	private RowBuilder $rowBuilder;

	/**
	 * @var TemplateResolver Template resolver instance
	 */
	private TemplateResolver $templateResolver;

	/**
	 * @var TableValidator Validator instance
	 */
	private TableValidator $validator;

	/**
	 * @var RendererInterface|null Renderer instance
	 */
	private ?RendererInterface $renderer = null;

	/**
	 * @var ColumnNormalizer Column normalizer instance
	 */
	private ColumnNormalizer $normalizer;

	/**
	 * Set the template from the table config file if it exists
	 *
	 * @param	array	$config	(default: array())
	 * @return	void
	 */
	public function __construct($config = array())
	{
		$this->normalizer = new ColumnNormalizer();
		$this->templateResolver = new TemplateResolver();
		$this->validator = new TableValidator();
		$this->headerBuilder = new HeaderBuilder($this->normalizer);
		$this->rowBuilder = new RowBuilder($this->normalizer);

		// initialize config
		if (is_array($config) && !empty($config)) {
			foreach ($config as $key => $val) {
				$this->template[$key] = $val;
			}
		}

		log_message('info', 'Table Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Set the template
	 *
	 * @param	array	$template
	 * @return	bool
	 */
	public function set_template($template): bool
	{
		if (!is_array($template)) {
			return FALSE;
		}

		$this->template = $template;
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the table heading
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @param	mixed
	 * @return	Table
	 */
	public function set_heading($args = array()): self
	{
		$this->heading = $this->_prep_args(func_get_args());
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set columns. Takes a one-dimensional array as input and creates
	 * a multi-dimensional array with a depth equal to the number of
	 * columns. This allows a single array with many elements to be
	 * displayed in a table that has a fixed column count.
	 *
	 * @param	array	$array
	 * @param	int	$col_limit
	 * @return	array|false
	 */
	public function make_columns($array = array(), $col_limit = 0)
	{
		if (!is_array($array) || count($array) === 0 || !is_int($col_limit)) {
			return FALSE;
		}

		// Turn off the auto-heading feature since it's doubtful we
		// will want headings from a one-dimensional array
		$this->auto_heading = FALSE;

		if ($col_limit === 0) {
			return $array;
		}

		return $this->normalizer->makeColumns($array, $col_limit);
	}

	// --------------------------------------------------------------------

	/**
	 * Set "empty" cells
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @param	mixed	$value
	 * @return	Table
	 */
	public function set_empty($value): self
	{
		$this->empty_cells = $value;
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a table row
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @param	mixed
	 * @return	Table
	 */
	public function add_row($args = array()): self
	{
		$this->rows[] = $this->_prep_args(func_get_args());
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Args
	 *
	 * Ensures a standard associative array format for all cell data
	 *
	 * @param	array
	 * @return	array
	 */
	protected function _prep_args($args)
	{
		return $this->normalizer->prepArgs($args);
	}

	// --------------------------------------------------------------------

	/**
	 * Add a table caption
	 *
	 * @param	string	$caption
	 * @return	Table
	 */
	public function set_caption($caption): self
	{
		$this->caption = $caption;
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate the table
	 *
	 * @param	mixed	$table_data
	 * @return	string
	 */
	public function generate($table_data = NULL)
	{
		// The table data can optionally be passed to this function
		// either as a database result object or an array
		if (!empty($table_data)) {
			if ($table_data instanceof \CI_DB_result) {
				$this->_set_from_db_result($table_data);
			} elseif (is_array($table_data)) {
				$this->_set_from_array($table_data);
			}
		}

		// Is there anything to display? No? Smite them!
		if (empty($this->heading) && empty($this->rows)) {
			return 'Undefined table data';
		}

		// Compile and validate the template date
		$resolvedTemplate = $this->templateResolver->resolve($this->template);

		// Validate a possibly existing custom cell manipulation function
		if (isset($this->function) && !is_callable($this->function)) {
			$this->function = NULL;
		}

		// Use renderer to generate output
		$renderer = $this->getRenderer();
		$out = $renderer->render(
			$this->heading,
			$this->rows,
			$resolvedTemplate,
			$this->empty_cells,
			$this->caption,
			$this->function
		);

		// Clear table class properties before generating the table
		$this->clear();

		return $out;
	}

	// --------------------------------------------------------------------

	/**
	 * Clears the table arrays.  Useful if multiple tables are being generated
	 *
	 * @return	Table
	 */
	public function clear(): self
	{
		$this->rows = array();
		$this->heading = array();
		$this->auto_heading = TRUE;
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set table data from a database result object
	 *
	 * @param	\CI_DB_result	$object	Database result object
	 * @return	void
	 */
	protected function _set_from_db_result($object)
	{
		// First generate the headings from the table column names
		if ($this->auto_heading === TRUE && empty($this->heading)) {
			$this->heading = $this->_prep_args($object->list_fields());
		}

		foreach ($object->result_array() as $row) {
			$this->rows[] = $this->_prep_args($row);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set table data from an array
	 *
	 * @param	array	$data
	 * @return	void
	 */
	protected function _set_from_array($data)
	{
		if ($this->auto_heading === TRUE && empty($this->heading)) {
			$this->heading = $this->_prep_args(array_shift($data));
		}

		foreach ($data as &$row) {
			$this->rows[] = $this->_prep_args($row);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get the renderer instance
	 *
	 * @return RendererInterface
	 */
	protected function getRenderer(): RendererInterface
	{
		if ($this->renderer === null) {
			$this->renderer = new HtmlRenderer();
			$this->renderer->setNewline($this->newline);
		}
		return $this->renderer;
	}

	/**
	 * Set a custom renderer
	 *
	 * @param RendererInterface $renderer
	 * @return self
	 */
	public function setRenderer(RendererInterface $renderer): self
	{
		$this->renderer = $renderer;
		return $this;
	}

	/**
	 * Get the header builder
	 *
	 * @return HeaderBuilder
	 */
	public function getHeaderBuilder(): HeaderBuilder
	{
		return $this->headerBuilder;
	}

	/**
	 * Get the row builder
	 *
	 * @return RowBuilder
	 */
	public function getRowBuilder(): RowBuilder
	{
		return $this->rowBuilder;
	}

	/**
	 * Get the template resolver
	 *
	 * @return TemplateResolver
	 */
	public function getTemplateResolver(): TemplateResolver
	{
		return $this->templateResolver;
	}

	/**
	 * Get the normalizer
	 *
	 * @return ColumnNormalizer
	 */
	public function getNormalizer(): ColumnNormalizer
	{
		return $this->normalizer;
	}
}
