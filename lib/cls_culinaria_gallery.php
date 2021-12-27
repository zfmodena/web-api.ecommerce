<?php

include_once "sql.php";

class culinaria_galeri extends sql {

    public static function daftar_culinaria_galeri($arr_parameter = array(), $limit = "") {
        $sql = "select * from galeri ";

        if (count($arr_parameter) > 0)
            $sql .= " where " . sql::sql_parameter($arr_parameter);

        try {
            return sql::execute($sql . ( $limit != "" ? "limit " . $limit : "" ));
        } catch (Exception $e) {
            $e->getMessage();
        }
    }
	
	public static function daftar_culinaria_image($arr_parameter = array(), $limit = "") {
        $sql = "select * from galeri_media ";

        if (count($arr_parameter) > 0)
            $sql .= " where " . sql::sql_parameter($arr_parameter);

        try {
            return sql::execute($sql . "order by galeri_urutan asc" . ( $limit != "" ? "limit " . $limit : "" ));
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public static function deskripsi_galeri($arr_parameter = array(), $limit = ""){
        global $lang;
        $sql = "select galeri_judul_".$lang." from galeri  ";
        if (count($arr_parameter) > 0)
            $sql .= " where   " . sql::sql_parameter($arr_parameter) . "  ";
        
        try {
            return sql::execute($sql . ( $limit != "" ? "limit " . $limit : "" ));
        } catch (Exception $e) {
            $e->getMessage();
        }
    }
    
   

   

    /* $arr_col : array() : [nama kolom]=>[nilai kolom] */

    static function insert_culinaria_gallery($arr_col) {
        $sql = "insert into galeri (" . implode(",", array_keys($arr_col)) . ") values(" . implode(",", array_values($arr_col)) . ");";
        return sql::execute($sql);
    }

}

?>