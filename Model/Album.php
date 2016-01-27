<?php

namespace Pixlee\Pixlee\Model;

class Album extends \Magento\Framework\Model\AbstractModel {

	public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    \Psr\Log\LoggerInterface $logger,
    \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
    \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
    array $data = []
	) {
	   $this->_logger = $logger;
	   parent::__construct($context, $registry, $resource, $resourceCollection, $data);
	}

  public function _construct() {
    $this->_init('Pixlee\Pixlee\Model\Resource\Album');
  }
}
