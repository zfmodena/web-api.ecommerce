<?

include __DIR__ . "/lib/cls_culinaria_program.php";
include __DIR__ . "/../lang/id.php";
include __DIR__ . "/../lang/". $lang ."_culinaria.php";
						
$arr_data["cooking_class"] = culinaria_program::box_program('','','HYBRID');

echo json_encode( $arr_data );

?>