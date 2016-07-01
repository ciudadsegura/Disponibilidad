<?php
set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
/** PHPExcel_IOFactory */
set_time_limit(0);
include 'PHPExcel/IOFactory.php';
include 'conexion.php';
include 'dispo_resp.php';


$archivoExcel7k="LOGS ASR 7K_MAYO 2016.xlsx";
$archivoExcel8k="LOGS ASR 8K_MAYO 2016.xlsx";
$facturacion="FACTURCION 7K MAYO 2016.xlsx";

class MyReadFilter implements PHPExcel_Reader_IReadFilter
{
    public function __construct($Columns,$rowIni=0,$rowFin=0) {
        $this->columns = $Columns;
        $this->rowIni=$rowIni;
        $this->rowFin=$rowFin;
    }

	public function readCell($column, $row, $worksheetName = '') {

		// Read title row and rows 0 - 30
		if($this->rowIni>0 && $this->rowFin>0){
			if ($row >= $this->rowIni && $row <= $this->rowFin) {
				//Read Columns $columns[]
			 	if (in_array($column, $this->columns)) {
              		return true;
          		}
			}

		}else if($this->rowIni>0){

			if ($row >= $this->rowIni) {
				//Read Columns $columns[]
			 	if (in_array($column, $this->columns)) {
              		return true;
          		}
			}

		}else if($this->rowFin>0){
			if ($row <= $this->rowFin) {
				//Read Columns $columns[]
			 	if (in_array($column, $this->columns)) {
              		return true;
          		}
			}


		}else{
			if (in_array($column, $this->columns)) {
          		return true;
      		}
		}

		return false;
	}
}

function cmp($a,$b){
	return strcmp($a["fecha_log"],$b["fecha_log"]);
}

function excelExecFact($archivoExcel,$columnas,$base){
	$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	//$objReader->setLoadSheetsOnly("Detalle 7K´S"); 
	//$objReader->setLoadSheetsOnly($hoja); 																																																																																																																				
	$objReader->setReadDataOnly(true);
	$objReader->setReadFilter( new MyReadFilter($columnas,2 ,null) );

	$objPHPExcel = $objReader->load($archivoExcel);
	$objWorksheet = $objPHPExcel->getActiveSheet();

	$i=0;
	$tuplas=array();
	$fecha_consulta=date("Y-m-d H:i:s");

	if(file_exists("LOG_PROCESS.sql"))
	{
		unlink("LOG_PROCESS.sql");
	}

	$fp=fopen("LOG_PROCESS.sql","a");

	foreach ($objWorksheet->getRowIterator() as $row) {
	  $cellIterator = $row->getCellIterator();
	  $cellIterator->setIterateOnlyExistingCells(false); // This loops all cells,
	                                                     // even if it is not set.
	                                                     // By default, only cells
	                                                     // that are set will be
	                                                     // iterated.
	  if($i==0){
	  	$i++;
	  	continue;
	  }

	  $j=0;
	  $row=array();

	  foreach ($cellIterator as $cell) {
	    $row[]=preg_replace("/'/", "", $cell->getValue());
	    $j++;

	    if($j==count($columnas)){
	    	break;
	    }
	  }
	  if(count($row)>0){
		  	$lineaInsert=$row[1]."\t".$row[2]."\n";
			fputs($fp,$lineaInsert);
		}
		$i++;
	}

	fclose($fp);

	$query="LOAD DATA LOCAL INFILE '/var/www/html/logsys2/LOG_PROCESS.sql' INTO TABLE facturacion";
	$result =conexion("bgpr",$base,$query);
	if($result[0]['evento']=='error'){
		echo($result[0]['msg']);
	}
}

function excelExec($archivoExcel,$hoja,$columnas,$base){
	$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	//$objReader->setLoadSheetsOnly("Detalle 7K´S"); 
	$objReader->setLoadSheetsOnly($hoja); 																																																																																																																				
	$objReader->setReadDataOnly(true);
	$objReader->setReadFilter( new MyReadFilter($columnas,2 ,null) );

	$objPHPExcel = $objReader->load($archivoExcel);
	$objWorksheet = $objPHPExcel->getActiveSheet();

	$i=0;
	$tuplas=array();
	$fecha_consulta=date("Y-m-d H:i:s");
	foreach ($objWorksheet->getRowIterator() as $row) {
	  $cellIterator = $row->getCellIterator();
	  $cellIterator->setIterateOnlyExistingCells(false); // This loops all cells,
	                                                     // even if it is not set.
	                                                     // By default, only cells
	                                                     // that are set will be
	                                                     // iterated.
	  if($i==0){
	  	$i++;
	  	continue;
	  }
	  $j=0;
	  $row=array();
	  foreach ($cellIterator as $cell) {
	    $row[]=preg_replace("/'/", "", $cell->getValue());
	    $j++;

	    if($j==count($columnas)){
	    	break;
	    }
	  }
	  if(count($row)>0){
	  	$fecha_log=date_create(date("Y")."-".(explode(":",$row[2])[1])."-".(($row[3]<10)?"0".$row[3]:$row[3])." ".$row[4]);
	  	$tuplas[]= array(
	  			"ip_man"=>$row[6],
	  			"ip_asr"=>$row[0],
	  			"fecha_consulta"=>$fecha_consulta,
	  			"fecha_log"=>$fecha_log->format("Y-m-d H:i:s.u"),
	  			"tipo_evento"=>$row[7],
	  			"bgp"=>preg_replace("/:/","",$row[5]),
	  			"numero_registro"=>$row[1],
	  			"mes_msj"=>$row[2],
	  			"as"=>$row[8],
	  			"asr"=>$row[9],
	  			"id_stv"=>$row[10],
	  			"pry"=>$row[11],
	  			"id_fact_7k"=>$row[12],
	  			"vlan"=>$row[13],
	  		);
		}
		$i++;
	}
	usort($tuplas,"cmp");
	if(file_exists("LOG_PROCESS.sql"))
	{
		unlink("LOG_PROCESS.sql");
	}
	$fp=fopen("LOG_PROCESS.sql","a");

	for($i=0;$i<count($tuplas);$i++){
		$lineaInsert="\t".$tuplas[$i]["ip_man"]."\t".$tuplas[$i]["ip_asr"]."\t".$tuplas[$i]["fecha_consulta"]."\t".$tuplas[$i]["fecha_log"]."\t".$tuplas[$i]["tipo_evento"]."\t".$tuplas[$i]["bgp"]."\t".$tuplas[$i]["numero_registro"]."\t".$tuplas[$i]["mes_msj"]."\t".$tuplas[$i]["as"]."\t".$tuplas[$i]["asr"]."\t".$tuplas[$i]["id_stv"]."\t".$tuplas[$i]["pry"]."\t".$tuplas[$i]["id_fact_7k"]."\t".$tuplas[$i]["vlan"]."\n";
		fputs($fp,$lineaInsert);
	}
	fclose($fp);

	$query="LOAD DATA LOCAL INFILE '/var/www/html/logsys2/LOG_PROCESS.sql' INTO TABLE log";
	$result =conexion("bgpr",$base,$query);
	if($result[0]['evento']=='error'){
		echo($result[0]['msg']);
	}
}

/*
excelExec($archivoExcel7k,"ASR TRABAJO",['A','B','C','D','E','F','G','H','I','J','K','L','M','N'],'logs_7kt');
echo("Termino logs_7kt\n");
excelExec($archivoExcel7k,"ASR RESPALDO",['A','B','C','D','E','F','G','H','I','J','K','L','M','N'],'logs_7kr');
echo("Termino logs_7kr\n");
*/
excelExec($archivoExcel8k,"ASR TRABAJO",['A','B','C','D','E','F','G','H','I','J','K','L','M','N'],'logs_8kt');
echo("Termino logs_8kt\n");
excelExec($archivoExcel8k,"ASR RESPALDO",['A','B','C','D','E','F','G','H','I','J','K','L','M','N'],'logs_8kr');
echo("Termino logs_8kr\n");
/*
excelExecFact($facturacion,['A','B','C'],'logs');
echo("Termino Facturacion\n");
*/
/*
dispoBasica('7kr');
dispoBasica('7kt');
*/
dispoBasica('8kr');
dispoBasica('8kt');
echo("Termino dispo basica\n");
?>