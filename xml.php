<?

class xml implements Iterator, ArrayAccess
{
	public	$xml				= '',
			$data				= [];
	
	private	$stack				= [],
			$declaration		= '',
			$index				= 0,
			$line				= 0,
			$tag_name			= '',
			$tag_value			= '',
			$attribute_name		= '',
			$attribute_value	= '',
			$attributes			= [],
			$syntax				= 'syntax_tag_value';
	
	public function __construct($data = [])
	{
		switch(gettype($data))
		{
			case 'string':
				$this->xml = (
					($data[0] == DIRECTORY_SEPARATOR && file_exists($data))
					?
					file_get_contents($data)
					:
					$data
				);
				if(!empty($this->xml) && $this->xml[0] == '<')
				{
					$this->parse();
				}
				break;
			
			case 'array':
				$this->data = $data;
				break;
		}
	}
	
	public function __toString()
	{
		return (
			isset($this->declaration)
			?
			"<?{$this->declaration}?>"
			:
			'<?xml version="1.0" encoding="UTF-8"?>'
		) . $this->traverse($this->data);
	}
	
		private function traverse($node)
		{
			$xml = '';
			$attributes = '';
			if(count($node) > 1)
			{
				foreach(array_slice($node, 1) as $key => $value)
				{
					$attributes .= ' ' . substr($key, 1) . '="' . $value . '"';
				}
			}
			foreach(array_slice($node, 0, 1) as $tag => $data)
			{
				switch(gettype($data))
				{
					case 'array':
						$xml .= "<{$tag}{$attributes}>";
							if($this->is_assoc($data))
							{
								foreach($data as $child)
								{
									$xml .= $this->traverse($child);
								}
							}
							else
							{
								$xml .= $this->traverse($data);
							}
						$xml .= "</{$tag}>";
						break;
					
					case 'NULL':
						$xml .= "<{$tag}{$attributes} />";
						break;
					
					default:
						$xml .= "<{$tag}{$attributes}>{$data}</{$tag}>";
						break;
				}
			}
			return $xml;
		}
	
	private function is_assoc($array)
	{
		return (
			count(
				array_filter(
					array_keys($array),
					'is_string'
				)
			) > 0
		);
	}
	
	private function parse()
	{
		$this->xml = str_replace(
			"\t",
			'    ',
			$this->xml
		);
		
		$this->stack[] =& $this->data;
		
		for(
			$length = strlen($this->xml);
			$this->index < $length;
			$this->index++
		)
		{
			switch($this->xml[$this->index])
			{
				case '<':
					switch($this->xml[$this->index + 1])
					{
						case '?':
							$this->index		+= 2;
							$this->syntax		= 'syntax_declaration';
							break;
						
						case '/':
							$this->index		+= 2;
							$this->tag_name		= '';
							$this->syntax		= 'syntax_tag_back_start';
							break;
						
						default:
							$this->index		+= 1;
							$this->tag_name		= $this->tag_value = '';
							$this->attributes	= [];
							$this->syntax		= 'syntax_tag_front_start';
							break;
					}
					break;
				
				case '/':
					switch($this->xml[$this->index + 1])
					{
						case '>':
							$this->index += 1;
							$this->syntax = 'syntax_tag_back_end';
							break;
					}
					break;
	
				case '>':
					switch($this->syntax)
					{
						case 'syntax_tag_front_start':
						case 'syntax_attribute_name':
							$this->syntax = 'syntax_tag_front_end';
							break;
						
						default:
							$this->xml		= substr($this->xml, $this->index);
							$this->index	= 0;
							$length			= strlen($this->xml);
							$this->syntax	= 'syntax_tag_back_end';
							break;
					}
					break;
	
				case "\n":
					$this->line++;
					break;
			}
			
			$this->{$this->syntax}();
		}
	
		unset($this->xml);
	}
	
	// ### Iterator: foreach access ###
	
	public function rewind()
	{
		reset($this->data);
	}
	
	public function current()
	{
		return current($this->data);
	}
	
	public function key() 
	{
		return key($this->data);
	}
	
	public function next() 
	{
		return next($this->data);
	}
	
	public function valid()
	{
		$key = key($this->data);
		return ($key !== null && $key !== false);
	}
	
	// ### ArrayAccess: key/value access ###
	
	public function offsetSet($offset, $value)
	{
		if(is_null($offset))
		{
			$this->data[] = $value;
		}
		else
		{
			$this->data[$offset] = $value;
		}
	}
	
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}
	
	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}
	
	public function offsetGet($offset)
	{
		return (
			isset($this->data[$offset])
			?
			$this->data[$offset]
			:
			null
		);
	}
	
	// ### START ### Declaration ###
	
	public function version()
	{
		return (
			preg_match('#version\="(.*)"#U', $this->declaration, $match)
			?
			$match[1]
			:
			'1.0'
		);
	}
	
	public function encoding()
	{
		return (
			preg_match('#encoding\="(.*)"#U', $this->declaration, $match)
			?
			$match[1]
			:
			'utf-8'
		);
	}
	
	private function syntax_declaration()
	{
		if(
			$this->xml[$this->index] == '?'
			&&
			$this->xml[$this->index + 1] == '>'
		)
		{
			$this->index++;
			$this->syntax = 'syntax_tag_value';
		}
		else
		{
			$this->declaration .= $this->xml[$this->index];
		}
	}
	
	// ### END ### Declaration ###
	
	private function syntax_error()
	{
		error_log("Syntax error in XML data. Please check line # {$this->line}.");
	}
	
	private function syntax_tag_front_start()
	{
		switch($this->xml[$this->index])
		{
			case ' ':
				$this->syntax = 'syntax_attribute_name';
				$this->attribute_name = $this->attribute_value = '';
				break;
			
			default:
				$this->tag_name .= $this->xml[$this->index];
				break;
		}			
	}
	
	private function syntax_tag_front_end()
	{
		$node = [];
		$node[$this->tag_name] = [];
		if(!empty($this->attributes))
		{
			foreach($this->attributes as $key => $value)
			{
				$node["@{$key}"] = $value;
			}
		}
		
		$current =& $this->stack[count($this->stack) - 1];
		if(empty($current))
		{
			$current = $node;
			$this->stack[] =& $current[$this->tag_name];
		}
		else
		{
			if($this->is_assoc($current))
			{
				$current = [$current, $node];
			}
			else
			{
				$current[] = $node;
			}
			$this->stack[] =& $current[count($current) - 1][$this->tag_name];
		}
		
		$this->syntax = 'syntax_tag_value';
	}
	
	private function syntax_tag_back_start()
	{
		$this->tag_name .= $this->xml[$this->index];
	}
	
	private function syntax_tag_back_end()
	{
		$child =& $this->stack[count($this->stack) - 1];
		array_pop($this->stack);
		
		$last = count($this->stack) - 1;
		if(
			isset($this->stack[$last][$this->tag_name])
			||
			isset(end($this->stack[$last])[$this->tag_name])
		)
		{
			if(empty($child))
			{
				$child = (
					(
						($this->tag_value = trim($this->tag_value))
						&&
						$this->tag_value != ''
					)
					?
					$this->tag_value
					:
					null
				);
			}
			$this->tag_value	= '';
			$this->syntax		= 'syntax_tag_value';
		}
		else
		{
			$this->syntax_error();
		}
	}
	
	private function syntax_tag_value()
	{
		$this->tag_value .= $this->xml[$this->index];
	}
	
	private function syntax_attribute_name()
	{
		switch($this->xml[$this->index])
		{
			case '=':
			case ' ':
				break;
			
			case '"':
				$this->syntax = 'syntax_attribute_value';
				break;
			
			default:
				$this->attribute_name .= $this->xml[$this->index];
				break;
		}
	}
	
	private function syntax_attribute_value()
	{
		switch($this->xml[$this->index])
		{
			case '"':
				$this->syntax = 'syntax_attribute_end';
				$this->index--;
				break;
			
			default:
				$this->attribute_value .= $this->xml[$this->index];
				break;
		}
	}
	
	private function syntax_attribute_end()
	{
		$this->attributes[$this->attribute_name] = $this->attribute_value;
		$this->syntax = 'syntax_tag_front_start';
	}
}