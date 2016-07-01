<?php
set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
/** PHPExcel_IOFactory */
set_time_limit(0);
include 'PHPExcel/IOFactory.php';
include 'conexion.php';
$archivoExcel7k="LOGS ASR 7K_MAYO 2016.xlsx";
$archivoExcel8k="LOGS ASR 8K_MAYO 2016.xlsx";

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
	if(strcmp($a[14],$b[14])==0){
		return strcmp($a[15],$b[15]);
	}
	return strcmp($a[14],$b[14]);
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

	  	$tuplas[]= array($row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10]
	  		,$row[11],$row[12],$row[13],$row[6],$fecha_log->format("Y-m-d H:i:s"));
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
		/*
		if(($i%3000)==0){
			echo(date("Y/m/d H:i:s")."\n");

			echo "Insertando en Mysql>>".($i/3000)."\n";

			$query="LOAD DATA LOCAL INFILE '/var/www/html/logs_sys/LOG_PROCESS.sql' INTO TABLE log";
			$result =conexion("bgpr",$base,$query);
			if($result[0]['evento']=='error'){
				echo($result[0]['msg']);
			}
			echo "Concluyó inserción de LOGS\n";

			fclose($fp);
			echo "Borrando archivo LOGS\n";
			$fp=fopen("LOG_PROCESS.sql","w");
			fputs($fp,"");
			fclose($fp);
			echo "Abriendo archivo LOGS\n";
			$fp=fopen("LOG_PROCESS.sql","a");
		}
		*/
		$lineaInsert=$tuplas[$i][0]."\t".$tuplas[$i][1]."\t".$tuplas[$i][2]."\t".$tuplas[$i][3]."\t".$tuplas[$i][4]."\t".$tuplas[$i][5]."\t".$tuplas[$i][6]."\t".$tuplas[$i][7]."\t".$tuplas[$i][8]."\t".$tuplas[$i][9]."\t".$tuplas[$i][10]."\t".$tuplas[$i][11]."\t".$tuplas[$i][12]."\t".$tuplas[$i][13]."\n";
		fputs($fp,$lineaInsert);
	}
	fclose($fp);
/*
	$query="LOAD DATA LOCAL INFILE '/var/www/html/logs_sys/LOG_PROCESS.sql' INTO TABLE log";
	$result =conexion("bgpr",$base,$query);
	if($result[0]['evento']=='error'){
		echo($result[0]['msg']);
	}
*/
}
excelExec($archivoExcel8k,"ASR TRABAJO",['A','B','C','D','E','F','G','H','I','J','K','L','M','N'],'logs_8kt');
echo("Termino logs_8kt\n");


?>