<?php
/**
 * Category view template
 *
 * @var $block \Magento\Catalog\Block\Category\View
 */
?>
<?php
$helper = $this->helper('Pixlee\Pixlee\Helper\Data');
$nativeCategoryId = $block->getCurrentCategory()->getId();
?>
<div id="pixlee_container"></div>
<div id="pixlee_widget_master_container"></div>
<script id="pixlee_script">

window.PixleeAsyncInit = function() {
    Pixlee.init({
        apiKey: '<?php echo $helper->getApiKey(); ?>'
    });
    Pixlee.addCategoryWidget({
        widgetId: <?php echo $helper->getCDPWidgetId(); ?>,
        nativeCategoryId: '<?php echo $nativeCategoryId; ?>',
        ecomm_platform: 'magento_2'
    });
};
</script>
<script src="https://assets.pixlee.com/assets/pixlee_widget_1_0_0.js"></script>

