<?
include "var.php";
mysql_query("DROP PROCEDURE IF EXISTS `__search_product`;");
mysql_query("
CREATE PROCEDURE `__search_product`(lang varchar(5), search varchar(200))
begin
set @sql='select distinct c.categoryid, c.name categoryname, 
b.categoryid sub_categoryid, b.name sub_categoryname, a.productid, a.name from product a, category b, category c, 
productproperty d, category_productpropertyschema e, productpropertyschema f
where a.categoryid=b.categoryid and b.parentcategoryid=c.categoryid and 
a.productid=d.productid and b.categoryid=e.categoryid and e.productpropertyschemaid=f.productpropertyschemaid and
b.enabled=true and c.enabled=true and a.penable=\'Y\' and (
	a.name like \'%$search$%\' or
	b.name like \'%$search$%\' or
	c.name like \'%$search$%\' or
	d.value_$lang$ like \'%$search$%\' or
	f.name_$lang$ like \'%$search$%\'
)';
set @sql=replace(@sql, '$lang$', lang);
set @sql=replace(@sql, '$search$', search);
prepare stmt from @sql;
EXECUTE stmt;
end;") or die(mysql_error());
?>