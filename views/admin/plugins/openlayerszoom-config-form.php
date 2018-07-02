<fieldset id="fieldset-openlayerszoom">
    <legend><?php echo __('OpenLayersZoom'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('openlayerszoom_tiles_dir', __('Directory path of tiles files')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('openlayerszoom_tiles_dir', get_option('openlayerszoom_tiles_dir'), null); ?>
            <p class="explanation">
                <?php echo __('Directory path where tiles files are stored.'); ?>
                <?php echo  __('Default directory is "%s".', FILES_DIR . DIRECTORY_SEPARATOR . 'zoom_tiles'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('openlayerszoom_tiles_web', __('Base Url of tiles files')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('openlayerszoom_tiles_web', get_option('openlayerszoom_tiles_web'), null); ?>
            <p class="explanation">
                <?php echo __('Equivalent web url, with or without root.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('openlayerszoom_use_default_hook', __('Use "public_items_show" hook')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formCheckbox('openlayerszoom_use_default_hook', true, array('checked' => (boolean) get_option('openlayerszoom_use_default_hook'))); ?>
            <p class="explanation">
                <?php echo __('Enable the default hook to display the OpenLayersZoom for an item. Disable it if you want more control on display in theme.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('openlayerszoom_use_public_head', __('Automatically add css and javascript')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formCheckbox('openlayerszoom_use_public_head', true, array('checked' => (boolean) get_option('openlayerszoom_use_public_head'))); ?>
            <p class="explanation">
                <?php echo __('OpenLayersZoom needs css and javascript to run. It is added automatically and only when needed.'); ?>
                <?php echo __('Unckeck if you prefer to load them yourself in case of complex items or javascript.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('openlayerszoom_queue_js', __('Js queued in footer')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formCheckbox('openlayerszoom_queue_js', true, array('checked' => (boolean) get_option('openlayerszoom_queue_js'))); ?>
            <p class="explanation">
                <?php echo __('If your theme lists the scripts in the footer, you can check this box, so the open layers box will be echoed in the footer too.'); ?>
            </p>
        </div>
    </div>
</fieldset>
