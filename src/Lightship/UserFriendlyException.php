<?php
namespace Lightship;

class UserFriendlyException extends \Exception implements UserFriendlyExceptionInterface {
	public function getUserFriendlyMessage() {
		return $this->getMessage();
	}
}
