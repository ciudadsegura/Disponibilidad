<?php
set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
/** PHPExcel_IOFactory */
set_time_limit(0);
include 'PHPExcel/IOFactory.php';
include 'conexion.php';
$archivo="1234a.xlsx";

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
function excelExec($archivoExcel,$hoja,$columnas){
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
	if(file_exists("archivo_csv.csv"))
		{
			unlink("archivo_csv.csv");
		}
	$fp=fopen("archivo_csv.csv","a");

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
		$lineaInsert="";
	  	foreach ($row as $key => $value) {
	  		if($lineaInsert!=""){
	  			$lineaInsert.=", ";
	  		}
	  		$lineaInsert.=$value;
	  	}

	  	$lineaInsert.="\n";
	  	fputs($fp,$lineaInsert);
	  }
  }
  fclose($fp);
}

excelExec($archivo,"DATABASE FOR CONTENT",['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q']);
echo("Termino archivo\n");
?>