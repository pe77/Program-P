<?php
class FileLog {
	
	private $dir = '';
	private $fileName = '';
	
	function __construct($fileName, $dir = '') 
	{
		$this->fileName = $fileName;
		$this->dir = $dir; 
	}
	
	function Write($content, $category = '') 
	{
		if($category)
			$content = date('YmdHis') . "|{$category}|\t" . $content . PHP_EOL;
		else
			$content = date('YmdHis') . "\t" . $content . PHP_EOL;	
		//
		
		if ($this->dir != '' && !is_dir($this->dir))
		{
		    mkdir($this->dir, 0755, true);
		}
			
		$file = fopen($this->dir . $this->fileName, 'a');			
		
		fwrite($file, $content);
		fclose($file);
	}
}