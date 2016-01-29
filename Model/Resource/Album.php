<?php

namespace Pixlee\Pixlee\Model\Resource;

class Album extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb {

	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context,
		\Psr\Log\LoggerInterface $logger,
		$resourcePrefix = null
	) {
		$this->_logger = $logger;
		parent::__construct($context, $resourcePrefix);
	}

	public function _construct() 
	{
    	$this->_init('px_product_albums', 'id');
	}
}