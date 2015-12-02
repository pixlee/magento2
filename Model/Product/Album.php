<?php

namespace Pixlee\Pixlee\Model\Product;

class Album extends \Magento\Framework\Model\AbstractModel {
  protected function _construct() {
    $this->_init('Pixlee\Pixlee\Model\Resource\Product\Album');
  }
}