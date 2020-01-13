<?php
/* Copyright (C) 2017-2018 	PtibogXIV        <support@ptibogxiv.net>
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

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
//require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/config.php';
/**                                                                                                
 *	\class      Rewards
 *	\brief      Class for Rewards
 */
class StripeConnexion extends CommonObject
{
	public $rowid;
  public $fk_soc;
  public $fk_key;
  public $id;
  public $mode;  
  public $entity;
  public $statut;
  public $type;
  public $code;
  public $message;
	
	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db			Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

	}
  
    /**
     * Charge dans cache la liste des catégories d'hôtes (paramétrable dans dictionnaire)
     *
     * @return int Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    public function load_cache_categories_hosts()
    {
        global $langs;

        if (count($this->cache_categories_hosts)) {
            return 0;
        }
        // Cache deja charge

        $sql = "SELECT rowid, code, label, stripe_enabled, active, favorite";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_mcc";
        $sql .= " WHERE active > 0";
        $sql .= " ORDER BY pos ASC";
        dol_syslog(get_class($this) . "::load_cache_categories_hosts sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label = ($langs->trans("TicketTypeShort" . $obj->code) != ("HostCategoryShort" . $obj->code) ? $langs->trans("HostCategoryShort" . $obj->code) : ($obj->label != '-' ? $obj->label : ''));
                $this->cache_categories_hosts[$obj->rowid]['code'] = $obj->code;
                $this->cache_categories_hosts[$obj->rowid]['label'] = $label;
                $this->cache_categories_hosts[$obj->rowid]['use_default'] = $obj->use_default;
                $this->cache_categories_hosts[$obj->rowid]['pos'] = $obj->pos;
                $i++;
            }
            return $num;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }
	
	/**
	 * 
	 * @param 	Facture 	$facture	Invoice object
	 * @param 	double 		$points		Points to add/remove
	 * @param 	string 		$typemov	Type of movement (increase to add, decrease to remove)
	 * @return int			<0 if KO, >0 if OK
	 */
public function GetStripeAccount($id)
	{
		global $conf;

		$sql = "SELECT key_account";
		$sql.= " FROM ".MAIN_DB_PREFIX."stripeconnect_entity";
		$sql.= " WHERE entity = ".$id.""; 

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
    if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
    $key=$obj->key_account;
    }
    else {$key=0;}
    }
    else {$key=0;}
    
return $key;
	} 
  
public function GetStripeCustomerAccount($id)
	{
		global $conf;

		$sql = "SELECT s.key_account as key_account, s.entity, e.fk_object";
		$sql.= " FROM ".MAIN_DB_PREFIX."stripeconnect_entity as s";
    $sql.= " JOIN ".MAIN_DB_PREFIX."entity_extrafields as e ON s.entity=e.fk_object";
		$sql.= " WHERE e.fk_soc=".$id." "; 

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
    if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
    $key=$obj->key_account;
    }
    else {$key=NULL;}
    }
    else {$key=NULL;}
    
return $key;
	}  
   
public function CustomerStripe($id,$key)
	{
		global $conf;
if (empty($conf->global->STRIPECONNECT_LIVE))
{
$mode=0;
}
else 
{
if (empty($conf->global->STRIPE_LIVE))
{
$mode=0;
}
else
{	
$mode=$conf->global->STRIPE_LIVE;
}
}  
		$sql = "SELECT rowid,fk_soc,fk_key,mode,entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."stripeconnect_societe";
		$sql.= " WHERE fk_soc = ".$id." "; 
		$sql.= " AND  mode=".$mode." AND entity IN (" . getEntity('stripeconnect') . ")";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
    $soc = new Societe($this->db);
    $soc->fetch($id);
    $num=$this->db->num_rows($resql);
			if ($num) {
			$obj = $this->db->fetch_object($resql);
      $tiers = $obj->fk_key; 
      if ($conf->entity==1){
      $customer = \Stripe\Customer::retrieve("$tiers");
      }else{
      $customer = \Stripe\Customer::retrieve("$tiers",array("stripe_account" => $key));
      }}
      else {
      if ($conf->entity==1){
      $customer = \Stripe\Customer::create(array(
      "email" => $soc->email,
      "description" => $soc->name
      ));
      }else{
      $customer = \Stripe\Customer::create(array(
      "email" => $soc->email,
      "description" => $soc->name
      ), array("stripe_account" => $key));
      }
      $customer_id = "".$customer->id."";
      $sql  = "INSERT INTO ".MAIN_DB_PREFIX."stripeconnect_societe (fk_soc,fk_key,mode,entity)";
      $sql .= " VALUES ($id,'$customer_id',".$mode.",".$conf->entity.")"; 
      dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
      $resql = $this->db->query($sql);
      }}
return $customer;
	}
  
public function CreatePaymentStripe($amount,$currency,$origin,$item,$source,$customer,$account)
{
global $conf;
if (empty($conf->global->STRIPECONNECT_LIVE))
{
$mode=0;
}
else 
{
if (empty($conf->global->STRIPE_LIVE))
{
$mode=0;
}
else
{	
$mode=$conf->global->STRIPE_LIVE;
}
} 
		$sql = "SELECT fk_soc,fk_key,mode,entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."stripeconnect_societe";
		$sql.= " WHERE fk_key = '$customer' "; 
		$sql.= " AND mode=".$mode." "; 

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
    if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
    $entite=$obj->entity;
    $fksoc=$obj->fk_soc;
    }
    }
$stripeamount=round($amount*100);   
$societe = new Societe($this->db);
$societe->fetch($fksoc);

if ($origin==order){
$order=new Commande($this->db);
$order->fetch($item);
$ref=$order->ref;
$description="ORD=".$ref.".CUS=".$societe->code_client;
}
elseif ($origin==invoice) {
$invoice=new Facture($this->db);
$invoice->fetch($item);
$ref=$invoice->ref;
$description="INV=".$ref.".CUS=".$societe->code_client;
} 

$metadata = array(
    "source" => "".$origin."",
    "idsource" => "".$item."",
    "idcustomer" => "".$societe->id.""
  );
$return = new StripeConnexion($this->db);   
try {
if ($stripeamount>=100) {
if ($entite=='1'){
if (preg_match('/acct_/i',$source)){
   $charge = \Stripe\Charge::create(array(
  "amount" => "$stripeamount",
  "currency" => "$currency",
//  "statement_descriptor" => " ",
  "metadata" => $metadata,
  "source" => "$source"
)
);
} else {
$charge = \Stripe\Charge::create(array(
  "amount" => "$stripeamount",
  "currency" => "$currency",
//  "statement_descriptor" => " ",
  "description" => "$description",
  "metadata" => $metadata,
  "receipt_email" => $societe->email,
  "source" => "$source",
  "customer" => "$customer") 
 ,array("idempotency_key" => "$ref")
); 
}}else{
$fee=round(($amount*($conf->global->STRIPE_APPLICATION_FEE_PERCENT/100)+$conf->global->STRIPE_APPLICATION_FEE)*100);
if ($fee<($conf->global->STRIPE_APPLICATION_FEE_MINIMAL*100)){
$fee=round($conf->global->STRIPE_APPLICATION_FEE_MINIMAL*100);
}
$charge = \Stripe\Charge::create(array(
  "amount" => "$stripeamount",
  "currency" => "$currency",
//  "statement_descriptor" => " ",
  "description" => "$description",
  "metadata" => $metadata,
  "source" => "$source",
  "customer" => "$customer",
  "application_fee" => "$fee"
), array("idempotency_key" => "$ref","stripe_account" => "$account"));
}
if (isset($charge->id)){

}
}

$return->statut = 'success';
$return->id = $charge->id;
if ($charge->source->type=='card'){
$return->message = $charge->source->card->brand." ****".$charge->source->card->last4;
}elseif ($charge->source->type=='three_d_secure'){
$stripeconnect=new StripeConnexion($this->db);
$src = \Stripe\Source::retrieve("".$charge->source->three_d_secure->card."",array("stripe_account" => $stripeconnect->GetStripeAccount($conf->entity)));
$return->message = $src->card->brand." ****".$src->card->last4;
}else {
$return->message = $charge->id;
}

} catch(\Stripe\Error\Card $e) {
        // Since it's a decline, \Stripe\Error\Card will be caught
        $body = $e->getJsonBody();
        $err  = $body['error'];
        
$return->statut = 'error';        
$return->id = $err['charge'];
$return->type = $err['type'];
$return->code = $err['code'];
$return->message = $err['message'];
$body = "Une erreur de paiement est survenue. Voici le code d'erreur: <br />".$return->id." ".$return->message." "; 
$subject = '[NOTIFICATION] Erreur de paiement';
$headers = 'From: "ptibogxiv.net" <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
mail(''.$conf->global->MAIN_INFO_SOCIETE_MAIL.'', $subject, $body, $headers); 
        $error++;
        dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
    } catch (\Stripe\Error\RateLimit $e) {
        // Too many requests made to the API too quickly
        $error++;
        dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
    } catch (\Stripe\Error\InvalidRequest $e) {
        // Invalid parameters were supplied to Stripe's API
        $error++;
        dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
    } catch (\Stripe\Error\Authentication $e) {
        // Authentication with Stripe's API failed
        // (maybe you changed API keys recently)
        $error++;
        dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
    } catch (\Stripe\Error\ApiConnection $e) {
        // Network communication with Stripe failed
        $error++;
        dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
    } catch (\Stripe\Error\Base $e) {
        // Display a very generic error to the user, and maybe send
        // yourself an email
        $error++;
        dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
    } catch (Exception $e) {
        // Something else happened, completely unrelated to Stripe
        $error++;
        dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
    }      
        return $return;
} 
   
}