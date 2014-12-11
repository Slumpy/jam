<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * The actual widgets, used by the form builder, you can extend replace this to handle your usecase better
 *
 * @package    Jam
 * @category   Form
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Form_General extends Jam_Form {

	/**
	 * The template for a single html row
	 * @var string
	 */
	protected $_template = '<div class="row :name-field :type-field :with-errors">:label<div class="input"><div class="field-wrapper">:field:errors</div>:help</div></div>';

	/**
	 * Getter / setter for the template
	 * @param  string|null $template
	 * @return Jam_Form|string
	 */
	public function template($template = NULL)
	{
		if ($template !== NULL)
		{
			$this->_template = $template;
			return $this;
		}
		return $this->_template;
	}

	/**
	 * Generate a html row with the input field, label, help and error messages
	 *
	 * @param string $type       the html field (input, hidden, textarea ...)
	 * @param string $name       the name of the attribute of the bound Jam_Model
	 * @param array  $options    options for the input field, you can also change the 'label', and add 'help' message
	 * @param array  $attributes html attributes for the input field
	 * @return string
	 */
	public function row($type, $name, array $options = array(), array $attributes = array(), $template = NULL)
	{
		$errors = Arr::get($options, 'errors', $this->errors($name));

		$field = call_user_func(array($this, $type), $name, $options, $attributes);

		$help = Arr::get($options, 'help');

		$slots = array(
			':name' => $name,
			':type' => $type,
			':label' => $this->label($name, Arr::get($options, 'label')),
			':with-errors' => $errors ? 'with-errors' : '',
			':errors' => $errors,
			':help' => $help ? "<span class=\"help-block\">{$help}</span>" : '',
			':field' => $field,
		);

		return strtr($template ? $template : $this->template(), $slots);
	}

	/**
	 * Return the html for the errors for a given field
	 * @param string $name
	 * @return string
	 */
	public function errors($name)
	{
		$errors = join(', ', Arr::flatten( (array) $this->object()->errors($name)));
		return $errors ? "<span class=\"form-error\">{$errors}</span>" : '';
	}

	/**
	 * Return an html label linked to a given input field
	 * @param string $name       the name of the Jam_Model attribute
	 * @param string $label      the text of the label, can be autogenerated if NULL
	 * @param array  $attributes of the input field, extract the id from there
	 * @return string
	 */
	public function label($name, $label = NULL, array $attributes = array())
	{
		$field_attributes = $this->default_attributes($name, $attributes);

		if ($label === NULL)
		{
			if ($this->meta()->field($name) AND $this->meta()->field($name)->label !== NULL)
			{
				$label = UTF8::ucfirst($this->meta()->field($name)->label);
			}
			else
			{
				$label = UTF8::ucfirst(Inflector::humanize($name));
			}
		}
		return Form::label($field_attributes['id'], $label, $attributes);
	}

	/**
	 * HTML input text field
	 *
	 * @param string $name       the name of the Jam_Model attribute
	 * @param array  $options    Not Used - for compatibility
	 * @param array  $attributes HTML attributes for the field
	 * @return string
	 */
	public function input($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return Form::input($attributes['name'], $this->object()->$name, $attributes);
	}

	/**
	 * HTML input hidden field
	 *
	 * @param string $name       the name of the Jam_Model attribute
	 * @param array  $options    Not Used - for compatibility
	 * @param array  $attributes HTML attributes for the field
	 * @return string
	 */
	public function hidden($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return Form::hidden($attributes['name'], Jam_Form::list_id($this->object()->$name), $attributes);
	}


	/**
	 * HTML input hidden field for multiple values, renders all the nesessary hidden fields
	 * @param  string $name
	 * @param  array  $options    Not Used - for compatibility
	 * @param  array  $attributes HTML attributes for all the fields
	 * @return string
	 */
	public function hidden_list($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);
		$ids = Jam_Form::list_id($this->object()->$name);
		$html = '';
		foreach ($ids as $index => $id)
		{
			$html .= Form::hidden($attributes['name']."[$index]", $id, $attributes);
		}
		return $html;
	}

	/**
	 * HTML input checkbox field
	 *
	 * @param string $name       the name of the Jam_Model attribute
	 * @param array  $options    can change the value of the checkbox with 'value', defaults to 1
	 * @param array  $attributes HTML attributes for the field
	 * @return string
	 */
	public function checkbox($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		$value = Arr::get($options, 'value', 1);
		$empty = Arr::get($options, 'empty', 0);
		$disabled = in_array('disabled', $attributes) OR isset($attributes['disabled']);

		return
			Form::hidden($attributes['name'], $empty, $disabled ? array('disabled') : array())
			.Form::checkbox($attributes['name'], $value, $this->object()->$name, $attributes);
	}

	/**
	 * HTML input radio field
	 *
	 * @param string $name       the name of the Jam_Model attribute
	 * @param array  $options    must provide the value of the radio with 'value'
	 * @param array  $attributes HTML attributes for the field
	 * @return string
	 */
	public function radio($name, array $options = array(), array $attributes = array())
	{
		$value = Arr::get($options, 'value', '');

		if ( ! isset($attributes['id']))
		{
			$attributes['id'] = $this->default_id($name).'_'.URL::title($value);
		}

		$attributes = $this->default_attributes($name, $attributes);

		return Form::radio($attributes['name'], $value, Jam_Form::list_id($this->object()->$name, TRUE) == Jam_Form::list_id($value), $attributes);
	}

	public function checkboxes($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		if ( ! isset($options['choices']))
			throw new Kohana_Exception("Checkboxes tag widget requires a 'choices' option");

		$choices = Jam_Form::list_choices($options['choices']);
		$values = Arr::get($options, 'value', (array) Jam_Form::list_choices($this->object()->$name));
		$html = '';

		foreach ($choices as $key => $title)
		{
			$id = $attributes['id'].'_'.$key;
			$html .= '<li>'
				.Form::label($id, Form::checkbox($attributes['name']."[]", $key, array_key_exists($key, $values), array("id" => $id))."<span>$title</span>")
			.'</li>';
		}
		return "<ul ".HTML::attributes($attributes).">$html</ul>";
	}

	public function radios($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		if ( ! isset($options['choices']))
			throw new Kohana_Exception("Radios tag widget requires a 'choices' option");

		$choices = Jam_Form::list_choices($options['choices']);

		$radios = array();

		foreach ($choices as $key => $title)
		{
			$id = $attributes['id'].'_'.$key;
			$radios[] =
				'<li>'
					.$this->radio($name, array('value' => $key), array('id' => $id))
					.$this->label($name, $title, array('id' => $id))
				.'</li>';
		}
		return "<ul ".HTML::attributes($attributes).">".join("\n", $radios)."</ul>";
	}

	/**
	 * HTML input file field
	 *
	 * @param string $name       the name of the Jam_Model attribute
	 * @param array  $options    temp_source = TRUE, set this to add a spection hidden input to preserve the file upload on fiald validation
	 * @param array  $attributes HTML attributes for the field
	 * @return string
	 */
	public function file($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return
			Form::file($attributes['name'], $attributes)
			.(Arr::get($options, 'temp_source', FALSE)
				? Form::hidden($attributes['name'], $this->object()->$name->temp_source(), Arr::get($options, 'temp_attributes', array('class' => 'hidden-input')))
				: ''
			);
	}

	/**
	 * HTML input password field
	 * @param  string $name       the name of the Jam_Model attribute
	 * @param  array  $options    Not Used - for compatibility
	 * @param  array  $attributes HTML attributes for the field
	 * @return string
	 */
	public function password($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return Form::password($attributes['name'], '', $attributes);
	}


	/**
	 * HTML input textarea field
	 *
	 * @param string $name       the name of the Jam_Model attribute
	 * @param array  $options    Not Used - for compatibility
	 * @param array  $attributes HTML attributes for the field
	 * @return string
	 */
	public function textarea($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return Form::textarea($attributes['name'], $this->object()->$name, $attributes);
	}

	/**
	 * HTML input select field
	 * available options
	 * 	- choices - this can be Jam_Query_Builder_Collection or a simple array
	 * 	- include_blank - bool|string - include an empty option
	 *
	 * @param string $name       the name of the Jam_Model attribute
	 * @param array  $options    set the options of the select with 'choices' and 'include_blank'
	 * @param array  $attributes HTML attributes for the field
	 * @return string
	 */
	public function select($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		if ( ! isset($options['choices']))
			throw new Kohana_Exception("Select tag widget requires a 'choices' option");

		$choices = Jam_Form::list_choices($options['choices']);

		if ($blank = Arr::get($options, 'include_blank'))
		{
			Arr::unshift($choices, '', ($blank === TRUE) ? " -- Select -- " : $blank);
		}

		if ($additional = Arr::get($options, 'additional'))
		{
			$choices = Arr::merge($choices, $additional);
		}

		$selected = Jam_Form::list_id($this->object()->$name);

		return Form::select($attributes['name'], $choices, $selected, $attributes);
	}
} // End Kohana_Jam_Field
