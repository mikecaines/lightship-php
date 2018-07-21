<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

class ComponentChainLink {
	/** @var string */ private $id;
	/** @var string */ private $namespace;
	/** @var string */ private $path;
	/** @var string */ private $pluginsNamespace;
	/** @var string */ private $pluginsPath;
	
	public function id(): string {
		return $this->id;
	}
	
	public function namespace(): string {
		return $this->namespace;
	}
	
	public function path(): string {
		return $this->path;
	}
	
	public function pluginsNamespace(): string {
		return $this->pluginsNamespace;
	}
	
	public function pluginsPath(): string {
		return $this->pluginsPath;
	}
	
	public function __construct(array $aOptions) {
		$options = array_replace([
			'id' => '',
			'namespace' => '',
			'path' => '',
			'pluginsNamespace' => '\\Plugins',
			'pluginsPath' => '/Plugins',
		], $aOptions);
		
		$this->id = (string)$options['id'];
		$this->namespace = (string)$options['namespace'];
		$this->path = (string)$options['path'];
		$this->pluginsNamespace = (string)$options['pluginsNamespace'];
		$this->pluginsPath = (string)$options['pluginsPath'];
	}
}
