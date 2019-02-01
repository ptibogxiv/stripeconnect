<?php
/* Copyright (C) 2011-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 *      \file       htdocs/compta/salaries/class/paymentsalary.class.php
 *      \ingroup    salaries
 *      \brief		Class for salaries module payment
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *  Class to manage salary payments
 */
class PaymentStripeFee extends CommonObject
{
	//public $element='payment_salary';			//!< Id that identify managed objects
	//public $table_element='payment_salary';	//!< Name of table without prefix where object is stored
    public $picto='payment';

	public $tms;
	public $fk_soc;
	public $datep;
	public $datev;
  public $mode;
	public $stripe_fee;
  public $application_fee;
  public $currency;
	public $type_payment;
	public $num_payment;
	public $datesp;
	public $dateep;
	public $fk_bank;
	public $fk_user_author;
	public $fk_user_modif;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->element = 'stripeconnect_fee';
		$this->table_element = 'stripeconnect_fee';
		return 1;
	}

	/**
	 * Update database
	 *
	 * @param   User	$user        	User that modify
	 * @param	int		$notrigger	    0=no, 1=yes (no update trigger)
	 * @return  int         			<0 if KO, >0 if OK
	 */
	function update($user=null, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->fk_soc=trim($this->fk_soc);
		$this->amount=trim($this->amount);
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_author=trim($this->fk_user_author);
		$this->fk_user_modif=trim($this->fk_user_modif);

		// Check parameters
		if (empty($this->fk_user) || $this->fk_user < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."stripeconnect_fee SET";

		$sql.= " tms='".$this->db->idate($this->tms)."',";
		$sql.= " fk_soc=".$this->fk_soc.",";
		$sql.= " datep='".$this->db->idate($this->datep)."',";
		$sql.= " datev='".$this->db->idate($this->datev)."',";
		$sql.= " amount=".price2num($this->amount).",";
		$sql.= " fk_typepayment=".$this->fk_typepayment."',";
		$sql.= " num_payment='".$this->db->escape($this->num_payment)."',";
		$sql.= " label='".$this->db->escape($this->label)."',";
		$sql.= " datesp='".$this->db->idate($this->datesp)."',";
		$sql.= " dateep='".$this->db->idate($this->dateep)."',";
		$sql.= " note='".$this->db->escape($this->note)."',";
		$sql.= " fk_bank=".($this->fk_bank > 0 ? "'".$this->fk_bank."'":"null").",";
		$sql.= " fk_user_author=".$this->fk_user_author.",";
		$sql.= " fk_user_modif=".$this->fk_user_modif;

		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}

		if (! $notrigger)
		{
            // Call trigger
            $result=$this->call_trigger('PAYMENT_SALARY_MODIFY',$user);
            if ($result < 0) $error++;
            // End call triggers
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         id object
	 *  @param  User	$user       User that load
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function fetch($id, $user=null)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " s.rowid,";

		$sql.= " s.tms,";
		$sql.= " s.fk_soc,";
		$sql.= " s.datep,";
		$sql.= " s.datev,";
		$sql.= " s.amount,";
		$sql.= " s.fk_typepayment,";
		$sql.= " s.num_payment,";
    $sql.= " s.mode,";
		$sql.= " s.label,";
		$sql.= " s.datesp,";
		$sql.= " s.dateep,";
		$sql.= " s.note,";
		$sql.= " s.fk_bank,";
		$sql.= " s.fk_user_author,";
		$sql.= " s.fk_user_modif,";
		$sql.= " b.fk_account,";
		$sql.= " b.fk_type,";
		$sql.= " b.rappro";

		$sql.= " FROM ".MAIN_DB_PREFIX."stripeconnect_fee as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON s.fk_bank = b.rowid";
		$sql.= " WHERE s.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;
				$this->tms   = $this->db->jdate($obj->tms);
				$this->fk_soc = $obj->fk_soc;
				$this->datep = $this->db->jdate($obj->datep);
				$this->datev = $this->db->jdate($obj->datev);
				$this->amount = $obj->amount;
				$this->type_payement = $obj->fk_typepayment;
				$this->num_payment = $obj->num_payment;
				$this->label = $obj->label;
				$this->datesp = $this->db->jdate($obj->datesp);
				$this->dateep = $this->db->jdate($obj->dateep);
				$this->note  = $obj->note;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_account = $obj->fk_account;
				$this->fk_type = $obj->fk_type;
				$this->rappro  = $obj->rappro;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param	User	$user       User that delete
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function delete($user)
	{
		global $conf, $langs;

		$error=0;

		// Call trigger
		$result=$this->call_trigger('PAYMENT_SALARY_DELETE',$user);
		if ($result < 0) return -1;
		// End call triggers


		$sql = "DELETE FROM ".MAIN_DB_PREFIX."stripeconnect_fee";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}

		return 1;
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->tms='';
		$this->fk_soc='';
		$this->datep='';
		$this->datev='';
		$this->stripe_fee='';
		$this->label='';
		$this->datesp='';
		$this->dateep='';
		$this->note='';
		$this->fk_bank='';
		$this->fk_user_author='';
		$this->fk_user_modif='';
	}

    /**
     *  Create in database
     *
     *  @param      User	$user       User that create
     *  @return     int      			<0 if KO, >0 if OK
     */
	function create($user)
	{
		global $conf,$langs;

		$error=0;
		$now=dol_now();

		// Clean parameters
		$this->stripe_fee=price2num(trim($this->stripe_fee));
    $this->application_fee=price2num(trim($this->application_fee));
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_author=trim($this->fk_user_author);
		$this->fk_user_modif=trim($this->fk_user_modif);

		// Check parameters
		if ($this->fk_soc < 0 || $this->fk_soc == '')
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Employee"));
			return -4;
		}
		if ($this->stripe_fee < 0 || $this->stripe_fee == '')
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"));
			return -5;
		}
    if ($this->application_fee < 0 || $this->application == '')
		{
			//$this->application_fee = 0;
		}
		if (! empty($conf->banque->enabled) && (empty($this->accountid) || $this->accountid <= 0))
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Account"));
			return -6;
		}
		if (! empty($conf->banque->enabled) && (empty($this->type_payment) || $this->type_payment <= 0))
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
			return -7;
		}

		$this->db->begin();
if (empty($conf->global->STRIPECONNECT_LIVE))
{
$mode=$conf->global->STRIPECONNECT_LIVE;
}
else 
{
$mode=$conf->global->STRIPE_LIVE;	
}
		// Insert into llx_payment_salary
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."stripeconnect_fee (fk_soc";
		$sql.= ", datep";
		$sql.= ", datev";
		$sql.= ", stripe_fee";
    $sql.= ", application_fee";
		$sql.= ", multicurrency_code";    
		$sql.= ", mode";
		$sql.= ", fk_typepayment";
		$sql.= ", num_payment";
		if ($this->note) $sql.= ", note";
		$sql.= ", datesp";
		$sql.= ", dateep";
		$sql.= ", fk_user_author";
		$sql.= ", datec";
		$sql.= ", fk_bank";
		$sql.= ", entity";
		$sql.= ") ";
		$sql.= " VALUES (";
		$sql.= "'".$this->fk_soc."'";
		$sql.= ", '".$this->db->idate($this->datep)."'";
		$sql.= ", '".$this->db->idate($this->datev)."'";
		$sql.= ", ".$this->stripe_fee;
    $sql.= ", ".$this->application_fee;
    $sql.= ", '".$this->currency."'";
		$sql.= ", ".$mode;
		$sql.= ", '".$this->type_payment."'";
		$sql.= ", '".$this->num_payment."'";
		if ($this->note) $sql.= ", '".$this->db->escape($this->note)."'";
		$sql.= ", '".$this->db->idate($this->datesp)."'";
		$sql.= ", '".$this->db->idate($this->dateep)."'";
		$sql.= ", '".$user->id."'";
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", NULL";
		$sql.= ", ".$conf->entity;
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{

			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."stripeconnect_fee");

			

			if (! $error)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				return $error;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return $this->error;
		}
	}

	/**
	 *  Update link between payment salary and line generate into llx_bank
	 *
	 *  @param	int		$id_bank    Id bank account
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function update_fk_bank($id_bank)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'stripeconnect_fee SET fk_bank = '.$id_bank;
		$sql.= ' WHERE rowid = '.$this->id;
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	Send name clicable (with possibly the picto)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option			link option
	 *	@return	string					Chaine with URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowSalaryPayment").': '.$this->ref;

        $link = '<a href="'.DOL_URL_ROOT.'/custom/stripeconnect/stripefee.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='payment';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.$this->ref.$linkend;
		return $result;
	}

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	function info($id)
	{
		$sql = 'SELECT ps.rowid, ps.datec, ps.fk_user_author';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'stripeconnect_fee as ps';
		$sql.= ' WHERE ps.rowid = '.$id;

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 * Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *
	 * @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return  string				Libelle
	 */
	function getLibStatut($mode=0)
	{
	    return $this->LibStatut($this->statut,$mode);
	}

	/**
	 * Renvoi le libelle d'un statut donne
	 *
	 * @param   int		$status     Statut
	 * @param   int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return	string  		    Libelle du statut
	 */
	function LibStatut($status,$mode=0)
	{
	    global $langs;	// TODO Renvoyer le libelle anglais et faire traduction a affichage

	    $langs->load('compta');
	    /*if ($mode == 0)
	    {
	        if ($status == 0) return $langs->trans('ToValidate');
	        if ($status == 1) return $langs->trans('Validated');
	    }
	    if ($mode == 1)
	    {
	        if ($status == 0) return $langs->trans('ToValidate');
	        if ($status == 1) return $langs->trans('Validated');
	    }
	    if ($mode == 2)
	    {
	        if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
	        if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
	    }
	    if ($mode == 3)
	    {
	        if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1');
	        if ($status == 1) return img_picto($langs->trans('Validated'),'statut4');
	    }
	    if ($mode == 4)
	    {
	        if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
	        if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
	    }
	    if ($mode == 5)
	    {
	        if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
	        if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
	    }
		if ($mode == 6)
	    {
	        if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
	        if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
	    }*/
	    return '';
	}

}
