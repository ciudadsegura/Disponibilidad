<?php
set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
/** PHPExcel_IOFactory */
include 'PHPExcel/IOFactory.php';
$archivoExcel="IPS ASR.xlsx";

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

function sql($tabla)
	{
		$dbname='vlan';
		$server="localhost";
		$user="root";
		$pass="Nextengo";

		$con=mysqli_connect($server,$user,$pass,$dbname);
		if(!$con)
		{
			echo mysqli_error($con);
		}
		$qry="truncate table ".$tabla;
		if(!$query=mysqli_query($con,$qry))
		{
			echo "Error al truncar ".mysqli_error($con)."<br>";
		}
		$qry="LOAD DATA LOCAL INFILE '/var/www/html/Excel/asr.sql' REPLACE INTO TABLE ".$tabla."(ip_asr,proyecto,c2)";
		if(!$query=mysqli_query($con,$qry))
		{
			echo "Error al insertar ".mysqli_error($con)."<br>";
		}
		mysqli_close($con);
	}


function excelExec($archivoExcel,$hoja,$columnas,$tabla){

	$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	//$objReader->setLoadSheetsOnly("Detalle 7K´S"); 
	$objReader->setLoadSheetsOnly($hoja); 
	$objReader->setReadDataOnly(true);
	$objReader->setReadFilter( new MyReadFilter($columnas,2 ,null) );

	$objPHPExcel = $objReader->load($archivoExcel);
	$objWorksheet = $objPHPExcel->getActiveSheet();

	if(file_exists("asr.sql"))
	{
		$fp=fopen("asr.sql","w");
		fputs($fp,"");
		fclose($fp);
	}
	$fp=fopen("asr.sql","a");

	echo '<table>' . "\n";
	$j=0;
	foreach ($objWorksheet->getRowIterator() as $row) {
	  echo '<tr>' . "\n";


	  $cellIterator = $row->getCellIterator();
	  $cellIterator->setIterateOnlyExistingCells(false); // This loops all cells,
	                                                     // even if it is not set.
	                                                     // By default, only cells
	                                                     // that are set will be
	                                                     // iterated.
	  $j=0;
	  foreach ($cellIterator as $cell) {
	    echo '<td>' . $cell->getValue() . '</td>' . "\n";
	    if(trim($cell->getValue())!="")
	    {
	    	if($j>0){fputs($fp,"\t");}
	    	fputs($fp,$cell->getValue());	
	    	$j++;
	    }
	    if($j==3){
	    	fputs($fp,"\n");
	    	break;
	    }
	  }
	  
	  echo '</tr>' . "\n";
	}
	fclose($fp);
	sql($tabla);
}


excelExec($archivoExcel,"Hoja1",['A','D', 'E'],"asr");

////////////////////////////////////////////REPISA 7K///////////////////////////////////////////////////////////////




?>


<body>
</html>