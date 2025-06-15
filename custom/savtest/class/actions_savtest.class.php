<?php

/**
 * Class ActionsSavTest
 * Contains hook functions for the SavTest module.
 */
class ActionsSavTest
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public    $resprints;
    protected $hookmanager; // Hook manager

    /**
     * Constructor
     * @param DoliDB $db Database handler
     * @param HookManager $hookmanager Hook manager
     */
    public function __construct($db, $hookmanager = null)
    {
        $this->db = $db;
        $this->hookmanager = $hookmanager; // Store the hookmanager if passed
    }

    /**
     * Adds the SAV Test tab to the order card's tab head.
     * Hook: completeTabsHead
     * Context: commande_card (from modSavTest.class.php)
     *
     * @param array         $parameters     Hook metadata (context, etc...)
     * @param Commande      $object         The order object (type hint based on context)
     * @param string        $action         Current action (create, edit, null)
     * @param HookManager   $hookmanager    Hook manager
     * @return int                          0 on success, -1 on error
     */
    public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf, $user;

        // Ensure this hook is for the 'commande' element (sales order)
        // The hook registration in modSavTest.class.php should already ensure this,
        // but an extra check can be useful if the hook was more generic.
        if (isset($object->element) && $object->element == 'commande') {
            // Load module language file for the tab title
            $langs->load("savtest@savtest");

            // Get the array of current tabs
            // In Dolibarr, $parameters['head'] is often the array of tabs
            // For 'completeTabsHead', $parameters directly is the array of tabs.
            $head = &$parameters; // $parameters is passed by reference and is the $head array itself.

            if (is_array($head)) {
                // Check if tab already exists to prevent duplicates (e.g., during AJAX reloads)
                $tabExists = false;
                foreach ($head as $tab) {
                    if (isset($tab[2]) && $tab[2] == 'savtest_tab') { // Use a unique key for our tab
                        $tabExists = true;
                        break;
                    }
                }

                if (!$tabExists) {
                    // Add the new tab
                    // Structure of a tab: [URL, Title, Unique Key/ID]
                    // The position in the array determines the order. Appending adds it to the end.
                    $newTab = array();
                    $newTab[0] = dol_buildpath('/custom/savtest/sav_content.php', 1) . '?id=' . $object->id;
                    $newTab[1] = $langs->trans("SavTestTabTitle"); // Translated tab title
                    $newTab[2] = 'savtest_tab'; // Unique key for this tab

                    // Add the tab to the head array
                    // $head[] = $newTab; // Appends to the end
                    // Or, to insert at a specific position (e.g., before 'info' or 'documents' tab if they exist)
                    // For simplicity, append it. The order can be fine-tuned later if needed.

                    // Find the index of the 'Documents' or 'Info' tab to insert before it
                    $insertPos = count($head); // Default to end
                    $referenceTabs = array('documents', 'info', 'shipping', 'contact'); // Potential tabs to insert before

                    foreach ($referenceTabs as $refTabKey) {
                        foreach ($head as $index => $existingTab) {
                            if (isset($existingTab[2]) && $existingTab[2] == $refTabKey) {
                                $insertPos = $index +1; // Insert after this known tab
                                // If you want to insert before, just use $index
                                break 2; // Break both loops
                            }
                        }
                    }
                     // Insert the new tab at the determined position
                    array_splice($head, $insertPos, 0, array($newTab));


                }
            }
        }
        return 0; // Must return 0 for success
    }

    // You can add other hook methods here if needed by your module
    // For example, if you had 'formObjectOptions' hook in your descriptor:
    /*
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf;
        if (in_array($parameters['context'], array('ordercard'))) {
            $langs->load('savtest@savtest');
            $this->resprints .= '<a href="#">My Custom Option</a>';
        }
        return 0;
    }
    */
}
