<?php

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }
if (!$res && file_exists("../../../../main.inc.php")) { $res = @include "../../../../main.inc.php"; }
if (!$res && file_exists("../../../../../main.inc.php")) { $res = @include "../../../../../main.inc.php"; }
if (!$res) { die("Include of main fails"); }

$langs->loadLangs(array("admin", "savretourcommande@savretourcommande"));

$module_name = $langs->trans("ModuleSavRetourCommandeName");
$module_desc = $langs->trans("ModuleSavRetourCommandeDesc");
// Assuming your module class is modSavRetourCommande and it's loaded or can be instantiated
// For simplicity, we might hardcode version here or retrieve from module descriptor if easily accessible
$module_version = "1.0.0"; // Or fetch dynamically if module object is available

llxHeader('', $langs->trans("About").' '.$module_name);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print dol_get_fiche_head($langs->trans("About").' '.$module_name, '', '', 0, $linkback);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield" width="100%">';

print '<tr class="oddeven"><td width="200">'.$langs->trans("ModuleName").'</td><td>'.$module_name.'</td></tr>';
print '<tr class="oddeven"><td width="200">'.$langs->trans("Description").'</td><td>'.$module_desc.'</td></tr>';
print '<tr class="oddeven"><td width="200">'.$langs->trans("Version").'</td><td>'.$module_version.'</td></tr>';
print '<tr class="oddeven"><td width="200">'.$langs->trans("Author").'</td><td>Jules (AI Agent)</td></tr>';
print '<tr class="oddeven"><td width="200">'.$langs->trans("ModuleHome").'</td><td><a href="https://github.com/user/repo" target="_blank">Your Module Link (if any)</a></td></tr>'; // Replace with actual link if you have one

print '</table>';

print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();

?>
