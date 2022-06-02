<?

include_once "cls_main.php";

class coupon_code extends main{
	private $data=array();
	
	public function __set($name, $value){
		$this->data[$name] = $value;
	}
	
	public function __get($name){
		return $this->data[$name];
	}

	public function __isset($name) {
        return isset($this->data[$name]);
    }
	
	public function __unset($name) {
        unset($this->data[$name]);
    }

/*

kelas untuk generating kode promo dengan konsep paket.

2 mode kode :
-. sufix = kode+paket
-. prefix = paket+kode

1 paket dapat terdiri dari beberapa jenis kode promo dengan spesifikasi yang berbeda-beda yaitu :
-. besaran diskon yang berbeda.
-. nilai minimal sales atau kuantitas pembelian yang berbeda.
-. expired date yang berbeda.
-. limitasi yang berbeda.

generator kode promo terdapat fungsi untuk auto insert ke database ataupun tidak (hanya sebagai preview).

*/	
	
/* ############################# metode umum ########################################*/

/*
fungsi untuk generate kode promo
Return ARRAY()
*/
	function generate_promotion_code(){
			
		// PARAMETER WAJIB
		
		// array paket kode, STRING : array("paket 1", "paket 2", "paket 3", dst)
		if(!isset($this->paket_kode) || !is_array($this->paket_kode)) 		throw new Exception("PAKET KODE not set");
		$paket_kode=$this->paket_kode;
		
		// array besaran diskon, FLOAT ,dalam desimal ataupun nominal : array("diskon 1", "diskon 2", "diskon 3", dst)
		if(!isset($this->paket_diskon) || !is_array($this->paket_diskon)) 	throw new Exception("PAKET DISKON not set");
		if(count($paket_kode)!=count($this->paket_diskon))					throw new Exception("PAKET KODE tidak sama dengan PAKET DISKON");
		$paket_diskon=$this->paket_diskon;		
		
		// array expired, DATETIME ,dalam satuan tanggal : array("exp 1", "exp 2", "exp 3", dst)
		if(!isset($this->paket_expired) || !is_array($this->paket_expired)) 	throw new Exception("PAKET EXPIRED not set");
		if(count($paket_kode)!=count($this->paket_expired))						throw new Exception("PAKET KODE tidak sama dengan PAKET EXPIRED");
		$paket_expired=$this->paket_expired;		
		
		// remark ID kupon
		if(!isset($this->remark_id) || !is_array($this->remark_id)) 	throw new Exception("PAKET REMARKD ID not set");
		if(count($paket_kode)!=count($this->remark_id))						throw new Exception("PAKET KODE tidak sama dengan PAKET REMARKD ID");
		$remark_id=$this->remark_id;		
		
		// remark EN kupon
		if(!isset($this->remark_en) || !is_array($this->remark_en)) 	throw new Exception("PAKET REMARKD EN not set");
		if(count($paket_kode)!=count($this->remark_en))						throw new Exception("PAKET KODE tidak sama dengan PAKET REMARKD EN");
		$remark_en=$this->remark_en;		
		
		// END PARAMETER WAJIB

		// PARAMETER OPSIONAL
		
		// jumlah paket kode, INT
		$jumlah_paket=1;
		if(isset($this->jumlah_paket))	$jumlah_paket=$this->jumlah_paket;
		
		// kode minimal, INT
		$minimal_kode=1000;
		if(isset($this->minimal_kode))	$minimal_kode=$this->minimal_kode;
		
		// kode maksimal, INT
		$maksimal_kode=99999;
		if(isset($this->maksimal_kode))	$maksimal_kode=$this->maksimal_kode;
		
		// apakah langsung insert ke database (BOOL) ?
		$database_insert=false;
		if(isset($this->database_insert)) $database_insert=$this->database_insert;
		
		// mode penempatan kode, STRING, PREFIX || SUFIX
		$arr_mode=array("SUFIX", "PREFIX");
		$mode=$arr_mode[0];
		if(isset($this->mode) && in_array(strtoupper($this->mode), $arr_mode)) $mode=$this->mode;
		
		// array kuantitas minimal paket, INT
		$paket_min_kuantitas=array_fill(0, count($paket_kode), 1);
		if(isset($this->paket_min_kuantitas) &&  is_array($this->paket_min_kuantitas)){
			if(count($paket_kode)!=count($this->paket_min_kuantitas))							throw new Exception("PAKET KODE tidak sama dengan PAKET KUANTITAS MINIMAL");
			$paket_min_kuantitas=$this->paket_min_kuantitas;		
		}
		
		// array sales minimal paket, FLOAT
		$paket_min_sales=array_fill(0, count($paket_kode), 0);
		if(isset($this->paket_min_sales) && is_array($this->paket_min_sales)){
			if(count($paket_kode)!=count($this->paket_min_sales))								throw new Exception("PAKET KODE tidak sama dengan PAKET SALES MINIMAL");
			$paket_min_sales=$this->paket_min_sales;		
		}
		
		// array limitasi paket, BOOL
		$paket_limitasi=array_fill(0, count($paket_kode), 0);
		if(isset($this->paket_limitasi) && is_array($this->paket_limitasi)){
			if(count($paket_kode)!=count($this->paket_limitasi))								throw new Exception("PAKET KODE tidak sama dengan PAKET LIMITASI");
			$paket_limitasi=$this->paket_limitasi;		
		}
		
		// mekanisme kupon
		$mekanisme_kupon="";
		if(isset($this->mekanisme_kupon)) $mekanisme_kupon=$this->mekanisme_kupon;
		
		// kuota pembelian item dengan kupon
		$kuota_kupon="NULL";
		if(isset($this->kuota_kupon)) $kuota_kupon=$this->kuota_kupon;
		
		// status_double_promo
		$arr_status_double_promo=array(0, 1);
		$status_double_promo=$arr_status_double_promo[0];
		if(isset($this->status_double_promo) && in_array(strtoupper($this->status_double_promo), $arr_status_double_promo) ) $status_double_promo=$this->status_double_promo;
		
		// END PARAMETER OPSIONAL
		
		$arr_kode=array();
		$arr_kode_check=array();
		
		for($x=0; $x<$jumlah_paket; $x++){
		
			Repeat:
			$random_kode=mt_rand($minimal_kode, $maksimal_kode);
			
			foreach($paket_kode as $index=>$paket){
			
				if($mode=="SUFIX")	$kode=$random_kode."-".$paket;
				else				$kode=$paket."-".$random_kode;
				
				if($this->is_not_duplicated($kode, $arr_kode_check)){
					$arr_kode_check[$x]=$kode;					
					
					$this->kode_=$kode;													$this->paket_limitasi_=$paket_limitasi[$index];
					$this->paket_diskon_=$paket_diskon[$index];							$this->paket_expired_=$paket_expired[$index];
					$this->paket_min_kuantitas_=$paket_min_kuantitas[$index];			$this->paket_min_sales_=$paket_min_sales[$index];					
					
					$this->remark_id_=$remark_id[$index];
					$this->remark_en_=$remark_en[$index];
					
					$this->mekanisme_kupon_ = $mekanisme_kupon;
					$this->kuota_kupon_ = $kuota_kupon;
					$this->status_double_promo_ = $status_double_promo;
					
					$arr_kode[$x][$index]["kode"]=$kode;
					$arr_kode[$x][$index]["remark_id"]=$remark_id[$index];
					$arr_kode[$x][$index]["remark_en"]=$remark_en[$index];
					$arr_kode[$x][$index]["diskon"]=$paket_diskon[$index];
					$arr_kode[$x][$index]["expired"]=$paket_expired[$index];
					$arr_kode[$x][$index]["minimal_kuantitas"]=$paket_min_kuantitas[$index];
					$arr_kode[$x][$index]["minimal_sales"]=$paket_min_sales[$index];
					$arr_kode[$x][$index]["limitasi"]=$paket_limitasi[$index];
					$arr_kode[$x][$index]["mekanisme"]=$mekanisme_kupon;
					$arr_kode[$x][$index]["kuota"]=$kuota_kupon;
					$arr_kode[$x][$index]["status_double_promo"]=$status_double_promo;
					
					if($database_insert) $this->insert_into_database();
					
					unset($this->kode_, $this->remark_id_, $this->remark_en_, $this->paket_diskon_, $this->paket_expired_, $this->paket_min_kuantitas_, $this->paket_min_sales_, $this->paket_limitasi_, $this->mekanisme_kupon_, $this->kuota_kupon_, $this->status_double_promo_);
				}else goto Repeat;
				
			}
			
		}
		return $arr_kode;
	}
	
	private function is_not_duplicated($kode, $arr_kode=array()){
		if(in_array($kode, $arr_kode)) return false;
		$sql="select * from discount_coupon where trim(coupon_code)='".main::formatting_query_string(trim($kode))."';";
		$rs=mysql_query($sql) or die();
		if(mysql_num_rows($rs)==0) 	return true;
		else						return false;
	}
	
	private function insert_into_database(){
		$string="insert into discount_coupon(
			coupon_code, 
			enabled, 
			expired_date, 
			discount, 
			unlimited, 
			min_qty, 
			min_sales,
			coupon_mekanisme,
			remark_id,
			remark_en,
			qty_quota,
			status_double_promo) values('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s);";
		$sql=sprintf($string, 
			$this->kode_,
			"1",
			$this->paket_expired_,
			$this->paket_diskon_,
			$this->paket_limitasi_,
			$this->paket_min_kuantitas_,
			$this->paket_min_sales_,
			$this->mekanisme_kupon_,
			$this->remark_id_,
			$this->remark_en_,
			$this->kuota_kupon_,
			$this->status_double_promo_
			);
		mysql_query($sql) or die(mysql_error());
	}
	
}


/*include_once "var.php";
try{
	$kode=new coupon_code;
	$kode->paket_kode=		array("SV500", 		"SV1000", 		"SV1500", 		"SV2250");
	$kode->paket_diskon=	array(500000,		1000000,		1500000,		2250000);
	$kode->paket_expired=	array("2/1/2015",	"2/1/2015",		"2/1/2015",		"2/1/2015");
	
	$kode->jumlah_paket=	500;
	$kode->paket_min_sales=	array(2500000,		5000000,		10000000,		15000000);
	
	$kode->database_insert=	true;
	
	$kode->generate_promotion_code();	
}catch(Exception $e){echo $e->getMessage();}*/
?>