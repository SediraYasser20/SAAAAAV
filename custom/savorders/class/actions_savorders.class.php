<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/savorders/class/savorders.class.php');

/**
 * Class Actionssavorders
 */
class Actionssavorders
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $user, $conf;

		$langs->loadLangs(array('stocks'));
        $langs->load('savorders@savorders');

		$savorders = new savorders($db);

		// d($parameters,false);

		$tmparray = ['receiptofproduct_valid', 'createdelivery_valid', 'deliveredtosupplier_valid', 'receivedfromsupplier_valid'];

		$ngtmpdebug = GETPOST('ngtmpdebug', 'int');
		if($ngtmpdebug) {
			echo '<pre>';
			print_r($parameters);
			echo '</pre>';
			
		    ini_set('display_startup_errors', 1);
			ini_set('display_errors', 1);
			error_reporting(-1);

			// echo '<pre>';
			// print_r($object);
			// echo '</pre>';
		}

		if ($object && (in_array('ordercard', explode(':', $parameters['context'])) || in_array('ordersuppliercard', explode(':', $parameters['context']))) && in_array($action, $tmparray)) {

			$error = 0;
			$now = dol_now();

			$savorders_date = '';

			global $savorders_date;

			$tmpdate = dol_mktime(0,0,0, GETPOST('savorders_datemonth','int'), GETPOST('savorders_dateday','int'), GETPOST('savorders_dateyear','int'));
			
			$savorders_date = dol_print_date($tmpdate, 'day');

			$cancel = GETPOST('cancel', 'alpha');

			$novalidaction = str_replace("_valid", "", $action);

			$s = GETPOST('savorders_data', 'array');

			$savorders_sav = $object->array_options["options_savorders_sav"];
	        $savorders_status = $object->array_options["options_savorders_status"];

	        if(!$savorders_sav || $cancel) return 0;

			$idwarehouse = isset($conf->global->SAVORDERS_ADMIN_IDWAREHOUSE) ? $conf->global->SAVORDERS_ADMIN_IDWAREHOUSE : 0;

			if(($novalidaction == 'receiptofproduct' || $novalidaction == 'deliveredtosupplier') && $idwarehouse <= 0) {
				$error++;
				$action = $novalidaction;
			}

			$commande = $object;

			$nblines = count($commande->lines);

			if($object->element == 'order_supplier') {
				$labelmouve = ($novalidaction == 'deliveredtosupplier') ? $langs->trans('ProductDeliveredToSupplier') : $langs->trans('ProductReceivedFromSupplier');
			} else {
				$labelmouve = ($novalidaction == 'receiptofproduct') ? $langs->trans('ProductReceivedFromCustomer') : $langs->trans('ProductDeliveredToCustomer');
			}

			$origin_element = '';
			$origin_id = null;

			if($object->element == 'order_supplier') {
				$mouvement = ($novalidaction == 'deliveredtosupplier') ? 1 : 0; // 0 : Add / 1 : Delete
			} else {
				$mouvement = ($novalidaction == 'receiptofproduct') ? 0 : 1; // 0 : Add / 1 : Delete
			}


			// $createdoc = $savorders->generateReceptionDocument($action, $commande);


			$texttoadd = '';
			if(isset($object->array_options["options_savorders_history"]))
				$texttoadd = $object->array_options["options_savorders_history"];


			if($novalidaction == 'createdelivery' || $novalidaction == 'receivedfromsupplier') {
				$texttoadd .= '<br>';
			}

			$oneadded = 0;

			if(!$error)
			for ($i = 0; $i < $nblines; $i++) {
				if (empty($commande->lines[$i]->fk_product)) {
					continue;
				}

				$objprod = new Product($db);
				$objprod->fetch($commande->lines[$i]->fk_product);

				if($objprod->type != Product::TYPE_PRODUCT) continue;

				$tmid = $commande->lines[$i]->fk_product;

				$warehouse 	= $s && isset($s[$tmid]) && isset($s[$tmid]['warehouse']) ? $s[$tmid]['warehouse'] : 0;
				// $batch 	= $s && isset($s[$tmid]) && isset($s[$tmid]['batch']) ? $s[$tmid]['batch'] : '';
				$qty 	= $s && isset($s[$tmid]) && isset($s[$tmid]['qty']) ? $s[$tmid]['qty'] : $commande->lines[$i]->qty;

				if($novalidaction == 'receiptofproduct' || $novalidaction == 'deliveredtosupplier') {
					$warehouse = $idwarehouse;
				}

				// if ($objprod->hasbatch() && !$batch) {
				// 	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
				// 	$error++;
				// }

				if(($novalidaction == 'createdelivery') && $warehouse <= 0) {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
					$error++;
				}

				$txlabelmovement = '(SAV) '.$objprod->ref .': '. $labelmouve;


				if ($objprod->hasbatch()) {

					$qty = ($qty > $commande->lines[$i]->qty) ? $commande->lines[$i]->qty : $qty;

					if($qty)
					for ($z=0; $z < $qty; $z++) { 
						$batch = $s && isset($s[$tmid]) && isset($s[$tmid]['batch'][$z]) ? $s[$tmid]['batch'][$z] : '';

						if(!$batch && $z == 0) {
							setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
							$error++;
							break;
						}

						if(!$error && $batch) {
							$result = $objprod->correct_stock_batch(
								$user,
								$warehouse,
								$tmpqty = 1,
								$mouvement,
								$txlabelmovement, // label movement
								$priceunit = 0,
								$d_eatby = '',
								$d_sellby = '',
								$batch,
								$inventorycode = '',
								$origin_element,
								$origin_id,
								$disablestockchangeforsubproduct = 0
							); // We do not change value of stock for a correction

							if($result > 0) {
								$this->addLineHistoryToSavCommande($texttoadd, $novalidaction, $objprod, $batch);
								$oneadded++;
							} else {
								$error++;
								break;
							}
						}

					}

				} else {
					if(!$error && $qty) {
						$result = $objprod->correct_stock(
							$user,
							$warehouse,
							$qty,
							$mouvement,
							$txlabelmovement,
							$priceunit = 0,
							$inventorycode = '',
							$origin_element,
							$origin_id,
							$disablestockchangeforsubproduct = 0
						); // We do not change value of stock for a correction

						if($result > 0) {
							$this->addLineHistoryToSavCommande($texttoadd, $novalidaction, $objprod);
							$oneadded++;
						} else {
							$error++;
							break;
						}
					}
				}

			}


			if(!$error && $oneadded) {

				if($object->element == 'order_supplier') {
					$savorders_status = ($novalidaction == 'deliveredtosupplier') ? $savorders::DELIVERED_SUPPLIER : $savorders::RECEIVED_SUPPLIER;
				} else {
					$savorders_status = ($novalidaction == 'receiptofproduct') ? $savorders::RECIEVED_CUSTOMER : $savorders::DELIVERED_CUSTOMER;
				}

				// d($texttoadd);

				$texttoadd = str_replace(['<span class="savorders_history_td">', '</span>'], ' ', $texttoadd);

				$extrafieldtxt = '<span class="savorders_history_td">';
				$extrafieldtxt .= $texttoadd;
				$extrafieldtxt .= '</span>';

	            $object->array_options["options_savorders_history"] = $extrafieldtxt;
	            $object->array_options["options_savorders_status"] = $savorders_status;
	            $result = $object->insertExtraFields();
	            if(!$result) $error++;
			}

			if($error){
				setEventMessages($objprod->errors, $object->errors, 'errors');
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action='.$novalidaction);
			} else {
				if($oneadded)
					setEventMessages($langs->trans("RecordCreatedSuccessfully"), null, 'mesgs');
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit();
			}

		}
	}

	
    public function addLineHistoryToSavCommande(&$texttoadd, $novalidaction, $objprod = '', $batch = '')
    {
    	global $langs, $savorders_date;

    	// $texttoadd = ($texttoadd) ? $texttoadd."<br>" : $texttoadd;
    	$texttoadd = $texttoadd;

    	$contenu = '- '.$savorders_date.': ';

    	if($novalidaction == 'receiptofproduct' || $novalidaction == 'receivedfromsupplier') {
			$contenu .= $langs->trans("OrderSavRecieveProduct");
		}
		elseif($novalidaction == 'createdelivery' || $novalidaction == 'deliveredtosupplier') {
			$contenu .= $langs->trans("OrderSavDeliveryProduct");
		}

		$contenu .= ' <a target="_blank" href="'.dol_buildpath('/product/card.php?id='.$objprod->id, 1).'">';
		$contenu .= '<b>'.$objprod->ref.'</b>';
		$contenu .= '</a>';

		if($batch) {
			$contenu .=  ' N° <b>'.$batch.'</b>';
		}

    	$texttoadd .=  '<div class="savorders_history_txt " title="'.strip_tags($contenu).'">';
		$texttoadd .= $contenu;
		$texttoadd .=  '</div>';

	}

	/**
     * @param   array         	$parameters     Hook metadatas (context, etc...)
     * @param   Commande    	$object         The object to process
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons($parameters, &$object, &$action = '')
    {
        global $db, $conf, $langs, $confirm, $user;

        $langs->load('admin');
        $langs->load('savorders@savorders');

        $form = new Form($db);

		$ngtmpdebug = GETPOST('ngtmpdebug', 'int');
		if($ngtmpdebug) {
			echo '<pre>';
			print_r($parameters);
			echo '</pre>';

		    ini_set('display_startup_errors', 1);
			ini_set('display_errors', 1);
			error_reporting(-1);
		}

        if (in_array('ordercard', explode(':', $parameters['context'])) || in_array('ordersuppliercard', explode(':', $parameters['context']))) {

			$s = GETPOST('savorders_data', 'array');

        	$linktogo = $_SERVER["PHP_SELF"].'?id=' . $object->id;

			$tmparray = ['receiptofproduct', 'createdelivery', 'deliveredtosupplier', 'receivedfromsupplier'];

			if(in_array($action, $tmparray)) {

				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('html, body').animate({
							scrollTop: ($("#savorders_formconfirm").offset().top - 80)
						}, 800);
					});
				</script>
				<?php

				if($object->element == 'order_supplier') {
					$title = ($action == 'deliveredtosupplier') ? $langs->trans('ProductDeliveredToSupplier') : $langs->trans('ProductReceivedFromSupplier');
				} else {
					$title = ($action == 'receiptofproduct') ? $langs->trans('ProductReceivedFromCustomer') : $langs->trans('ProductDeliveredToCustomer');
				}

				$formproduct = new FormProduct($db);

				$nblines = count($object->lines);
				
				// $formconfirm = '';
				// $newselectedchoice = 'yes';
				// $formquestion = array();
				// d($formquestion);
				// $formquestion = array(
				// 	array('type' => 'text', 'name' => 'note_private', 'label' => $langs->trans("Note"), 'value' => '')
				// 	,array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => '' )
				// 	,array('type' => 'other', 'name' => 'idwarehouse', 'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse', 'int') ?GETPOST('idwarehouse', 'int') : 'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
				// );
				// print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('Creerfacturesurmarge'), $langs->trans('ConfirmCreateFactureMarge', $commande->ref), 'confirm_facturemarge', $formquestion, 'yes', 1);

				$idwarehouse = isset($conf->global->SAVORDERS_ADMIN_IDWAREHOUSE) ? $conf->global->SAVORDERS_ADMIN_IDWAREHOUSE : 0;

				if($action == 'receiptofproduct' && $idwarehouse <= 0) {
					// $msgtoshow = ($action == "receiptofproduct") ? 'SelectWarehouseForStockIncrease' : 'SelectWarehouseForStockDecrease';
					// setEventMessages($langs->trans('SAV').': '.$langs->trans($msgtoshow).' '.$link, null, 'errors');
					
					$link = '<a href="'.dol_buildpath('savorders/admin/admin.php', 1).'" target="_blank">'.img_picto('', 'setup', '').' '.$langs->trans("Configuration").'</a>';
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans('SAV').' '.dol_htmlentitiesbr_decode($langs->trans('Warehouse'))).' '.$link, null, 'errors');
					$error++;

					// $action = $novalidaction;
				}


				print '<div class="tagtable paddingtopbottomonly centpercent noborderspacing savorders_formconfirm" id="savorders_formconfirm">';
				print_fiche_titre($title, '', $object->picto);

				$idwarehouse = isset($conf->global->SAVORDERS_ADMIN_IDWAREHOUSE) ? $conf->global->SAVORDERS_ADMIN_IDWAREHOUSE : 0;
				// $dware = ($action == 'receiptofproduct') ? $idwarehouse : 0;
				$dware = 0;

				$more = '';
				$more .= '<div class="tagtable paddingtopbottomonly centpercent noborderspacing savorders_formconfirm" id="savorders_formconfirm">';
				$more .= '<form method="POST" action="'.$linktogo.'" class="notoptoleftroright">'."\n";
				$more .= '<input type="hidden" name="action" value="'.$action.'_valid">'."\n";
				$more .= '<input type="hidden" name="token" value="'.(isset($_SESSION['newtoken']) ? $_SESSION['newtoken'] : '').'">'."\n";

				$now = dol_now();

				

				$more .= '<table class="valid centpercent">';
					
					$more .= '<tr>';
					$more .= '<td class="left"><b>'.$langs->trans("Product").'</b></td>';
					$more .= '<td class="left"><b>'.$langs->trans("batch_number").'</b></td>';
					$more .= '<td class="left"><b>'.$langs->trans("Qty").'</b></td>';

					if($action == 'createdelivery' || $action == 'receivedfromsupplier') {
						$more .= '<td class="left">'.$langs->trans("Warehouse").'</td>';
					}

					$more .= '</tr>';

					for ($i = 0; $i < $nblines; $i++) {
						if (empty($object->lines[$i]->fk_product)) {
							continue;
						}

						$objprod = new Product($db);
						$objprod->fetch($object->lines[$i]->fk_product);

						if($objprod->type != Product::TYPE_PRODUCT) continue;

						$hasbatch = $objprod->hasbatch();


						$tmid = $object->lines[$i]->fk_product;

						$warehouse 	= $s && isset($s[$tmid]) && isset($s[$tmid]['warehouse']) ? $s[$tmid]['warehouse'] : $dware;
						$batch 		= $s && isset($s[$tmid]) && isset($s[$tmid]['batch']) ? $s[$tmid]['batch'] : '';
						$qty 		= $s && isset($s[$tmid]) && isset($s[$tmid]['qty']) ? $s[$tmid]['qty'] : $object->lines[$i]->qty;
						
						// $qty 	= $hasbatch ? 1 : $qty;

						$more .= '<tr class="oddeven_">';
						
						// Ref Product
						$more .= '<td class="left width300">'.$objprod->getNomUrl(1).'</td>';

						// Batch
						$more .= '<td class="left width300">';
						if($hasbatch) {
							for ($z=0; $z < $qty; $z++) { 
								$batch = $s && isset($s[$tmid]) && isset($s[$tmid]['batch'][$z]) ? $s[$tmid]['batch'][$z] : '';
								$more .= '<input type="text" class="flat width200" name="savorders_data['.$tmid.'][batch]['.$z.']" value="'.$batch.'"/>';
							}
						} else {
							$more .= '-';
						}
						// $more .= '<input type="text" class="flat width200" name="savorders_data['.$tmid.'][batch]" value="'.$batch.'"/>';
						$more .= '</td>';

						// $disabled = ($hasbatch) ? 'disabled' : '';
						$disabled = '';

						$maxqty = ($hasbatch) ? 'max="'.$qty.'"' : '';

						// Qty
						$more .= '<td class="left ">';
						$more .= '<input type="number" class="flat width50" name="savorders_data['.$tmid.'][qty]" value="'.$qty.'" '.$maxqty.' min="1" step="any" '.$disabled.'/>';
						$more .= '</td>';

						// Warehouse
						if($action == 'createdelivery' || $action == 'receivedfromsupplier') {
							$more .= '<td class="left selectWarehouses">';
							$formproduct = new FormProduct($db);
	                		$more .= $formproduct->selectWarehouses($warehouse, 'savorders_data['.$tmid.'][warehouse]', '', 0, 0, 0, '', 0, $forcecombo);
							$more .= '</td>';
						}

						$more .= '</tr>';

					}

					$more .= '<tr><td colspan="4"></td></tr>';
					$more .= '<tr>';
						$more .= '<td colspan="4" class="center">';
						$more .= '<div class="savorders_dateaction">';
						$more .= '<b>'.$langs->trans('Date').'</b>: ';
		                $more .= $form->selectDate('', 'savorders_date', 0, 0, 0, '', 1, 1);
						$more .= '</div>';
						$more .= '</td>';
					$more .= '</tr>';

					$more .= '<tr class="valid">';
					// // Line with question
					// $more .= '<td class="valid">'.$question.'</td>';
					$more .= '<td class="valid center" colspan="4">';
					// $more .= $form->selectyesno("confirm", $newselectedchoice, 0, false, 0, 0, 'marginleftonly marginrightonly');
					$more .= '<input class="button valignmiddle confirmvalidatebutton" type="submit" value="'.$langs->trans("Validate").'">';
					$more .= '<input type="submit" class="button button-cancel" value="'.$langs->trans("Cancel").'" name="cancel" />';
		        	$more .= '</a>';
					$more .= '</td>';
					$more .= '</tr>'."\n";


				$more .= '</table>';

				$more .= "</form>\n";

				if (!empty($conf->use_javascript_ajax)) {
					$more .= '<!-- code to disable button to avoid double clic -->';
					$more .= '<script type="text/javascript">'."\n";
					$more .= '
					$(document).ready(function () {
						$(".confirmvalidatebutton").on("click", function() {
							console.log("We click on button");
							$(this).attr("disabled", "disabled");
							setTimeout(\'$(".confirmvalidatebutton").removeAttr("disabled")\', 3000);
							//console.log($(this).closest("form"));
							$(this).closest("form").submit();
						});
						$("td.selectWarehouses select").select2();
					});
					';
					$more .= '</script>'."\n";
				}

				$more .= '</div>';
				$more .= '<br>';

				print $more;

				return 1;
			}
		

	        if (!$user->rights->savorders->creer || $object->statut < 1) return 0;

	        $nblines = count($object->lines);

	        $savorders_sav = $object->array_options["options_savorders_sav"];
	        $savorders_status = $object->array_options["options_savorders_status"];

	        if($ngtmpdebug) {
		        echo 'nblines : '.$nblines.'<br>';
		        echo 'savorders_sav : '.$savorders_sav.'<br>';
		        echo 'savorders_status : '.$savorders_status.'<br>';
		        echo 'object->element : '.$object->element.'<br>';
	        }

	        if($savorders_sav && $nblines > 0) {

	            print '<div class="inline-block divButAction">';

	        	if($object->element == 'order_supplier') {
	        		if(empty($savorders_status)) {
		        		print '<a id="savorders_button" class="savorders butAction badge-status1" href="'.$linktogo.'&action=deliveredtosupplier">' . $langs->trans('ProductDeliveredToSupplier');
			        	print '</a>';
		        	} 
		        	elseif($savorders_status == savorders::DELIVERED_SUPPLIER) {
			        	print '<a id="savorders_button" class="savorders butAction badge-status1" href="'.$linktogo.'&action=receivedfromsupplier">' . $langs->trans('ProductReceivedFromSupplier');
			        	print '</a>';
		        	}
	        	} else {
		        	if(empty($savorders_status)) {
		        		print '<a id="savorders_button" class="savorders butAction badge-status1" href="'.$linktogo.'&action=receiptofproduct">' . $langs->trans('ProductReceivedFromCustomer');
			        	print '</a>';
		        	} 
		        	elseif($savorders_status == savorders::RECIEVED_CUSTOMER) {
			        	print '<a id="savorders_button" class="savorders butAction badge-status1" href="'.$linktogo.'&action=createdelivery">' . $langs->trans('ProductDeliveredToCustomer');
			        	print '</a>';
		        	}
	        	}

	            print '</div>';

	        }

        }

        return 0;
    }
}
