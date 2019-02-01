<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/compta/bank/transfer.php
 *		\ingroup    banque
 *		\brief      Page de saisie d'un virement
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/config.php';
dol_include_once('/stripeconnect/lib/stripeconnect.lib.php');
dol_include_once('/stripeconnect/class/stripeconnect.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$action=GETPOST('action','aZ09');
$langs->load("admin");
$langs->load("companies");
$langs->load("stripeconnect@stripeconnect");
$langs->load("banks");
$langs->load("categories");

if (empty($conf->stripeconnect->enabled)) accessforbidden('',0,0,1);
if (! $user->rights->banque->transfer)
  accessforbidden();

$action = GETPOST('action','alpha');
$error = 0;

$stripeconnect=new StripeConnexion($db);
/*
 * Actions
 */
if (($action == 'add_confirm') && (NULL==$stripeconnect->GetStripeAccount($entity))){
$acc=\Stripe\Account::create(array(
  "type" => "custom",
  "country" => "FR",
  "email" => $conf->global->MAIN_INFO_SOCIETE_MAIL,
  "legal_entity" => array(
  "type" => "company"
  ),
  "tos_acceptance" => array(
  "date" => time(),
  "ip" => $_SERVER['REMOTE_ADDR'],
  "user_agent" => $_SERVER['HTTP_USER_AGENT']
  )
));
      $sql  = "INSERT INTO ".MAIN_DB_PREFIX."entity_stripe (entity,key_account,mode,fk_type) VALUES ('".$entity."','".$acc->id."','";
 if ($conf->global->STRIPE_LIVE==1){
     $sql  .= "1";
}
else{
     $sql  .= "0";
}           
      $sql .="','custom') ";
      $sql .= "ON DUPLICATE key UPDATE entity='".$entity."', key_account='".$acc->id."', mode='";
       if ($conf->global->STRIPE_LIVE==1){
     $sql  .= "1";
}
else{
     $sql  .= "0";
}     
     $sql  .= "', fk_type='custom'";
      //dol_syslog(get_class($THIS) . "::create sql=" . $sql, LOG_DEBUG);
      $db->query($sql);
 	    $db->commit();
}

$error=0;

/*
 * Actions
 */

if ( ($action == 'update' && empty($_POST["cancel"]))
|| ($action == 'updateedit') )
{

	$tmparray=getCountry(GETPOST('country_id','int'),'all',$db,$langs,0);
	if (! empty($tmparray['id']))
	{
		$mysoc->country_id   =$tmparray['id'];
		$mysoc->country_code =$tmparray['code'];
		$mysoc->country_label=$tmparray['label'];

		$s=$mysoc->country_id.':'.$mysoc->country_code.':'.$mysoc->country_label;
		dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", $s,'chaine',0,'',$conf->entity);
	}

	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM",$_POST["nom"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADDRESS",$_POST["address"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TOWN",$_POST["town"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ZIP",$_POST["zipcode"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_STATE",$_POST["state_id"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MONNAIE",$_POST["currency"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TEL",$_POST["tel"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FAX",$_POST["fax"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_MAIL",$_POST["mail"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_WEB",$_POST["web"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOTE",$_POST["note"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_GENCOD",$_POST["barcode"],'chaine',0,'',$conf->entity);
	 
  
	$varforimage='logo'; $dirforimage=$conf->mycompany->dir_output.'/logos/';
	if ($_FILES[$varforimage]["tmp_name"])
	{
		if (preg_match('/([^\\/:]+)$/i',$_FILES[$varforimage]["name"],$reg))
		{
			$original_file=$reg[1];

			$isimage=image_format_supported($original_file);
			if ($isimage >= 0)
			{
				dol_syslog("Move file ".$_FILES[$varforimage]["tmp_name"]." to ".$dirforimage.$original_file);
				if (! is_dir($dirforimage))
				{
					dol_mkdir($dirforimage);
				}
				$result=dol_move_uploaded_file($_FILES[$varforimage]["tmp_name"],$dirforimage.$original_file,1,0,$_FILES[$varforimage]['error']);
				if ($result > 0)
				{
					dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO",$original_file,'chaine',0,'',$conf->entity);

					// Create thumbs of logo (Note that PDF use original file and not thumbs)
					if ($isimage > 0)
					{
					    // Create thumbs
					    //$object->addThumbs($newfile);    // We can't use addThumbs here yet because we need name of generated thumbs to add them into constants. TODO Check if need such constants. We should be able to retreive value with get... 
					    	
						// Create small thumb, Used on logon for example
						$imgThumbSmall = vignette($dirforimage.$original_file, $maxwidthsmall, $maxheightsmall, '_small', $quality);
						if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i',$imgThumbSmall,$reg))
						{
							$imgThumbSmall = $reg[1];    // Save only basename
							dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO_SMALL",$imgThumbSmall,'chaine',0,'',$conf->entity);
						}
						else dol_syslog($imgThumbSmall);

						// Create mini thumb, Used on menu or for setup page for example
						$imgThumbMini = vignette($dirforimage.$original_file, $maxwidthmini, $maxheightmini, '_mini', $quality);
						if (image_format_supported($imgThumbMini) >= 0 && preg_match('/([^\\/:]+)$/i',$imgThumbMini,$reg))
						{
							$imgThumbMini = $reg[1];     // Save only basename
							dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO_MINI",$imgThumbMini,'chaine',0,'',$conf->entity);
						}
						else dol_syslog($imgThumbMini);
					}
					else dol_syslog("ErrorImageFormatNotSupported",LOG_WARNING);
				}
				else if (preg_match('/^ErrorFileIsInfectedWithAVirus/',$result))
				{
					$error++;
					$langs->load("errors");
					$tmparray=explode(':',$result);
					setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus',$tmparray[1]), null, 'errors');
				}
				else
				{
					$error++;
					setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
				}
			}
			else
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
			}
		}
	}
	
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_MANAGERS",$_POST["MAIN_INFO_SOCIETE_MANAGERS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_CAPITAL",$_POST["capital"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FORME_JURIDIQUE",$_POST["forme_juridique_code"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SIREN",$_POST["siren"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SIRET",$_POST["siret"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_APE",$_POST["ape"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_RCS",$_POST["rcs"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_PROFID5",$_POST["MAIN_INFO_PROFID5"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_PROFID6",$_POST["MAIN_INFO_PROFID6"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_INFO_TVAINTRA",$_POST["tva"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_OBJECT",$_POST["object"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "SOCIETE_FISCAL_MONTH_START",$_POST["fiscalmonthstart"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "FACTURE_TVAOPTION",$_POST["optiontva"],'chaine',0,'',$conf->entity);

	// Local taxes
	dolibarr_set_const($db, "FACTURE_LOCAL_TAX1_OPTION",$_POST["optionlocaltax1"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "FACTURE_LOCAL_TAX2_OPTION",$_POST["optionlocaltax2"],'chaine',0,'',$conf->entity);

	if($_POST["optionlocaltax1"]=="localtax1on")
	{
		if(!isset($_REQUEST['lt1']))
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX1", 0,'chaine',0,'',$conf->entity);
		}
		else
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX1", GETPOST('lt1'),'chaine',0,'',$conf->entity);
		}
		dolibarr_set_const($db,"MAIN_INFO_LOCALTAX_CALC1", $_POST["clt1"],'chaine',0,'',$conf->entity);
	}
	if($_POST["optionlocaltax2"]=="localtax2on")
	{
		if(!isset($_REQUEST['lt2']))
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX2", 0,'chaine',0,'',$conf->entity);
		}
		else
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX2", GETPOST('lt2'),'chaine',0,'',$conf->entity);
		}
		dolibarr_set_const($db,"MAIN_INFO_LOCALTAX_CALC2", $_POST["clt2"],'chaine',0,'',$conf->entity);
	}
  $account=\Stripe\Account::retrieve("".$stripeconnect->GetStripeAccount($entity)."");
//$account->external_accounts->retrieve("ba_1AjlEXIxY7Eyc0Pk0DzE6M7R")->delete()
//$account2->external_accounts->create(array("external_account" => array(
//"object" => "bank_account",
//"account_number" => "DE89370400440532013000", 
//"country" => "DE", 
//"currency" => "EUR", 
//))); 
// Indicate that there are no additional owners
$account2=\Stripe\Account::retrieve("".$stripeconnect->GetStripeAccount($entity)."");
$account2->business_name = $conf->global->MAIN_INFO_SOCIETE_NOM;
//$account2->country = "FR"; //$mysoc->country_code
$account2->email = $conf->global->MAIN_INFO_SOCIETE_MAIL;
$account2->business_url = $conf->global->MAIN_INFO_SOCIETE_WEB;
$account2->statement_descriptor = $conf->global->MAIN_INFO_SOCIETE_NOM;
//$account2->timezone = "Europe/Paris";
$account2->legal_entity->business_name = $conf->global->MAIN_INFO_SOCIETE_NOM;
$account2->legal_entity->address->line1 = $conf->global->MAIN_INFO_SOCIETE_ADDRESS; 
$account2->legal_entity->address->postal_code = $conf->global->MAIN_INFO_SOCIETE_ZIP ; 
$account2->legal_entity->address->city = $conf->global->MAIN_INFO_SOCIETE_TOWN; 
$account2->legal_entity->address->state = getState($conf->global->MAIN_INFO_SOCIETE_STATE,1);
//$account2->legal_entity->address->country = null;
$account2->default_currency = $conf->currency;
if ($conf->global->FACTURE_TVAOPTION==1){ 
$account2->legal_entity->business_tax_id = $conf->global->MAIN_INFO_TVAINTRA;
}else{
$account2->legal_entity->business_tax_id = "000000000";
}
// representant legal
$account2->legal_entity->first_name = "titi";
$account2->legal_entity->last_name = "dumoutier";
$account2->legal_entity->personal_address->line1 = "rue gdb";
$account2->legal_entity->personal_address->postal_code = "59000";
$account2->legal_entity->personal_address->city = "lill";
$account2->legal_entity->personal_address->state = getState($conf->global->MAIN_INFO_SOCIETE_STATE,1);
$account2->legal_entity->dob->day = "01";
$account2->legal_entity->dob->month = "01";
$account2->legal_entity->dob->year = "1980";

$account2->legal_entity->additional_owners = null; 
                    
//$account2->external_accounts->create(array("external_account" => "tok_mastercard"));
$account2->save();
	if ($action != 'updateedit' && ! $error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

llxHeader();

$form=new Form($db);

$account_from='';
$account_to='';
$label='';
$amount='';
$amount_to='';

if ($error)
{
	$account_from =	GETPOST('account_from','int');
	$account_to	= GETPOST('account_to','int');
	$label = GETPOST('label','alpha');
	$amount = GETPOST('amount','int');
}

print load_fiche_titre($langs->trans("StripeAccount"), '', 'title_bank.png');


//$pay=\Stripe\BalanceTransaction::all(array("payout" => "po_1AfzSMFNEmQ3Lcv5wYWatLfE","limit" => 1000), array("stripe_account" => "acct_1AfDRyFNEmQ3Lcv5"));

//$need=\Stripe\CountrySpec::retrieve("FR");
$object = new Societe($db);
$object->fetch(1);
//$head = societe_prepare_head($object);
dol_fiche_head($head, 'stripeaccount', $langs->trans("Stripesignup"), -1, 'company');
print $langs->trans("StripeAccountDesc").'<BR><BR>'; 

if ($entity==1){
//print \Stripe\Event::retrieve("evt_1BQM2oHEbfUxVIBUPJmbeKW6",array("stripe_account" => acct_1B8Qh9HEbfUxVIBU));
//print \Stripe\BalanceTransaction::all(array("limit" => 20));
//print \Stripe\BalanceTransaction::all(array("payout" => po_1BQmuSK034Aqz8l5EPZjXJME,"limit" => 100));
}

if ($stripeconnect->GetStripeAccount($entity) )
{
if ($entity==1){
$balance=\Stripe\Balance::retrieve(); 
$account=\Stripe\Account::retrieve("".$stripeconnect->GetStripeAccount($entity)."");
}
else {
$balance=\Stripe\Balance::retrieve(array("stripe_account" => $stripeconnect->GetStripeAccount($entity)));
$account=\Stripe\Account::retrieve("".$stripeconnect->GetStripeAccount($entity)."");  
}
}

if ($stripeconnect->GetStripeAccount($entity)){
print '<TABLE class="noborder" width="100%">';
  $sql = "SELECT key_account,next_payout,fk_type";
	$sql.= " FROM ".MAIN_DB_PREFIX."stripeconnect_entity";
	$sql.= " WHERE entity = ".$entity.""; 

	dol_syslog(get_class($db) . "::fetch", LOG_DEBUG);
	$result = $db->query($sql);
  if ($result)
	{
			if ($db->num_rows($result))
			{
		$obj = $db->fetch_object($result);
    $key=$obj->next_payout;
    }
    else {$key=0;}
  }
print '<TR class="oddeven"><TD>'.$langs->trans("NextVir").'</TD><TD>'.$key.'</TD></TR>';   
print '</TD></TR>';
print '<TR class="oddeven"><TD>'.$langs->trans("NextVirSettings").'</TD><TD>';
if ($account->type=='custom'){
print $langs->trans("".$account->payout_schedule->interval."");
if ($account->payout_schedule->interval=='monthly'){print ', le '.$account->payout_schedule->monthly_anchor.' du mois';}
elseif ($account->payout_schedule->interval=='weekly') {print ', le '.$langs->trans("".$account->payout_schedule->weekly_anchor."").'';}
} elseif ($account->type=='standard'){
print 'selon paramètres de votre compte Stripe';
}
print '</TD></TR>';   
print '</TD></TR>';
print '<TR class="oddeven"><TD>'.$langs->trans("ActualSold").'</TD><TD>';
foreach ($balance->available as $value) {
print price(($value->amount)/100,2).' '.$value->currency;}
print '<TR class="oddeven"><TD>'.$langs->trans("NextSold").' ('.$account->payout_schedule->delay_days.' '.$langs->trans("days").')</TD><TD>';
foreach ($balance->pending as $value) {
print price(($value->amount)/100,2).' '.$value->currency;}
print '</TD></TR>';
print '</TABLE>';
  }

if ($stripeconnect->GetStripeAccount($entity) && $account->type=='standard') 
{
print "vos informations de compte ne peuvent être mis à jour que via votre interface Stripe";
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

print "$need<BR><BR>";

print '<TABLE class="noborder" width="100%">';
print '<TR class="liste_titre">';
print '<TD>'.$langs->trans("TransferFrom").'</TD><TD>'.$langs->trans("TransferTo").'</TD><TD>'.$langs->trans("Date").'</TD>';
print '<TD>'.$langs->trans("Description").'</TD><TD>'.$langs->trans("Amount").'</TD>';
print '</TR>';

$var=false;
print '<TR class="oddeven"><TD>';

print "<TD>\n";
$form->select_comptes($account_to,'account_to',0,'',1);
print "</TD>\n";

print "<TD>";
$form->select_date((! empty($dateo)?$dateo:''),'','','','','add');
print "</TD>\n";
print '<TD><INPUT name="label" class="flat quatrevingtpercent" type="text" value="'.$label.'"></TD>';
print '<TD><INPUT name="amount" class="flat" type="text" size="6" value="'.$amount.'"></TD>';
print "</TABLE>";

}
elseif ($stripeconnect->GetStripeAccount($entity) && $entity!=1) 
{
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

//dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');
//print \Stripe\BalanceTransaction::all(array("limit" => 3));
//print "<BR><BR>payout ".$payout =\Stripe\Payout::retrieve("po_1B1GeJDb8KgiUCPjTBRwcL6d",array("stripe_account" => $stripeconnect->GetStripeAccount($entity)));
 

$form=new Form($db);
$formother=new FormOther($db);
$formcompany=new FormCompany($db);

$countrynotdefined='<FONT class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</FONT>';
if ($action == 'edit' || $action == 'updateedit')
{
	/**
	 * Edition des parametres
	 */
	print "\n".'<SCRIPT type="text/javascript" language="javascript">';
	print '$(document).ready(function () {
			  $("#selectcountry_id").change(function() {
				document.form_index.action.value="updateedit";
				document.form_index.submit();
			  });
		  });';
	print '</SCRIPT>'."\n";

	print '<FORM enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
	print '<INPUT type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<INPUT type="hidden" name="action" value="update">';
	$var=true;

	print '<TABLE class="noborder" width="100%">';
	print '<TR class="liste_titre"><TH class="titlefield">'.$langs->trans("CompanyInfo").'</TH><TH>'.$langs->trans("Value").'</TH></TR>'."\n";

	// Name
	
	print '<TR class="oddeven"><TD class="fieldrequired"><LABEL for="name">'.$langs->trans("CompanyName").'</LABEL></TD><TD>';
	print '<INPUT name="nom" id="name" class="minwidth200" value="'. ($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM:$_POST["nom"]) . '" autofocus="autofocus" required></TD></TR>'."\n";

	// Addresse
	
	print '<TR class="oddeven"><TD><LABEL for="address">'.$langs->trans("CompanyAddress").'</LABEL></TD><TD>';
	print '<TEXTAREA name="address" id="address" class="quatrevingtpercent" rows="'.ROWS_3.'" required>'. ($conf->global->MAIN_INFO_SOCIETE_ADDRESS?$conf->global->MAIN_INFO_SOCIETE_ADDRESS:$_POST["address"]) . '</TEXTAREA></TD></TR>'."\n";

	
	print '<TR class="oddeven"><TD><LABEL for="zipcode">'.$langs->trans("CompanyZip").'</LABEL></TD><TD>';
	print '<INPUT class="minwidth100" name="zipcode" id="zipcode" value="'. ($conf->global->MAIN_INFO_SOCIETE_ZIP?$conf->global->MAIN_INFO_SOCIETE_ZIP:$_POST["zipcode"]) . '" required></TD></TR>'."\n";

	
	print '<TR class="oddeven"><TD><LABEL for="town">'.$langs->trans("CompanyTown").'</LABEL></TD><TD>';
	print '<INPUT name="town" class="minwidth100" id="town" value="'. ($conf->global->MAIN_INFO_SOCIETE_TOWN?$conf->global->MAIN_INFO_SOCIETE_TOWN:$_POST["town"]) . '" required></TD></TR>'."\n";

	// Country
	
	print '<TR class="oddeven"><TD class="fieldrequired"><LABEL for="selectcountry_id">'.$langs->trans("Country").'</LABEL></TD><TD class="maxwidthonsmartphone">';
	//if (empty($country_selected)) $country_selected=substr($langs->defaultlang,-2);    // By default, country of localization
	print $form->select_country($mysoc->country_id,'country_id');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	print '</TD></TR>'."\n";

	
	print '<TR class="oddeven"><TD><LABEL for="state_id">'.$langs->trans("State").'</LABEL></TD><TD class="maxwidthonsmartphone">';
	$formcompany->select_departement($conf->global->MAIN_INFO_SOCIETE_STATE,$mysoc->country_code,'state_id');
	print '</TD></TR>'."\n";

	
	print '<TR class="oddeven"><TD><LABEL for="currency">'.$langs->trans("CompanyCurrency").'</LABEL></TD><TD>';
	print $form->selectCurrency($conf->currency,"currency");
	print '</TD></TR>'."\n";

	
	print '<TR class="oddeven"><TD><LABEL for="phone">'.$langs->trans("Phone").'</LABEL></TD><TD>';
	print '<INPUT name="tel" id="phone" value="'. $conf->global->MAIN_INFO_SOCIETE_TEL . '" required></TD></TR>';
	print '</TD></TR>'."\n";

	
	print '<TR class="oddeven"><TD><LABEL for="fax">'.$langs->trans("Fax").'</LABEL></TD><TD>';
	print '<INPUT name="fax" id="fax" value="'. $conf->global->MAIN_INFO_SOCIETE_FAX . '"></TD></TR>';
	print '</TD></TR>'."\n";

	
	print '<TR class="oddeven"><TD><LABEL for="email">'.$langs->trans("EMail").'</LABEL></TD><TD>';
	print '<INPUT name="mail" id="email" class="minwidth200" value="'. $conf->global->MAIN_INFO_SOCIETE_MAIL . '" required></TD></TR>';
	print '</TD></TR>'."\n";

	// Web
	
	print '<TR class="oddeven"><TD><LABEL for="web">'.$langs->trans("Web").'</LABEL></TD><TD>';
	print '<INPUT name="web" id="web" class="minwidth300" value="'. $conf->global->MAIN_INFO_SOCIETE_WEB . '"></TD></TR>';
	print '</TD></TR>'."\n";

	// Barcode
	if (! empty($conf->barcode->enabled)) {
		
		print '<TR class="oddeven"><TD><LABEL for="barcode">'.$langs->trans("Gencod").'</LABEL></TD><TD>';
		print '<INPUT name="barcode" id="barcode" class="minwidth150" value="'. $conf->global->MAIN_INFO_SOCIETE_GENCOD . '"></TD></TR>';
		print '</TD></TR>';
	}

	// Logo
	
	print '<TR'.dol_bc($var,'hideonsmartphone').'><TD><LABEL for="logo">'.$langs->trans("Logo").' (png,jpg)</LABEL></TD><TD>';
	print '<TABLE width="100%" class="nobordernopadding"><TR class="nocellnopadd"><TD valign="middle" class="nocellnopadd">';
	print '<INPUT type="file" class="flat class=minwidth200" name="logo" id="logo">';
	print '</TD><TD class="nocellnopadd" valign="middle" align="right">';
	if (! empty($mysoc->logo_mini)) {
		print '<A href="'.$_SERVER["PHP_SELF"].'?action=removelogo">'.img_delete($langs->trans("Delete")).'</A>';
		if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini)) {
			print ' &nbsp; ';
			print '<IMG src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('/thumbs/'.$mysoc->logo_mini).'">';
		}
	} else {
		print '<IMG height="30" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
	}
	print '</TD></TR></TABLE>';
	print '</TD></TR>';

	// Note
	
	print '<TR class="oddeven"><TD class="tdtop"><LABEL for="note">'.$langs->trans("Note").'</LABEL></TD><TD>';
	print '<TEXTAREA class="flat quatrevingtpercent" name="note" id="note" rows="'.ROWS_5.'">'.(! empty($conf->global->MAIN_INFO_SOCIETE_NOTE) ? $conf->global->MAIN_INFO_SOCIETE_NOTE : '').'</TEXTAREA></TD></TR>';
	print '</TD></TR>';

	print '</TABLE>';

	print '<BR>';

	// IDs of the company (country-specific)
	print '<TABLE class="noborder" width="100%">';
	print '<TR class="liste_titre"><TD>'.$langs->trans("CompanyIds").'</TD><TD>'.$langs->trans("Value").'</TD></TR>';
	$var=true;

	$langs->load("companies");

	// Managing Director(s)
	
	print '<TR class="oddeven"><TD><LABEL for="director">'.$langs->trans("ManagingDirectors").'</LABEL></TD><TD>';
	print '<INPUT name="MAIN_INFO_SOCIETE_MANAGERS" id="director" class="minwidth200" value="' . $conf->global->MAIN_INFO_SOCIETE_MANAGERS . '"></TD></TR>';

	// Capital
	
	print '<TR class="oddeven"><TD><LABEL for="capital">'.$langs->trans("Capital").'</LABEL></TD><TD>';
	print '<INPUT name="capital" id="capital" class="minwidth100" value="' . $conf->global->MAIN_INFO_CAPITAL . '"></TD></TR>';

	// Juridical Status
	
	print '<TR class="oddeven"><TD><LABEL for="forme_juridique_code">'.$langs->trans("JuridicalStatus").'</LABEL></TD><TD>';
	if ($mysoc->country_code) {
		print $formcompany->select_juridicalstatus($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE, $mysoc->country_code, '', 'forme_juridique_code');
	} else {
		print $countrynotdefined;
	}
	print '</TD></TR>';

	// ProfID1
	if ($langs->transcountry("ProfId1",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD><LABEL for="profid1">'.$langs->transcountry("ProfId1",$mysoc->country_code).'</LABEL></TD><TD>';
		if (! empty($mysoc->country_code))
		{
			print '<INPUT name="siren" id="profid1" class="minwidth200" value="' . (! empty($conf->global->MAIN_INFO_SIREN) ? $conf->global->MAIN_INFO_SIREN : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</TD></TR>';
	}

	// ProfId2
	if ($langs->transcountry("ProfId2",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD><LABEL for="profid2">'.$langs->transcountry("ProfId2",$mysoc->country_code).'</LABEL></TD><TD>';
		if (! empty($mysoc->country_code))
		{
			print '<INPUT name="siret" id="profid2" class="minwidth200" value="' . (! empty($conf->global->MAIN_INFO_SIRET) ? $conf->global->MAIN_INFO_SIRET : '' ) . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</TD></TR>';
	}

	// ProfId3
	if ($langs->transcountry("ProfId3",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD><LABEL for="profid3">'.$langs->transcountry("ProfId3",$mysoc->country_code).'</LABEL></TD><TD>';
		if (! empty($mysoc->country_code))
		{
			print '<INPUT name="ape" id="profid3" class="minwidth200" value="' . (! empty($conf->global->MAIN_INFO_APE) ? $conf->global->MAIN_INFO_APE : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</TD></TR>';
	}

	// ProfId4
	if ($langs->transcountry("ProfId4",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD><LABEL for="profid4">'.$langs->transcountry("ProfId4",$mysoc->country_code).'</LABEL></TD><TD>';
		if (! empty($mysoc->country_code))
		{
			print '<INPUT name="rcs" id="profid4" class="minwidth200" value="' . (! empty($conf->global->MAIN_INFO_RCS) ? $conf->global->MAIN_INFO_RCS : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</TD></TR>';
	}

	// ProfId5
	if ($langs->transcountry("ProfId5",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD><LABEL for="profid5">'.$langs->transcountry("ProfId5",$mysoc->country_code).'</LABEL></TD><TD>';
		if (! empty($mysoc->country_code))
		{
			print '<INPUT name="MAIN_INFO_PROFID5" id="profid5" class="minwidth200" value="' . (! empty($conf->global->MAIN_INFO_PROFID5) ? $conf->global->MAIN_INFO_PROFID5 : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</TD></TR>';
	}

	// ProfId6
	if ($langs->transcountry("ProfId6",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD><LABEL for="profid6">'.$langs->transcountry("ProfId6",$mysoc->country_code).'</LABEL></TD><TD>';
		if (! empty($mysoc->country_code))
		{
			print '<INPUT name="MAIN_INFO_PROFID6" id="profid6" class="minwidth200" value="' . (! empty($conf->global->MAIN_INFO_PROFID6) ? $conf->global->MAIN_INFO_PROFID6 : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</TD></TR>';
	}

	// TVA Intra
	
	print '<TR class="oddeven"><TD><LABEL for="intra_vat">'.$langs->trans("VATIntra").'</LABEL></TD><TD>';
	print '<INPUT name="tva" id="intra_vat" class="minwidth200" value="' . (! empty($conf->global->MAIN_INFO_TVAINTRA) ? $conf->global->MAIN_INFO_TVAINTRA : '') . '">';
	print '</TD></TR>';
	
	// Object of the company
	
	print '<TR class="oddeven"><TD><LABEL for="object">'.$langs->trans("CompanyObject").'</LABEL></TD><TD>';
	print '<TEXTAREA class="flat quatrevingtpercent" name="object" id="object" rows="'.ROWS_5.'">'.(! empty($conf->global->MAIN_INFO_SOCIETE_OBJECT) ? $conf->global->MAIN_INFO_SOCIETE_OBJECT : '').'</TEXTAREA></TD></TR>';
	print '</TD></TR>';

	print '</TABLE>';


	// Fiscal year start
	print '<BR>';
	print '<TABLE class="noborder" width="100%">';
	print '<TR class="liste_titre">';
	print '<TD class="titlefield">'.$langs->trans("FiscalYearInformation").'</TD><TD>'.$langs->trans("Value").'</TD>';
	print "</TR>\n";

	
	print '<TR class="oddeven"><TD><LABEL for="fiscalmonthstart">'.$langs->trans("FiscalMonthStart").'</LABEL></TD><TD>';
	print $formother->select_month($conf->global->SOCIETE_FISCAL_MONTH_START,'fiscalmonthstart',0,1) . '</TD></TR>';

	print "</TABLE>";


	// Fiscal options
	print '<BR>';
	print '<TABLE class="noborder" width="100%">';
	print '<TR class="liste_titre">';
	print '<TD class="titlefield">'.$langs->trans("VATManagement").'</TD><TD>'.$langs->trans("Description").'</TD>';
	print '<TD align="right">&nbsp;</TD>';
	print "</TR>\n";

	
	print "<TR class=\"oddeven\"><TD width=\"140\"><LABEL><INPUT type=\"radio\" name=\"optiontva\" id=\"use_vat\" value=\"1\"".(empty($conf->global->FACTURE_TVAOPTION)?"":" checked")."> ".$langs->trans("VATIsUsed")."</LABEL></TD>";
	print '<TD colspan="2">';
	print "<TABLE>";
	print "<TR><TD><LABEL for=\"use_vat\">".$langs->trans("VATIsUsedDesc")."</LABEL></TD></TR>";
	print "<TR><TD><I>".$langs->trans("Example").': '.$langs->trans("VATIsUsedExampleFR")."</I></TD></TR>\n";
	print "</TABLE>";
	print "</TD></TR>\n";

	
	print "<TR class=\"oddeven\"><TD width=\"140\"><LABEL><INPUT type=\"radio\" name=\"optiontva\" id=\"no_vat\" value=\"0\"".(empty($conf->global->FACTURE_TVAOPTION)?" checked":"")."> ".$langs->trans("VATIsNotUsed")."</LABEL></TD>";
	print '<TD colspan="2">';
	print "<TABLE>";
	print "<TR><TD><LABEL for=\"no_vat\">".$langs->trans("VATIsNotUsedDesc")."</LABEL></TD></TR>";
	print "<TR><TD><I>".$langs->trans("Example").': '.$langs->trans("VATIsNotUsedExampleFR")."</I></TD></TR>\n";
	print "</TABLE>";
	print "</TD></TR>\n";

	print "</TABLE>";

	/*
	 *  Local Taxes
	 */
	if ($mysoc->useLocalTax(1))
	{
		// Local Tax 1
		print '<BR>';
		print '<TABLE class="noborder" width="100%">';
		print '<TR class="liste_titre">';
		print '<TD>'.$langs->transcountry("LocalTax1Management",$mysoc->country_code).'</TD><TD>'.$langs->trans("Description").'</TD>';
		print '<TD align="right">&nbsp;</TD>';
		print "</TR>\n";
		
		// Note: When option is not set, it must not appears as set on on, because there is no default value for this option
		print "<TR class=\"oddeven\"><TD width=\"140\"><INPUT type=\"radio\" name=\"optionlocaltax1\" id=\"lt1\" value=\"localtax1on\"".(($conf->global->FACTURE_LOCAL_TAX1_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1on")?" checked":"")."> ".$langs->transcountry("LocalTax1IsUsed",$mysoc->country_code)."</TD>";
		print '<TD colspan="2">';
		print '<TABLE class="nobordernopadding">';
		print "<TR><TD><LABEL for=\"lt1\">".$langs->transcountry("LocalTax1IsUsedDesc",$mysoc->country_code)."</LABEL></TD></TR>";
		$example=$langs->transcountry("LocalTax1IsUsedExample",$mysoc->country_code);
		print ($example!="LocalTax1IsUsedExample"?"<TR><TD><I>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsUsedExample",$mysoc->country_code)."</I></TD></TR>\n":"");
		if(! isOnlyOneLocalTax(1))
		{
			print '<TR><TD align="left"><LABEL for="lt1">'.$langs->trans("LTRate").'</LABEL>: ';
			$formcompany->select_localtax(1,$conf->global->MAIN_INFO_VALUE_LOCALTAX1, "lt1");
		    print '</TD></TR>';
		}

		$opcions=array($langs->trans("CalcLocaltax1").' '.$langs->trans("CalcLocaltax1Desc"),$langs->trans("CalcLocaltax2").' - '.$langs->trans("CalcLocaltax2Desc"),$langs->trans("CalcLocaltax3").' - '.$langs->trans("CalcLocaltax3Desc"));

		print '<TR><TD align="left"></LABEL for="clt1">'.$langs->trans("CalcLocaltax").'</LABEL>: ';
		print $form->selectarray("clt1", $opcions, $conf->global->MAIN_INFO_LOCALTAX_CALC1);
		print '</TD></TR>';
		print "</TABLE>";
		print "</TD></TR>\n";

		
		print "<TR class=\"oddeven\"><TD width=\"140\"><INPUT type=\"radio\" name=\"optionlocaltax1\" id=\"nolt1\" value=\"localtax1off\"".((empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1off")?" checked":"")."> ".$langs->transcountry("LocalTax1IsNotUsed",$mysoc->country_code)."</TD>";
		print '<TD colspan="2">';
		print "<TABLE>";
		print "<TR><TD><LABEL for=\"nolt1\">".$langs->transcountry("LocalTax1IsNotUsedDesc",$mysoc->country_code)."</LABEL></TD></TR>";
		$example=$langs->transcountry("LocalTax1IsNotUsedExample",$mysoc->country_code);
		print ($example!="LocalTax1IsNotUsedExample"?"<TR><TD><I>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsNotUsedExample",$mysoc->country_code)."</I></TD></TR>\n":"");
		print "</TABLE>";
		print "</TD></TR>\n";
		print "</TABLE>";
	}
	if ($mysoc->useLocalTax(2))
	{
		// Local Tax 2
		print '<BR>';
		print '<TABLE class="noborder" width="100%">';
		print '<TR class="liste_titre">';
		print '<TD>'.$langs->transcountry("LocalTax2Management",$mysoc->country_code).'</TD><TD>'.$langs->trans("Description").'</TD>';
		print '<TD align="right">&nbsp;</TD>';
		print "</TR>\n";

		
		// Note: When option is not set, it must not appears as set on on, because there is no default value for this option
		print "<TR class=\"oddeven\"><TD width=\"140\"><INPUT type=\"radio\" name=\"optionlocaltax2\" id=\"lt2\" value=\"localtax2on\"".(($conf->global->FACTURE_LOCAL_TAX2_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2on")?" checked":"")."> ".$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code)."</TD>";
		print '<TD colspan="2">';
		print '<TABLE class="nobordernopadding">';
		print "<TR><TD><LABEL for=\"lt2\">".$langs->transcountry("LocalTax2IsUsedDesc",$mysoc->country_code)."</LABEL></TD></TR>";
		$example=$langs->transcountry("LocalTax2IsUsedExample",$mysoc->country_code);
		print ($example!="LocalTax2IsUsedExample"?"<TR><TD><I>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsUsedExample",$mysoc->country_code)."</I></TD></TR>\n":"");
		if(! isOnlyOneLocalTax(2))
		{
		    print '<TR><TD align="left"><LABEL for="lt2">'.$langs->trans("LTRate").'</LABEL>: ';
		    $formcompany->select_localtax(2,$conf->global->MAIN_INFO_VALUE_LOCALTAX2, "lt2");
			print '</TD></TR>';
		}
		print '<TR><TD align="left"><LABEL for="clt2">'.$langs->trans("CalcLocaltax").'</LABEL>: ';
		print $form->selectarray("clt2", $opcions, $conf->global->MAIN_INFO_LOCALTAX_CALC2);
		print '</TD></TR>';
		print "</TABLE>";
		print "</TD></TR>\n";

		
		print "<TR class=\"oddeven\"><TD width=\"140\"><INPUT type=\"radio\" name=\"optionlocaltax2\" id=\"nolt2\" value=\"localtax2off\"".((empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2off")?" checked":"")."> ".$langs->transcountry("LocalTax2IsNotUsed",$mysoc->country_code)."</TD>";
		print '<TD colspan="2">';
		print "<TABLE>";
		print "<TR><TD><LABEL for=\"nolt2\">".$langs->transcountry("LocalTax2IsNotUsedDesc",$mysoc->country_code)."</LABEL></TD></TR>";
		$example=$langs->transcountry("LocalTax2IsNotUsedExample",$mysoc->country_code);
		print ($example!="LocalTax2IsNotUsedExample"?"<TR><TD><I>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsNotUsedExample",$mysoc->country_code)."</I></TD></TR>\n":"");
		print "</TABLE>";
		print "</TD></TR>\n";
		print "</TABLE>";
	}


	print '<BR><DIV class="center">';
	print '<INPUT type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<INPUT type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</DIV>';
	print '<BR>';

	print '</FORM>';
}
else
{
	/*
	 * Show parameters
	 */


	print '<TABLE class="noborder" width="100%">';
  
	print '<TR class="liste_titre"><TD>'.$langs->trans("CompanyInfo").'</TD><TD>'.$langs->trans("Value").'</TD></TR>';

	
	print '<TR class="oddeven"><TD class="titlefield">'.$langs->trans("CompanyName").'</TD><TD>';
	if ((! empty($conf->global->MAIN_INFO_SOCIETE_NOM)) && (! in_array("business_name", $account->verification->fields_needed)) && $conf->global->MAIN_INFO_SOCIETE_NOM==$account->business_name){print $conf->global->MAIN_INFO_SOCIETE_NOM;} 
	else {print img_warning().' <FONT class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyName")).'</FONT>';}
	print '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("CompanyAddress").'</TD><TD>' . nl2br(empty($conf->global->MAIN_INFO_SOCIETE_ADDRESS)?'':$conf->global->MAIN_INFO_SOCIETE_ADDRESS) . '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("CompanyZip").'</TD><TD>' . (empty($conf->global->MAIN_INFO_SOCIETE_ZIP)?'':$conf->global->MAIN_INFO_SOCIETE_ZIP) . '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("CompanyTown").'</TD><TD>' . (empty($conf->global->MAIN_INFO_SOCIETE_TOWN)?'':$conf->global->MAIN_INFO_SOCIETE_TOWN) . '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("CompanyCountry").'</TD><TD>';
	if ($mysoc->country_code)
	{
		$img=picto_from_langcode($mysoc->country_code);
		print $img?$img.' ':'';
		print getCountry($mysoc->country_code,1);
	}
	else print img_warning().' <FONT class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</FONT>';
	print '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("State").'</TD><TD>';
	if (! empty($conf->global->MAIN_INFO_SOCIETE_STATE)) print getState($conf->global->MAIN_INFO_SOCIETE_STATE);
	else print '&nbsp;';
	print '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("CompanyCurrency").'</TD><TD>';
	print currency_name($conf->currency,1);
	print ' ('.$conf->currency;
	print ($conf->currency != $langs->getCurrencySymbol($conf->currency) ? ' - '.$langs->getCurrencySymbol($conf->currency) : '');
	print ')';
	print '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("Phone").'</TD><TD>' . dol_print_phone($conf->global->MAIN_INFO_SOCIETE_TEL,$mysoc->country_code) . '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("Fax").'</TD><TD>' . dol_print_phone($conf->global->MAIN_INFO_SOCIETE_FAX,$mysoc->country_code) . '</TD></TR>';

	
	print '<TR class="oddeven"><TD>'.$langs->trans("Mail").'</TD><TD>' . dol_print_email($conf->global->MAIN_INFO_SOCIETE_MAIL,0,0,0,80) . '</TD></TR>';

	// Web
	
	print '<TR class="oddeven"><TD>'.$langs->trans("Web").'</TD><TD>' . dol_print_url($conf->global->MAIN_INFO_SOCIETE_WEB,'_blank',80) . '</TD></TR>';

	// Barcode
	if (! empty($conf->barcode->enabled))
	{
		
		print '<TR class="oddeven"><TD>'.$langs->trans("Gencod").'</TD><TD>' . $conf->global->MAIN_INFO_SOCIETE_GENCOD . '</TD></TR>';
	}

	// Logo
	
	print '<TR class="oddeven"><TD>'.$langs->trans("Logo").'</TD><TD>';

	$tagtd='tagtd ';
	if ($conf->browser->layout == 'phone') $tagtd='';
	print '<DIV class="tagtable centpercent"><DIV class="tagtr inline-block centpercent valignmiddle"><DIV class="'.$tagtd.'inline-block valignmiddle left">';
	print $mysoc->logo;
	print '</DIV><DIV class="'.$tagtd.'inline-block valignmiddle left">';

	// It offers the generation of the thumbnail if it does not exist
	if (!is_file($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini) && preg_match('/(\.jpg|\.jpeg|\.png)$/i',$mysoc->logo))
	{
		print '<A class="img_logo" href="'.$_SERVER["PHP_SELF"].'?action=addthumb&amp;file='.urlencode($mysoc->logo).'">'.img_picto($langs->trans('GenerateThumb'),'refresh').'</A>&nbsp;&nbsp;';
	}
	else if ($mysoc->logo_mini && is_file($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini))
	{ 
		print '<IMG class="img_logo" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('/thumbs/'.$mysoc->logo_mini).'">';
	}
	else
	{
		print '<IMG class="img_logo" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
	}
	print '</DIV></DIV></DIV>';

	print '</TD></TR>';

	
	print '<TR class="oddeven"><TD class="tdtop">'.$langs->trans("Note").'</TD><TD>' . (! empty($conf->global->MAIN_INFO_SOCIETE_NOTE) ? nl2br($conf->global->MAIN_INFO_SOCIETE_NOTE) : '') . '</TD></TR>';

	print '</TABLE>';


	print '<BR>';


	// IDs of the company (country-specific)
	print '<FORM name="formsoc" method="post">';
	print '<INPUT type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<TABLE class="noborder" width="100%">';
	print '<TR class="liste_titre"><TD class="titlefield">'.$langs->trans("CompanyIds").'</TD><TD>'.$langs->trans("Value").'</TD></TR>';

	// Managing Director(s)
	
	print '<TR class="oddeven"><TD>'.$langs->trans("ManagingDirectors").'</TD><TD>';
	print $conf->global->MAIN_INFO_SOCIETE_MANAGERS . '</TD></TR>';

	// Capital
	
	print '<TR class="oddeven"><TD>'.$langs->trans("Capital").'</TD><TD>';
	print $conf->global->MAIN_INFO_CAPITAL . '</TD></TR>';

	// Juridical Status
	
	print '<TR class="oddeven"><TD>'.$langs->trans("JuridicalStatus").'</TD><TD>';
	print getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
	print '</TD></TR>';

	// ProfId1
	if ($langs->transcountry("ProfId1",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD>'.$langs->transcountry("ProfId1",$mysoc->country_code).'</TD><TD>';
		if (! empty($conf->global->MAIN_INFO_SIREN))
		{
			print $conf->global->MAIN_INFO_SIREN;
			$s = $mysoc->id_prof_url(1,$mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</TD></TR>';
	}

	// ProfId2
	if ($langs->transcountry("ProfId2",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD>'.$langs->transcountry("ProfId2",$mysoc->country_code).'</TD><TD>';
		if (! empty($conf->global->MAIN_INFO_SIRET))
		{
			print $conf->global->MAIN_INFO_SIRET;
			$s = $mysoc->id_prof_url(2,$mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</TD></TR>';
	}

	// ProfId3
	if ($langs->transcountry("ProfId3",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD>'.$langs->transcountry("ProfId3",$mysoc->country_code).'</TD><TD>';
		if (! empty($conf->global->MAIN_INFO_APE))
		{
			print $conf->global->MAIN_INFO_APE;
			$s = $mysoc->id_prof_url(3,$mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</TD></TR>';
	}

	// ProfId4
	if ($langs->transcountry("ProfId4",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD>'.$langs->transcountry("ProfId4",$mysoc->country_code).'</TD><TD>';
		if (! empty($conf->global->MAIN_INFO_RCS))
		{
			print $conf->global->MAIN_INFO_RCS;
			$s = $mysoc->id_prof_url(4,$mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</TD></TR>';
	}

	// ProfId5
	if ($langs->transcountry("ProfId5",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD>'.$langs->transcountry("ProfId5",$mysoc->country_code).'</TD><TD>';
		if (! empty($conf->global->MAIN_INFO_PROFID5))
		{
			print $conf->global->MAIN_INFO_PROFID5;
			$s = $mysoc->id_prof_url(5,$mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</TD></TR>';
	}

	// ProfId6
	if ($langs->transcountry("ProfId6",$mysoc->country_code) != '-')
	{
		
		print '<TR class="oddeven"><TD>'.$langs->transcountry("ProfId6",$mysoc->country_code).'</TD><TD>';
		if (! empty($conf->global->MAIN_INFO_PROFID6))
		{
			print $conf->global->MAIN_INFO_PROFID6;
			$s = $mysoc->id_prof_url(6,$mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</TD></TR>';
	}

	// VAT
	
	print '<TR class="oddeven"><TD>'.$langs->trans("VATIntra").'</TD>';
	print '<TD>';
	if (! empty($conf->global->MAIN_INFO_TVAINTRA))
	{
		$s='';
		$s.=$conf->global->MAIN_INFO_TVAINTRA;
		$s.='<INPUT type="hidden" name="tva_intra" size="12" maxlength="20" value="'.$conf->global->MAIN_INFO_TVAINTRA.'">';
		if (empty($conf->global->MAIN_DISABLEVATCHECK) && $mysoc->isInEEC())
		{
			$s.=' - ';
			if (! empty($conf->use_javascript_ajax))
			{
				print "\n";
				print '<SCRIPT language="JavaScript" type="text/javascript">';
				print "function CheckVAT(a) {\n";
				print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,285);\n";
				print "}\n";
				print '</SCRIPT>';
				print "\n";
				$s.='<A href="#" onClick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</A>';
				$s = $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
			}
			else
			{
				$s.='<A href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_country).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</A>';
			}
		}
		print $s;
	}
	else
	{
		print '&nbsp;';
	}
	print '</TD>';
	print '</TR>';
	
	
	print '<TR class="oddeven"><TD class="tdtop">'.$langs->trans("CompanyObject").'</TD><TD>' . (! empty($conf->global->MAIN_INFO_SOCIETE_OBJECT) ? nl2br($conf->global->MAIN_INFO_SOCIETE_OBJECT) : '') . '</TD></TR>';

	print '</TABLE>';
	print '</FORM>';

	/*
	 *  fiscal year beginning
	 */
	print '<BR>';
	print '<TABLE class="noborder" width="100%">';
	print '<TR class="liste_titre">';
	print '<TD class="titlefield">'.$langs->trans("FiscalYearInformation").'</TD><TD>'.$langs->trans("Value").'</TD>';
	print "</TR>\n";

	
	print '<TR class="oddeven"><TD>'.$langs->trans("FiscalMonthStart").'</TD><TD>';
	$monthstart=(! empty($conf->global->SOCIETE_FISCAL_MONTH_START)) ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1;
	print dol_print_date(dol_mktime(12,0,0,$monthstart,1,2000,1),'%B','gm') . '</TD></TR>';

	print "</TABLE>";

	/*
	 *  tax options
	 */
	print '<BR>';
	print '<TABLE class="noborder" width="100%">';
	print '<TR class="liste_titre">';
	print '<TD>'.$langs->trans("VATManagement").'</TD><TD>'.$langs->trans("Description").'</TD>';
	print '<TD align="right">&nbsp;</TD>';
	print "</TR>\n";

	
	print "<TR class=\"oddeven\"><TD width=\"160\"><INPUT class=\"oddeven\" type=\"radio\" name=\"optiontva\" id=\"use_vat\" disabled value=\"1\"".(empty($conf->global->FACTURE_TVAOPTION)?"":" checked")."> ".$langs->trans("VATIsUsed")."</TD>";
	print '<TD colspan="2">';
	print "<TABLE>";
	print "<TR><TD><LABEL for=\"use_vat\">".$langs->trans("VATIsUsedDesc")."</LABEL></TD></TR>";
	print "<TR><TD><I>".$langs->trans("Example").': '.$langs->trans("VATIsUsedExampleFR")."</I></TD></TR>\n";
	print "</TABLE>";
	print "</TD></TR>\n";

	
	print "<TR class=\"oddeven\"><TD width=\"160\"><INPUT class=\"oddeven\" type=\"radio\" name=\"optiontva\" id=\"no_vat\" disabled value=\"0\"".(empty($conf->global->FACTURE_TVAOPTION)?" checked":"")."> ".$langs->trans("VATIsNotUsed")."</TD>";
	print '<TD colspan="2">';
	print "<TABLE>";
	print "<TR><TD><LABEL=\"no_vat\">".$langs->trans("VATIsNotUsedDesc")."</LABEL></TD></TR>";
	print "<TR><TD><I>".$langs->trans("Example").': '.$langs->trans("VATIsNotUsedExampleFR")."</I></TD></TR>\n";
	print "</TABLE>";
	print "</TD></TR>\n";

	print "</TABLE>";


	/*
	 *  Local Taxes
	 */
	if ($mysoc->useLocalTax(1))    // True if we found at least on vat with a setup adding a localtax 1
	{
		// Local Tax 1
		print '<BR>';
		print '<TABLE class="noborder" width="100%">';
		print '<TR class="liste_titre">';
		print '<TD>'.$langs->transcountry("LocalTax1Management",$mysoc->country_code).'</TD><TD>'.$langs->trans("Description").'</TD>';
		print '<TD align="right">&nbsp;</TD>';
		print "</TR>\n";

		
		print "<TR class=\"oddeven\"><TD width=\"160\"><INPUT class=\"oddeven\" type=\"radio\" name=\"optionlocaltax1\" id=\"lt1\" disabled value=\"localtax1on\"".(($conf->global->FACTURE_LOCAL_TAX1_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1on")?" checked":"")."> ".$langs->transcountry("LocalTax1IsUsed",$mysoc->country_code)."</TD>";
		print '<TD colspan="2">';
		print "<TABLE>";
		print "<TR><TD></LABEL for=\"lt1\">".$langs->transcountry("LocalTax1IsUsedDesc",$mysoc->country_code)."</LABEL></TD></TR>";
		$example=$langs->transcountry("LocalTax1IsUsedExample",$mysoc->country_code);
		print ($example!="LocalTax1IsUsedExample"?"<TR><TD><I>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsUsedExample",$mysoc->country_code)."</I></TD></TR>\n":"");
		if($conf->global->MAIN_INFO_VALUE_LOCALTAX1!=0)
		{
			print '<TR><TD>'.$langs->trans("LTRate").': '. $conf->global->MAIN_INFO_VALUE_LOCALTAX1 .'</TD></TR>';
		}
		print '<TR><TD align="left">'.$langs->trans("CalcLocaltax").': ';
		if($conf->global->MAIN_INFO_LOCALTAX_CALC1==0)
		{
			print $langs->trans("CalcLocaltax1").' - '.$langs->trans("CalcLocaltax1Desc");
		}
		else if($conf->global->MAIN_INFO_LOCALTAX_CALC1==1)
		{
			print $langs->trans("CalcLocaltax2").' - '.$langs->trans("CalcLocaltax2Desc");
		}
		else if($conf->global->MAIN_INFO_LOCALTAX_CALC1==2){
			print $langs->trans("CalcLocaltax3").' - '.$langs->trans("CalcLocaltax3Desc");
		}

		print '</TD></TR>';
		print "</TABLE>";
		print "</TD></TR>\n";

		
		print "<TR class=\"oddeven\"><TD width=\"160\"><INPUT class=\"oddeven\" type=\"radio\" name=\"optionlocaltax1\" id=\"nolt1\" disabled value=\"localtax1off\"".((empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1off")?" checked":"")."> ".$langs->transcountry("LocalTax1IsNotUsed",$mysoc->country_code)."</TD>";
		print '<TD colspan="2">';
		print "<TABLE>";
		print "<TR><TD><LABEL for=\"no_lt1\">".$langs->transcountry("LocalTax1IsNotUsedDesc",$mysoc->country_code)."</LABEL></TD></TR>";
		$example=$langs->transcountry("LocalTax1IsNotUsedExample",$mysoc->country_code);
		print ($example!="LocalTax1IsNotUsedExample"?"<TR><TD><I>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsNotUsedExample",$mysoc->country_code)."</I></TD></TR>\n":"");
		print "</TABLE>";
		print "</TD></TR>\n";

		print "</TABLE>";
	}
	if ($mysoc->useLocalTax(2))    // True if we found at least on vat with a setup adding a localtax 1
	{
		// Local Tax 2
		print '<BR>';
		print '<TABLE class="noborder" width="100%">';
		print '<TR class="liste_titre">';
		print '<TD>'.$langs->transcountry("LocalTax2Management",$mysoc->country_code).'</TD><TD>'.$langs->trans("Description").'</TD>';
		print '<TD align="right">&nbsp;</TD>';
		print "</TR>\n";

		
		print "<TR class=\"oddeven\"><TD width=\"160\"><INPUT class=\"oddeven\" type=\"radio\" name=\"optionlocaltax2\" id=\"lt2\" disabled value=\"localtax2on\"".(($conf->global->FACTURE_LOCAL_TAX2_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2on")?" checked":"")."> ".$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code)."</TD>";
		print '<TD colspan="2">';
		print "<TABLE>";
		print "<TR><TD><LABEL for=\"lt2\">".$langs->transcountry("LocalTax2IsUsedDesc",$mysoc->country_code)."</LABEL></TD></TR>";
		$example=$langs->transcountry("LocalTax2IsUsedExample",$mysoc->country_code);
		print ($example!="LocalTax2IsUsedExample"?"<TR><TD><I>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsUsedExample",$mysoc->country_code)."</I></TD></TR>\n":"");
		if($conf->global->MAIN_INFO_VALUE_LOCALTAX2!=0)
		{
			print '<TR><TD>'.$langs->trans("LTRate").': '. $conf->global->MAIN_INFO_VALUE_LOCALTAX2 .'</TD></TR>';
		}
		print '<TR><TD align="left">'.$langs->trans("CalcLocaltax").': ';
		if($conf->global->MAIN_INFO_LOCALTAX_CALC2==0)
		{
			print $langs->trans("CalcLocaltax1").' - '.$langs->trans("CalcLocaltax1Desc");
		}
		else if($conf->global->MAIN_INFO_LOCALTAX_CALC2==1)
		{
			print $langs->trans("CalcLocaltax2").' - '.$langs->trans("CalcLocaltax2Desc");
		}
		else if($conf->global->MAIN_INFO_LOCALTAX_CALC2==2)
		{
			print $langs->trans("CalcLocaltax3").' - '.$langs->trans("CalcLocaltax3Desc");
		}

		print '</TD></TR>';
		print "</TABLE>";
		print "</TD></TR>\n";

		
		print "<TR class=\"oddeven\"><TD width=\"160\"><INPUT class=\"oddeven\" type=\"radio\" name=\"optionlocaltax2\" id=\"nolt2\" disabled value=\"localtax2off\"".((empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2off")?" checked":"")."> ".$langs->transcountry("LocalTax2IsNotUsed",$mysoc->country_code)."</TD>";
		print '<TD colspan="2">';
		print "<TABLE>";
		print "<TR><TD><LABEL for=\"nolt2\">".$langs->transcountry("LocalTax2IsNotUsedDesc",$mysoc->country_code)."</LABEL></TD></TR>";
		$example=$langs->transcountry("LocalTax2IsNotUsedExample",$mysoc->country_code);
		print ($example!="LocalTax2IsNotUsedExample"?"<TR><TD><I>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsNotUsedExample",$mysoc->country_code)."</I></TD></TR>\n":"");
		print "</TABLE>";
		print "</TD></TR>\n";

		print "</TABLE>";
	}


	// Actions buttons
	print '<DIV class="tabsAction">';
	print '<DIV class="inline-block divButAction"><A class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</A></DIV>';
	print '</DIV>';

	print '<BR>';
}

 		if ($action != 'editlogin' && $user->rights->adherent->creer)
		{
if ($user->rights->user->user->creer)
			{
				print '<A href="'.$_SERVER["PHP_SELF"].'?action=editlogin&amp;rowid=1">'.img_edit($langs->trans('SetLinkToUser'),1).'</A>';
			}
			print '<BR>';
		}

		if ($action == 'editlogin')
		{
			$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'userid','');
		}
		else
		{
			if (1==1)
			{
				$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'none');
			}
			else print $langs->trans("NoDolibarrAccess");
		}
$accountid=$conf->global->STRIPE_INTERNAL_ACCOUNT; 
            $form->select_comptes($accountid,'accountid',0,'',2);
            print '<INPUT name="accountid" type="hidden" value="'.$conf->global->STRIPE_INTERNAL_ACCOUNT.'">';
$accountid=$conf->global->STRIPE_EXTERNAL_ACCOUNT; 
            $form->select_comptes($accountid,'accountid',0,'',2);
            print '<INPUT name="accountid" type="hidden" value="'.$conf->global->STRIPE_EXTERNAL_ACCOUNT.'">';            
            
            
    $form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'userid','');
 print $_FILES[$varforimage]["tmp_name"]; 
 
//$account = \Stripe\Account::retrieve("acct_1AjpB7Ap9bdBykyB");
//print $account->external_accounts->retrieve("ba_1AfE1jFNEmQ3Lcv54TfzYRUe");

// $bank=\Stripe\Token::create(array(
//  "bank_account" => array(
//    "country" => "DE",
//    "currency" => "EUR",
//    "account_number" => "DE89370400440532013000"
//                         
//  )
//));
//print $bank;
//$account->external_accounts->create(array("external_account" => "".$bank->id.""));

}
else {
print "<BR><BR><CENTER><B>Prochainement vous allez pouvoir utiliser Stripe pour vos paiements en ligne</B></CENTER><BR><BR>";

$string=$langs->trans("StripeLegacy"); 

//$string = 'The quick brown fox jumped over the lazy dog.';
$patterns[0] = '/platformname/';

$replacements[0] = 'ptibogxiv.net';
print preg_replace($patterns, $replacements, $string);
print '<FORM name="add" method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<INPUT type="hidden" name="action" value="add_confirm">';
print '<BR><BR><BR><DIV class="center"><INPUT type="submit" class="butActionRefused" value="'.$langs->trans("StripeAdd").'"></DIV>';

print "</FORM>";



}

llxFooter();
$db->close();