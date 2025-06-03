<?php
/* ------------------------------------------------------------------------
 * Copyright (C) 2017-2025 Your Name / Your Company
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ------------------------------------------------------------------------
 */

/**
 *  \file       receptionshowroom1_list.php
 *  \ingroup    receptionshowroom1
 *  \brief      List page for Receptionshowroom1 objects without permission checks
 */

require_once __DIR__ . '/../../main.inc.php'; // Load Dolibarr environment

// Load required libraries and classes
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

require_once __DIR__ . '/class/receptionshowroom1.class.php';

// Load translations
$langs->load('receptionshowroom1@receptionshowroom1');

// No permission checks here

// Initialize variables from GET/POST
$action = GETPOST('action', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = max(GETPOST('page', 'int'), 1);
$limit = $conf->liste_limit;

// Search parameters
$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_date = GETPOST('search_date', 'alpha');

// Reset filters
if ($action === 'clear') {
    $search_ref = '';
    $search_label = '';
    $search_date = '';
    $page = 1;
}

// Instantiate object and helpers
$object = new Receptionshowroom1($db);
$form = new Form($db);
$extrafields = new ExtraFields($db);

// Prepare SQL parts for search and filtering
$sql = "SELECT r.rowid, r.ref, r.label, r.date_creation, r.fk_user_author";
$sql .= ", u.login as user_author_name";

// Join user table for author name
$sql .= " FROM " . MAIN_DB_PREFIX . "receptionshowroom1 as r";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON r.fk_user_author = u.rowid";

// Where clauses
$where = [];
if ($search_ref) {
    $where[] = "r.ref LIKE '%" . $db->escape($search_ref) . "%'";
}
if ($search_label) {
    $where[] = "r.label LIKE '%" . $db->escape($search_label) . "%'";
}
if ($search_date) {
    $where[] = "r.date_creation = '" . $db->escape($search_date) . "'";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

// Sorting
if ($sortfield) {
    $sql .= " ORDER BY " . $db->escape($sortfield) . " " . $db->escape($sortorder);
} else {
    $sql .= " ORDER BY r.rowid DESC";
}

// Pagination
$offset = ($page - 1) * $limit;
$sql .= " LIMIT $limit OFFSET $offset";

// Execute query
$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

// Count total for pagination
$sqlcount = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "receptionshowroom1 as r";
if (!empty($where)) {
    $sqlcount .= " WHERE " . implode(' AND ', $where);
}
$rescount = $db->query($sqlcount);
$objcount = $db->fetch_object($rescount);
$total = $objcount ? $objcount->nb : 0;

// Start page output
llxHeader('', $langs->trans('Receptionshowroom1List'));

// Title and button to create new object
print load_fiche_titre($langs->trans('Receptionshowroom1List'), '', 'receptionshowroom1@receptionshowroom1');

// Button to create new object (always shown)
print '<div class="tabsAction">';
print '<a class="butAction" href="receptionshowroom1_card.php?action=create">' . $langs->trans('NewReceptionshowroom1') . '</a>';
print '</div>';

// Search form
print '<form method="GET" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<table class="noborder" width="100%"><tr class="liste_titre">';
print '<td>' . $langs->trans('Ref') . '</td>';
print '<td>' . $langs->trans('Label') . '</td>';
print '<td>' . $langs->trans('DateCreation') . '</td>';
print '<td></td>';
print '</tr><tr class="liste_titre">';
print '<td><input class="flat" type="text" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';
print '<td><input class="flat" type="text" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
print '<td><input class="flat" type="date" name="search_date" value="' . dol_escape_htmltag($search_date) . '"></td>';
print '<td><input type="submit" class="button" value="' . $langs->trans('Search') . '">';
print ' <input type="submit" class="button" name="action" value="' . $langs->trans('Clear') . '"></td>';
print '</tr></table>';
print '</form>';

// Table header
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Ref') . '</td>';
print '<td>' . $langs->trans('Label') . '</td>';
print '<td>' . $langs->trans('DateCreation') . '</td>';
print '<td>' . $langs->trans('Author') . '</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// Loop on results
if ($resql && $db->num_rows($resql) > 0) {
    while ($obj = $db->fetch_object($resql)) {
        print '<tr class="oddeven">';
        print '<td><a href="receptionshowroom1_card.php?id=' . $obj->rowid . '">' . dol_escape_htmltag($obj->ref) . '</a></td>';
        print '<td>' . dol_escape_htmltag($obj->label) . '</td>';
        print '<td>' . dol_print_date(dol_stringtotime($obj->date_creation, 'dayhour'), 'dayhour') . '</td>';
        print '<td>' . dol_escape_htmltag($obj->user_author_name) . '</td>';
        print '<td>';
        print '<a href="receptionshowroom1_card.php?id=' . $obj->rowid . '&action=edit">' . img_edit() . '</a> ';
        print '<a href="receptionshowroom1_card.php?id=' . $obj->rowid . '&action=delete" class="deletefield">' . img_delete() . '</a>';
        print '</td>';
        print '</tr>';
    }
} else {
    print '<tr><td colspan="5">' . $langs->trans('NoRecordFound') . '</td></tr>';
}

print '</table>';

// Pagination
$param = '';
if ($search_ref) $param .= '&search_ref=' . urlencode($search_ref);
if ($search_label) $param .= '&search_label=' . urlencode($search_label);
if ($search_date) $param .= '&search_date=' . urlencode($search_date);

print '<div class="center">';
print $form->showPagination($page, $langs->trans('Page'), $total, $limit, $_SERVER['PHP_SELF'] . '?action=' . $action . $param);
print '</div>';

// End of page
llxFooter();
$db->close();
