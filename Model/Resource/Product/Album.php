<?php

namespace Pixlee\Pixlee\Model\Resource\Product;

class Album extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb {
  protected function _construct() {
    $this->_init('px_product_album', 'id');
  }
}
