<?php

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) { // From htdocs/custom/savtest/
    if (false === (@include '../../../main.inc.php')) { // From htdocs/custom/savtest/xxx/
        if (false === (@include '../../../../main.inc.php')) { // From htdocs/custom/savtest/xxx/yyy/
             die("Global Dolibarr main.inc.php file not found.");
        }
    }
}

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php'; // For Form class

// Load translation files for this module and standard areas
$langs->loadLangs(array("orders", "companies", "savtest@savtest"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha'); // Not directly used for fetch but good practice
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

// Initialize objects
$object = new Commande($db);
$extrafields = new ExtraFields($db); // Initialize ExtraFields
$hookmanager->initHooks(array('ordercard', 'globalcard')); // For other potential hooks on the page

// Load object (order)
if ($id > 0 || !empty($ref)) {
    $result = $object->fetch($id, $ref);
    if ($result > 0) {
        $object->fetch_thirdparty();
        // Fetch extrafields
        $object->fetch_optionals();
    } else {
        dol_print_error($db, $object->error);
        exit;
    }
} else {
    dol_print_error($db, "Error: Missing ID or Ref for order.");
    exit;
}

// Define permissions (standard for orders)
$permissiontoread = $user->hasRight('commande', 'lire');
$permissiontowrite = $user->hasRight('commande', 'creer'); // Or 'write' if more granular needed

// Security check
if (!$permissiontoread) {
    accessforbidden();
}

/*
 * Actions
 */
if ($action == 'update_sav_test_info' && !$cancel && $permissiontowrite) {
    $error = 0;

    // Update the savorders_sav extrafield
    // Extrafields are stored in array_options with prefix 'options_'
    $object->array_options['options_savorders_sav'] = GETPOST('savorders_sav', 'int') ? 1 : 0;

    // Update the object with its extrafields
    // When only extrafields are modified, insertExtraFields() is usually sufficient and safer.
    // $result = $object->update($user); // This updates the whole object, might be too broad
    $result = $object->insertExtraFields();


    if ($result >= 0) {
        setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
        // Redirect to the same page to avoid form resubmission issues
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
        $error++;
    }
} elseif ($action == 'update_sav_test_info' && !$permissiontowrite) {
    setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
}


/*
 * View
 */
$form = new Form($db);

$title = $langs->trans("Order") . ' - ' . $langs->trans("SavTestTabTitle");
llxHeader('', $title);

if ($id > 0 || !empty($ref)) {
    // Standard Dolibarr tab setup
    // We need to find a robust way to get the standard tabs for an order.
    // The 'actions_savtest.class.php' adds our tab. Here we just display them.

    // This is a simplified way to show tabs. A more robust way might involve calling a lib function.
    // For now, we rely on the hook in actions_savtest.class.php to have added our tab.
    // The dol_get_fiche_head function will render the tabs.

    $head = array(); // This will be populated by Dolibarr's core logic for tabs
    // Our tab 'savtest_tab' should be in $head if the hook worked.

    // Prepare head array (example from commande/card.php)
    // This is usually handled by a lib like order_prepare_head, but let's try a minimal version
    // The actual tabs are usually prepared by core functions or specific object `prepare_head` methods.
    // We pass our unique tab key 'savtest_tab' to mark it active.

    // Let's use a common function if available, or build manually.
    // For Dolibarr 12+, order_prepare_head is available.
    // For older versions, it might be different. The issue specifies v21.0.1.
    if (function_exists('commande_prepare_head')) {
         $head = commande_prepare_head($object);
    } else {
        // Fallback for older versions or if function is not found (less likely for v21)
        $head = array();
        $h=0;
        $head[$h][0] = dol_buildpath('/commande/card.php', 1).'?id='.$object->id;
        $head[$h][1] = $langs->trans("Card");
        $head[$h][2] = 'card';
        $h++;
        // ... manually add other tabs like contacts, notes, etc.
        // Then add our tab:
        $head[$h][0] = dol_buildpath('/custom/savtest/sav_content.php', 1).'?id='.$object->id;
        $head[$h][1] = $langs->trans("SavTestTabTitle");
        $head[$h][2] = 'savtest_tab'; // The key we used in actions class
        $h++;
    }

    print dol_get_fiche_head($head, 'savtest_tab', $langs->trans("CustomerOrder"), -1, 'order');

    // Standard Banner
    $linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
    $morehtmlref = ''; // Can add more info here if needed
    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    // SAV Information Form
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update_sav_test_info">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';

    print '<table class="border centpercent tableforfield">';

    // SAV Active Checkbox (savorders_sav)
    print '<tr>';
    print '<td class="titlefield">'.$langs->trans("SavOrdersSav").'</td>';
    print '<td>';
    // Ensure array_options is an array and the key exists before accessing
    $sav_active_value = (isset($object->array_options['options_savorders_sav']) ? $object->array_options['options_savorders_sav'] : 0);
    print $form->selectyesno('savorders_sav', $sav_active_value, 1, !$permissiontowrite);
    print '</td>';
    print '</tr>';

    // SAV Status (Read-only, placeholder)
    print '<tr>';
    print '<td class="titlefield">'.$langs->trans("SavStatusLabel").'</td>';
    print '<td>';
    print $langs->trans("SavStatusPlaceholder");
    print '</td>';
    print '</tr>';

    // SAV History (Read-only, placeholder)
    print '<tr>';
    print '<td class="titlefield">'.$langs->trans("SavHistoryLabel").'</td>';
    print '<td>';
    // For multi-line placeholder, could use nl2br or a div
    print $langs->trans("SavHistoryPlaceholder");
    print '</td>';
    print '</tr>';

    print '</table>';

    // Action buttons
    if ($permissiontowrite) {
        print '<div class="center tabsAction">';
        print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
        if (GETPOST('backtopage', 'alpha')) { // If a backtopage is provided
            print ' &nbsp; &nbsp; ';
            print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
        }
        print '</div>';
    }

    print '</form>';

    print '</div>'; // End fichecenter
    print dol_get_fiche_end();

} else {
    print $langs->trans("ErrorRecordNotFound");
}

llxFooter();
$db->close();

?>
