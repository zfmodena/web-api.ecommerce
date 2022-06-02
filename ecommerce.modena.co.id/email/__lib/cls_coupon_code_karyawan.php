<?

$regexp = "/^[0-9]{4}[.][0-9]{4}$/";

if ( preg_match($regexp, $code) ){ // masuk ke prosedur diskon bertingkat khusus kode promo karyawan, $return_discount dalam satuan IDR
	
	/*
	jika total netto setelah discount 10%+20% nilainya diatas Rp 10 juta maka ada tambahan discount lagi 5% sehingga menjadi 10%+20%+5% = 31.6%
	*/
	
	if( $discount_grandtotal > 10000000 ){
		
		$discount_price_tambahan =  $discount_grandtotal * 0.05;
		$discount_grandtotal = $discount_grandtotal - $discount_price_tambahan;
		$discount_price = $discount_price + $discount_price_tambahan;
		$discount_coupon = $discount_price;
		
	}		
	
}

?>