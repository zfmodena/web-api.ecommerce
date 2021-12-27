<?

include __DIR__ . "/lib/cls_culinaria_program.php";
include __DIR__ . "/../lang/id.php";
include __DIR__ . "/../lang/". $lang ."_culinaria.php";

$template = "<div class=\"gallery-container\" style=\"#div-middle#\">
					<div class=\"content\">#program-content#</div>
					<img class=\"layer\" src=\"#url-path-image##path#\" id=\"#idnya#\" />
				</div>";

$template_content = "<div>
							<h4 class=\"h4-tanggal_awal\">#tanggal_awal#</h4>
							<hr noshade />#kelas_memasak#
							<hr noshade />#harga#
							<div class=\"content-footer\" style=\"text-align:right; float:right\">". $lang_hari_program_tersisa ."</div>
							<div class=\"content-footer\" style=\"text-align:right; float:right; margin-top:17px\">
								<input type=\"button\" value=\"". strtoupper( $lang_detail ) ."\" class=\"culinaria-button\" name=\"b_detail_#idnya#\" id=\"b_detail_#idnya#\" onclick=\"javascript:location.href='publikasi_detail_culinaria.html?id=#idnya#'\" />
								<input type=\"button\" value=\"#label_button#\" class=\"culinaria-button\" #disabled# name=\"b_daftar_#idnya#\" id=\"b_daftar_#idnya#\" onclick=\"cordova.InAppBrowser.open('http://www.modena.co.id/culinaria-cart.php?id=#idnya#', '_blank', 'location=no')\" />
							</div>
						</div>";
						
$arr_data["cooking_class"] = culinaria_program::box_program($template, $template_content);

echo json_encode( $arr_data );

?>