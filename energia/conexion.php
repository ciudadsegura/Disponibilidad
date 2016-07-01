<?php	
	function conexion($serv,$dbname,$query){


		$conexiones= array(
	"bgpt"=>array('server' => "192.168.125.110",'user' => "rec1",'pass' => "Nextengo"),
	"bgpr"=>array('server' => "192.168.125.105",'user' => "usuario",'pass' => "Nextengo"),
	"logs"=>array('server' => "192.168.125.115",'user' => "rec3",'pass' => "Nextengo")
	);

		$servidor = $conexiones[$serv];

			$con=new mysqli($servidor['server'],$servidor['user'],$servidor['pass'],$dbname);

			if($con->connect_errno){
				$resultados[]=array('evento' =>"error",'msg' =>" >>>> ".$con->connect_error."\n");
				return $resultados;
			}

			$result= $con->query($query);

			$resultados= array();

				if (isset($result->num_rows)){
					$resultados[]=array('evento' =>"correcto",'msg' =>"Consulta ejecutada correctamente\n",'numRegs' =>$result->num_rows);
					$resultados=array_merge($resultados,$result->fetch_all(MYSQLI_ASSOC));
					$result->free_result();
				}else{
				
					if ($result == 1){
						$resultados[]=array('evento' =>"correcto",'msg' =>"Consulta ejecutada correctamente\n",'numRegs' =>$con->affected_rows,'id' =>$con->insert_id);
					}
					else{
						$resultados[]=array('evento' =>"error",'msg' =>"$query >>>> ".$con->error."\n");
					}
				}
			$con->close();
			$con=NULL;
			$result=NULL;
			return $resultados;
	}
/*print_r($result);
print_r($result[1]);
*/
?>