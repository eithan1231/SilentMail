<?php

class html_tabs
{
	private $m_pages;

	private $m_width;
	private $m_height;

	private $m_random

	function __construct()
	{
		$this->m_random = versionHash . randomString(6);
	}

	public function addPage($title, $template, $get_template = false)
	{
		$this->m_pages[] = [
			'title' => $title,
			'template' => $template,
			'get_template' => $get_template,
		];
	}

	public function setDimensions($width, $height)
	{
		$this->m_height = $height;
		$this->m_width = $width;
	}

	public function loadHtml()
	{

	}

	public function loadCss()
	{

	}

	public function loadJs()
	{
		
	}
}
