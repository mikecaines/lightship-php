<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

class ComponentChain implements \IteratorAggregate {
	/** @var ComponentChainLink[] */ private $links = [];
	
	private function getLinkIndex(string $aId) {
		foreach ($this->links as $i => $link) {
			if ($link->id() === $aId) return $i;
		}
		
		return null;
	}
	
	/**
	 * @param string $aId
	 * @return ComponentChainLink|null
	 */
	public function get(string $aId) {
		foreach ($this->links as $link) {
			if ($link->id() === $aId) return $link;
		}
		
		return null;
	}
	
	/**
	 * @param string|null $aId
	 * @param ComponentChainLink|array $aLink
	 */
	public function insertBefore($aId, $aLink) {
		$link = $aLink instanceof ComponentChainLink ? $aLink : new ComponentChainLink($aLink);
		array_splice($this->links, $this->getLinkIndex($aId), 0, [$link]);
	}
	
	/**
	 * @param string|null $aId
	 * @param ComponentChainLink|array $aLink
	 */
	public function insertAfter($aId, $aLink) {
		$link = $aLink instanceof ComponentChainLink ? $aLink : new ComponentChainLink($aLink);
		
		if ($aId === null) {
			array_push($this->links, $link);
		}
		else {
			$index = $this->getLinkIndex($aId);
			if ($index === null) array_push($this->links, $link);
			else array_splice($this->links, $index, 0, [$link]);
		}
	}
	
	public function __clone() {
		$clone = new static();
		
		foreach ($this->links as $link) {
			$clone->insertAfter(null, $link);
		}
		
		return $clone;
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->links);
	}
}
