<?php
namespace Pixlee\Pixlee\Model\Resource\Product;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Pixlee\Pixlee\Model\Product\Album', 'Pixlee\Pixlee\Model\Resource\Product\Album');
    }
}
