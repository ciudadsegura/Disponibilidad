<?php	
	function conexion($serv,$dbname,$query){

			$conexiones= array(
			"bgpt"=>array('server' => "192.168.125.110",'user' => "root",'pass' => "Nextengo"),
			"bgpr"=>array('server' => "localhost",'user' => "usuario",'pass' => "Nextengo"),
			"logs"=>array('server' => "192.168.125.184",'user' => "rec3",'pass' => "Nextengo")
			);

			$servidor=$conexiones["$serv"];
			$resultados= null;
			$con=null;

			//$con=new mysqli($servidor['server'],$servidor['user'],$servidor['pass'],$dbname);
			try{
				$con=new PDO("mysql:host=".$servidor['server'].";dbname=$dbname",$servidor['user'],$servidor['pass']);
				$con->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			}catch(PDOException $e){
				$resultados[]=array('evento' =>"error",'msg' =>" >>>> ".$e->getMessage()."\n");
				return $resultados;
			}

			try{
				$result= $con->prepare($query);
				$correcto=$result->execute();
			}catch(Exception $e){
				$resultados[]=array('evento' =>"error",'msg' =>" >>>> ".$e->getMessage()."\n");
				return $resultados;
			}

			if($correcto){
				try{
					$tuplas=$result->fetchAll();
					$resultados[]=array('evento' =>"correcto",'msg' =>"Consulta ejecutada correctamente\n",'numRegs' =>$result->rowCount());
					$resultados=array_merge($resultados,$tuplas);

				}catch(Exception $e){
						$resultados[]=array('evento' =>"correcto",'msg' =>"Consulta ejecutada correctamente\n",'numRegs' =>$result->rowCount(),'id' =>$con->lastInsertId());
				}

			}else{
				$resultados[]=array('evento' =>"error",'msg' =>"$query >>>> ".$con->errorInfo()."\n");
			}
			return ($resultados);
	}
?>