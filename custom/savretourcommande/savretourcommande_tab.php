<?php

// Ensure this path is correct for your Dolibarr installation structure
// It tries to find main.inc.php from the current script's location.
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) { // Assuming htdocs/custom/module/file.php
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) { // Assuming htdocs/custom/module/subdir/file.php
    $res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) { // Assuming htdocs/custom/module/subdir/subdir/file.php
    $res = @include "../../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php'; // For $form object if needed for display consistency

// Load order library (optional, but good practice if using order_prepare_head)
$order_lib_loaded = false;
$order_lib_paths = array(
    DOL_DOCUMENT_ROOT.'/commande/lib/commande.lib.php', // Older path
    DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php',      // Newer path
    DOL_DOCUMENT_ROOT.'/commande/lib/order.lib.php'   // Alternative older path
);
foreach ($order_lib_paths as $lib_path) {
    if (file_exists($lib_path)) {
        require_once $lib_path;
        $order_lib_loaded = true;
        break;
    }
}


// Load translation files required by page
$langs->loadLangs(array("orders", "companies", "savretourcommande@savretourcommande"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09'); // Not used in this read-only tab, but good practice

// Initialize objects
$object = new Commande($db);
$extrafields = new ExtraFields($db);
$form = new Form($db); // For potential use in displaying fields consistently

// Initialize hooks
$hookmanager->initHooks(array('ordercard', 'globalcard')); // 'ordercard' is relevant

// Fetch object and its Complementary Attributes
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // This script should fetch the object based on $id

if ($object->id) {
    // Fetch extra fields
    $extrafields->fetch_name_optionals_label($object->table_element); // Get names of extra fields
    $object->fetch_optionals(); // Fetch values of extra fields for this object
}


// Permissions
if (!$user->rights->commande->lire) {
    accessforbidden();
}
if ($user->socid > 0) { // External user not allowed
    accessforbidden();
}
if (!isModEnabled('commande')) {
    accessforbidden($langs->trans("ModuleDisabled", $langs->trans("Orders")));
}


/*
 * View
 */

$title = $langs->trans("Order").' - '.$langs->trans("SavRetourCommandeTabTitle");
llxHeader('', $title);

if ($object->id) {
    $object->fetch_thirdparty(); // Fetch thirdparty details for the banner

    // Prepare head array for tabs
    if ($order_lib_loaded && function_exists('order_prepare_head')) {
        $head = order_prepare_head($object, $user);
    } else {
        // Fallback or simplified head preparation if specific lib/func not found
        $head = array();
        $h = 0;
        $head[$h][0] = DOL_URL_ROOT.'/commande/card.php?id='.$object->id;
        $head[$h][1] = $langs->trans("OrderCard");
        $head[$h][2] = 'order';
        $h++;
        // Add other standard tabs if necessary, or rely on the module descriptor's tab definition
        // For this custom tab, ensure its entry is present or correctly highlighted by dol_get_fiche_head
    }

    // Display the standard Dolibarr card header with tabs
    print dol_get_fiche_head($head, 'savretourcommandetab', $langs->trans("CustomerOrder"), -1, 'order');

    // Banner with object ref and link back to list
    $linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>'; // Standard under banner div

    print '<table class="border tableforfield" width="100%">';

    // SAV Order SAV (Boolean)
    print '<tr><td class="titlefield">'.$langs->trans("SAVOrderSAV").'</td><td>';
    $sav_value = isset($object->array_options['options_savorders_sav']) ? $object->array_options['options_savorders_sav'] : null;
    if ($sav_value !== null) {
        print ($sav_value ? $langs->trans("Yes") : $langs->trans("No"));
    } else {
        print '<span class="opacitymedium">'.$langs->trans("ValueNotSet").'</span>';
    }
    print '</td></tr>';

    // SAV Order Status (List)
    print '<tr><td class="titlefield">'.$langs->trans("SAVOrderStatus").'</td><td>';
    $status_key = isset($object->array_options['options_savorders_status']) ? $object->array_options['options_savorders_status'] : '';
    if (!empty($status_key)) {
        // Use showOutputField to correctly display the label for the list type extrafield
        print $extrafields->showOutputField(array('code'=>'savorders_status', 'type'=> $extrafields->attributes['commande']['fields']['savorders_status']['type']), $status_key, $object->id);
    } else {
        print '<span class="opacitymedium">'.$langs->trans("ValueNotSet").'</span>';
    }
    print '</td></tr>';

    // SAV Order History (Long Text)
    print '<tr><td class="titlefield">'.$langs->trans("SAVOrderHistory").'</td><td>';
    $history_value = isset($object->array_options['options_savorders_history']) ? $object->array_options['options_savorders_history'] : '';
    if (!empty(trim($history_value))) {
        print nl2br(dol_escape_htmltag($history_value));
    } else {
        print '<span class="opacitymedium">'.$langs->trans("ValueNotSet").'</span>';
    }
    print '</td></tr>';

    print '</table>';

    print '</div>'; // End fichecenter
    print dol_get_fiche_end(); // End of the Dolibarr card display

} else {
    // Object not found or error
    dol_print_error($db, $langs->trans("ErrorRecordNotFound"));
}

llxFooter();
$db->close();

?>
