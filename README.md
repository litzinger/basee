
# Installation

`composer require litzinger/basee`

# Usage

In your upd.mymodule.php file Add the following to your update method. Be sure to set the path to your updates files and your hook template. The hook template will be used for all hooks added via the addHooks() method.

```
<?php

use Basee\Updater;

class MyModule {

    /**
     * Module Updater
     *
     * @param string $current
     * @return bool true
     */
    public function update($current = '')
    {
        $updater = new Updater();
        $updater
            ->setFilePath(PATH_THIRD.'mymodule/updates')
            ->setHookTemplate([
                'class' => MyModule,
                'settings' => '',
                'priority' => 5,
                'version' => 1.0,
                'enabled' => 'y',
            ])
            ->fetchUpdates($current)
            ->runUpdates();

        return true;
    }
}
```

Create an updates folder in your addon, e.g. mymodule/updates/up_2_00_00.php (you defined the path above in setFilePath). 

```
<?php

use Basee\Update\AbstractUpdate;

class Update_2_00_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        // Run some custom queries
        ee()->db->update('modules', ['has_publish_fields' => 'y'], ['module_name' => 'Publisher']);
        ee()->db->query("ALTER TABLE `". ee()->db->dbprefix ."publisher_titles` CHANGE COLUMN `publisher_lang_id` `lang_id` int(4) DEFAULT null");
        ee()->db->query("ALTER TABLE `". ee()->db->dbprefix ."publisher_titles` CHANGE COLUMN `publisher_status` `status` varchar(24) DEFAULT null");

        /** @var EntryTranslation $entryTranslation */
        $entryTranslation = ee('Model')->make(EntryTranslation::NAME);
        $entryTranslation->createTable();

        // Add new hooks
        $this->addHooks([
            ['hook'=>'before_channel_entry_save', 'method'=>'before_channel_entry_save'],
            ['hook'=>'after_channel_entry_save', 'method'=>'after_channel_entry_save'],
        ]);

        // Remove existing hooks
        $this->removeHooks('MyModule_ext', [
            'entry_submission_ready',
            'publish_form_channel_preferences',
        ]);

        // Run another method in this file that might do something more complicated.
        $this->updateSettings();
    }
```
