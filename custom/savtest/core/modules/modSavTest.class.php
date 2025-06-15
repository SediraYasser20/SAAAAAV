<?php

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php'; // Required for extrafields

/**
 * Class modSavTest
 * Module descriptor for adding an SAV Test tab to sales orders.
 */
class modSavTest extends DolibarrModules
{
    /**
     * Constructor.
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->numero         = 104001; // Unique module ID (ensure this is unique if other custom modules exist)
        $this->rights_class   = 'savtest'; // Corresponds to 'perms_mypermission' in permission names like 'mypermissionread', 'mypermissionwrite'
        $this->family         = 'crm'; // Or 'products', 'projects', 'financial', 'ecm', 'technic', 'other', 'transverse'
        $this->name           = preg_replace('/^mod/i', '', get_class($this)); // Module name without 'mod' prefix
        $this->description    = 'ModuleSavTestDescription'; // From lang file
        $this->version        = '1.0.0'; // Module version
        $this->const_name     = 'MAIN_MODULE_' . strtoupper($this->name); // Constant name to enable/disable module
        $this->picto          = 'generic'; // Default picto
        $this->module_parts   = array(); // No sub-modules

        // Data directories to create when module is enabled (Relative to dolibarr_main_data_root)
        // Example: $this->dirs = array("mymodule/temp");
        $this->dirs           = array();

        // Dependencies
        $this->depends        = array(); // Example: array('modOtherModule')
        $this->required_by    = array(); // Example: array('modAnotherModule')
        $this->conflictwith   = array(); // Example: array('modConflictingModule')

        // Config pages. Put here list of php page, stored in admin directory, to manage settings of module.
        $this->config_page_url = array(); // Example: array("mysetuppage.php@mymodule")

        // Constants
        // List of particular constants to add when module is enabled
        // Example: $this->const = array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','Comment for const 1',0,'',0));
        $this->const          = array();

        // Permissions
        // List of permissions that can be granted to users for this module
        // Example: $this->rights = array(0=>array('mypermissionread','Read permission','r'), 1=>array('mypermissionwrite','Write permission','w'));
        $this->rights         = array(); // No specific permissions for this module

        // Menu entries
        // List of menu entries to add when module is enabled
        // Example: $this->menu = array(0=>array('menutype'=>'top','module'=>'mymodule','mainmenu'=>'mymainmenu','leftmenu'=>'myleftmenu','url'=>'/mymodule/mynewpage.php','langs'=>'mylangfile','position'=>100,'enabled'=>'1','perms'=>'1','target'=>'','user'=>2));
        $this->menu           = array(); // No menu entries for this module

        // Hooks
        // List of hooks to add when module is enabled
        // Example: $this->hooks = array('hookcontext1','hookcontext2');
        $this->hooks          = array(
            'commande_card' // Context for sales order card
        );
        // The hook file must be class/actions_mymodule.class.php with a method name equals to the hook name without the first part (before the underscore).
        // Example: For a hook 'substitutions_completesubstitutionarray', the method name will be completesubstitutionarray.
        // The method signature for sales order card tabs is usually:
        // public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
        // However, the issue specified formObjectOptions. Let's try with completeTabsHead first as per savtab example for adding a main tab.
        // If we use 'commande_card' as context, we need to refine which specific hook from that context.
        // For adding a main tab, 'completeTabsHead' (used by savtab) or 'addHomeTab' or similar might be candidates.
        // The issue mentioned 'formObjectOptions' but this is usually for adding fields/options within a form, not a top-level tab.
        // Let's stick to 'completeTabsHead' as it's proven for adding tabs in the example.
        // The hook manager will look for a method named 'completeTabsHead' in actions_savtest.class.php
        // The actual hook name to register is 'completeTabsHead'
         $this->registerHook('completeTabsHead', 'commande');


        // Language files
        $this->langfiles      = array('savtest@savtest');

        // Boxes
        // List of boxes
        // Example: $this->boxes = array(0=>array('file'=>'myboxfile.php@mymodule','note'=>'Comment for box 1','enabled'=>'1','position'=>1));
        $this->boxes          = array();

        // Cronjobs
        // List of cron jobs to add when module is enabled
        // Example: $this->cronjobs = array(0=>array('label'=>'MyJob','jobtype'=>'method','class'=>'/mymodule/class/myobject.class.php','objectname'=>'MyObject','method'=>'doScheduledThings','parameters'=>'','comment'=>'Comment','frequency'=>2,'unitfrequency'=>3600,'status'=>0,'test'=>true));
        $this->cronjobs       = array();

        // Triggers
        // List of triggers to add when module is enabled
        // Example: $this->triggers = array(0=>array('modNomModule','MAMETHODE','MYTRIGGERCODE','dol_include_once("/mymodule/core/triggers/interface_mymodule_mytrigger.class.php");'));
        $this->triggers       = array();

        // Exports
        // List of export profiles
        // Example: $this->export_code = array(0=>array('label'=>'MyExport','module'=>'mymodule','model'=>'myexportmodel','prefix'=>'MYEXPORT_','version'=>1,'active'=>1));
        $this->export_code    = array();

        // Default settings
        // $this->default_settings = array("MYMODULE_OPTION_1"=>"value1", "MYMODULE_OPTION_2"=>"value2");

        // Translations
        // $this->translations = array("fr_FR" => array("MyModule" => "Mon Module", "MyProduct" => "Mon Produit"));
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus (defined in constructor)
     * Can also create data structure. Dolibarr will call this function at first activation of module.
     * Dolibarr will also call this function each time the module is upgraded.
     *
     * @param      string $options    Options when enabling module ('', 'noboxes')
     * @return     int                1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $db, $langs;

        // Load module language file for translations
        $langs->load("savtest@savtest");

        $sql = array(); // For ALTER TABLE or CREATE TABLE/INDEX commands

        // Create extrafields
        $extrafields = new ExtraFields($db);
        $res = $extrafields->addExtraField('savorders_sav', $langs->transnoentitiesnoconv("SavOrdersSav"), 'boolean', 100, '', 'commande', 0, 0, '', null, $this->const_name);
        if ($res < 0) {
            $this->error = $extrafields->error;
            return 0; // Fail init
        }

        // If you need to add more extrafields:
        // $res = $extrafields->addExtraField('my_other_field', "My Other Field Label", 'varchar', 101, 255, 'commande', 0, 0, '', null, $this->const_name);
        // if ($res < 0) { $this->error = $extrafields->error; return 0; }

        // Run SQL commands if any
        // foreach ($sql as $query) {
        //     $resql = $this->db->query($query);
        //     if (!$resql) {
        //         $this->error = $this->db->lasterror();
        //         return 0;
        //     }
        // }

        // Activate permissions (if any were defined in constructor)
        // $this->addRights();

        return parent::init($options); // parent::init will add constants, boxes, permissions and menus
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data structure are not removed.
     *
     * @param      string $options    Options when disabling module ('', 'noboxes')
     * @return     int                1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        // Remove extrafields (optional, set to 1 to remove)
        // Consider if you want to remove data on disable. Generally, it's better not to.
        // $extrafields = new ExtraFields($this->db);
        // $extrafields->deleteExtraField('savorders_sav', 'commande', 1);

        // Remove permissions (if any were defined in constructor)
        // $this->removeRights();

        return parent::remove($options); // parent::remove will remove constants, boxes, permissions and menus
    }
}
