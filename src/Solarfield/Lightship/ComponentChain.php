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

	public function getIterator() {
		return new \ArrayIterator($this->links);
	}

	public function withLinkPrepended($aLink) : ComponentChain {
		$newChain = clone $this;
		$link = $aLink instanceof ComponentChainLink ? $aLink : new ComponentChainLink($aLink);
		array_splice($newChain->links, null, 0, [$link]);
		return $newChain;
	}

	public function withLinkAppended($aLink) : ComponentChain {
		$newChain = clone $this;
		$link = $aLink instanceof ComponentChainLink ? $aLink : new ComponentChainLink($aLink);
		array_push($newChain->links, $link);
		return $newChain;
	}

	/**
	 * Clones this chain, inserting the specified link into the chain,
	 * before the link with the specified id. If no link with the specified id exists,
	 * the new link is prepended.
	 * @param string $aId
	 * @param ComponentChainLink|array $aLink
	 * @return ComponentChain
	 */
	public function withLinkInsertedBefore($aLink, string $aId) : ComponentChain {
		$newChain = clone $this;
		$link = $aLink instanceof ComponentChainLink ? $aLink : new ComponentChainLink($aLink);
		array_splice($newChain->links, $newChain->getLinkIndex($aId), 0, [$link]);
		return $newChain;
	}

	/**
	 * Clones this chain, inserting the specified link into the chain,
	 * after the link with the specified id. If no link with the specified id exists,
	 * the new link is appended.
	 * @param string $aId
	 * @param ComponentChainLink|array $aLink
	 * @return ComponentChain
	 */
	public function withLinkInsertedAfter($aLink, string $aId) : ComponentChain {
		$newChain = clone $this;
		$link = $aLink instanceof ComponentChainLink ? $aLink : new ComponentChainLink($aLink);
		$index = $newChain->getLinkIndex($aId);
		if ($index === null) array_push($newChain->links, $link);
		else array_splice($newChain->links, $index, 0, [$link]);
		return $newChain;
	}
	
	public function __construct(array $aLinks = []) {
		foreach ($aLinks as $link) {
			$this->links[] = $link instanceof ComponentChainLink ? $link : new ComponentChainLink($link);
		}
	}
}
