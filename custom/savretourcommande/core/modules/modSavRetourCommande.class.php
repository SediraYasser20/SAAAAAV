<?php

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Class modSavRetourCommande
 *
 * Module descriptor for displaying SAV (After-Sales Service) related extra fields
 * in a dedicated tab on the sales order card.
 */
class modSavRetourCommande extends DolibarrModules
{
    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->numero         = 500001; // Unique module ID (from >500000 range for custom/unreserved modules)
        $this->rights_class   = 'savretourcommande'; // Optional: For specific permissions, can be same as module name
        $this->family         = 'crm'; // Or 'orders', 'products', 'tools' - 'crm' or 'tools' seems appropriate
        $this->name           = preg_replace('/^mod/i', '', get_class($this)); // Will be 'SavRetourCommande'
        $this->description    = 'Displays SAV-related extra fields (savorders_sav, savorders_status, savorders_history) in a new tab on the sales order card.';
        $this->version        = '1.0.0';
        $this->const_name     = 'MAIN_MODULE_' . strtoupper(preg_replace('/^mod/i', '', get_class($this))); // MAIN_MODULE_SAVRETOURCOMMANDE
        $this->picto          = 'aftersale.png'; // Using 'aftersale.png' if available, otherwise 'service.png' or a generic icon
        $this->module_parts   = array('css' => array('/savretourcommande/css/savretourcommande.css')); // Optional CSS

        // Data directories to create when module is enabled (none needed for now)
        $this->dirs           = array();

        // Dependencies
        $this->depends        = array(); // No specific module dependencies
        $this->required_by    = array(); // Not required by any other module

        // Config pages. Put here list of php page, relative to module root path, to manage module setup.
        $this->config_page_url = array('admin/setup.php@'.preg_replace('/^mod/i', '', get_class($this)));

        // Constants (none needed for now)
        $this->const          = array();

        // Triggers (none needed for now, using $this->tabs instead for simplicity)
        $this->triggers       = array();

        // Hooks
        // $this->hooks['commande']['completeTabsHead'] = '$this->completeTabsHeadOrder'; // Example if using Actions class
        // Using $this->tabs for direct tab addition as per Dolibarr documentation for simple cases
        $this->hooks = array(); // Keep empty if using $this->tabs

        // Tabs
        // Definition: 'objecttype:+tabname:Title (can be lang key):LangFile@ModuleDir:$user->rights->perm->read:/path/to/tab_content.php?id=__ID__'
        $this->tabs = array(
            'order:+savretourcommandetab:SavRetourCommandeTabTitle:savretourcommande@savretourcommande:$user->rights->commande->lire:/custom/savretourcommande/savretourcommande_tab.php?id=__ID__'
        );

        // Permissions (none specifically defined for this module's functionality beyond standard object access)
        // If specific rights are needed, they would be defined here.
        // For example:
        // $this->rights = array();
        // $this->rights[0][0] = $this->numero + 1; // A unique ID for this permission
        // $this->rights[0][1] = 'Read SAV Tab Data'; // Label for this permission
        // $this->rights[0][3] = 1; // Default: 0 for no, 1 for yes
        // $this->rights[0][4] = 'lire'; // Short name for the permission
        // $this->rights[0][5] = ''; // Optional sub-permission
        $this->rights = array();


        // Menu entries (none needed for this module as it only adds a tab)
        $this->menu = array();

        // Language files
        $this->langfiles = array('savretourcommande@savretourcommande');
    }

    /**
     * Init function. Called when module is enabled.
     * The init function may add or update data in database.
     *
     * @param  string $options Options when enabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        // The extra fields are assumed to be pre-existing as per the issue description.
        // If they needed to be created, the code would go here, for example:
        // $extrafields = new ExtraFields($this->db);
        // $extrafields->addExtraField('savorders_sav', $langs->trans("SAVOrderSAV"), 'boolean', 100, '', 'commande', array('params' => array()), 0, 0, $this->const_name);
        // $extrafields->addExtraField('savorders_status', $langs->trans("SAVOrderStatus"), 'select', 101, 'your:list,of:options', 'commande', array('params' => array()), 0, 0, $this->const_name);
        // $extrafields->addExtraField('savorders_history', $langs->trans("SAVOrderHistory"), 'text', 102, '', 'commande', array('params' => array()), 0, 0, $this->const_name);

        // sql_scaffold($this->sqlpaths[0], $options); // If you have SQL files for table creation

        return 1; // Return 1 if OK, 0 if KO
    }

    /**
     * Remove function. Called when module is disabled.
     * Data must be deleted if it was added by module.
     *
     * @param  string $options Options when disabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        // Logic to remove any data, tables, or constants created by the module.
        // Since we are not creating extra fields here (they are pre-existing),
        // we don't remove them either.
        // Example: delConstants($this->const_name);
        return 1; // Return 1 if OK, 0 if KO
    }
}
