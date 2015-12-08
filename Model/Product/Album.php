<?php

namespace Pixlee\Pixlee\Model\Product;

class Album extends \Magento\Framework\Model\AbstractModel {

	/**
     * Class constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Authorization\Model\ResourceModel\Rules $resource
     * @param \Magento\Authorization\Model\ResourceModel\Permissions\Collection $resourceCollection
     * @param array $data
     */
	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Pixlee\Pixlee\Model\Resource\Product\Album $resource,
		\Magento\Authorization\Model\ResourceModel\Permissions\Collection $resourceCollection,
		array $data = []
		) {
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);
	}

	protected function _construct() {
		$this->_init('Pixlee\Pixlee\Model\Resource\Product\Album');
	}
}