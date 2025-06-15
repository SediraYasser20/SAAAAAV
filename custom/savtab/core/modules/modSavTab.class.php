<?php
// File: custom/savtab/core/modules/modSavTab.class.php

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Class modSavTab
 * Module descriptor for adding an SAV tab to sales orders.
 */
class modSavTab extends DolibarrModules
{
    /**
     * Constructor.
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->numero         = 104000; // Unique module ID
        $this->rights_class   = 'savtab';
        $this->family         = 'tools';
        $this->name           = 'savtab';
        $this->description    = 'Adds a SAV tab on the Order card';
        $this->version        = '1.0.0';
        $this->const_name     = 'MAIN_MODULE_SAVTAB';
        $this->picto          = 'service';

        // Data directories to create when module is enabled
        $this->dirs           = array('savtab/temp');

        // Dependencies
        $this->depends        = array();
        $this->required_by    = array();

        // Constants (none)
        $this->const          = array();

        // Hooks for adding tab
        $this->hooks          = array('completeTabsHead');

        // Module parts (none)
        $this->module_parts   = array();

        // Permissions (none)
        $this->rights         = array();

        // Menus (none)
        $this->menu           = array();

        // Export code (none)
        $this->export_code    = array();

        // Cronjobs (none)
        $this->cronjobs       = array();

        // Config pages
        $this->config_page_url = array();

        // Language files
        $this->langfiles      = array('savtab@savtab');
    }

    /**
     * Enable module and set up extra fields.
     *
     * @param string $options Options when enabling module
     * @return int            1 on success, 0 on failure
     */
    public function init($options = '')
    {
        global $db, $langs;

        // Load module language
        $langs->load("savtab@savtab");

        // Initialize extra fields
        $extrafields = new ExtraFields($db);

        // Add SAV Order field (boolean)
        $extrafields->addExtraField('savorders_sav', $langs->trans("SAVOrder"), 'boolean', 100, '', 'commande');

        // Add SAV Status field (varchar)
        $extrafields->addExtraField('sav_status', $langs->trans("SAVStatus"), 'varchar', 101, '255', 'commande');

        // Add SAV History field (text)
        $extrafields->addExtraField('sav_history', $langs->trans("SAVHistory"), 'text', 102, '', 'commande');

        return 1;
    }

    /**
     * Disable module.
     *
     * @param string $options Options when disabling module
     * @return int            1 on success
     */
    public function remove($options = '')
    {
        return 1;
    }
}