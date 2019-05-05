<?php 

//HEADER RETURN
header("Content-Type: application/json");

//CAPTURA DE DADOS
$search = (isset($_POST["searchinput"])) ? $_POST["searchinput"] : "" ;

//DADOS DE CONEXAO
$dbhost = "localhost";
$dbname = "parserlog";
$dbuser = "root";
$dbpass = "";

//CONEXAO PDO
try {

	//CONEXAO
	$db = new PDO("mysql:host={$dbhost};dbname={$dbname}", 
		$dbuser, 
		$dbpass, 
		array(
			// MANTER CONEXAO PERSISTENTE NO BANCO DE DADOS
			PDO::ATTR_PERSISTENT            => true,
			// MANTER CODIFICACAO PADRAO DURANTE A CONEXAO
			PDO::MYSQL_ATTR_INIT_COMMAND    => "SET NAMES utf8",
			// DEFININDO O MODO DE ERRO PARA LANCAR EXCECOES
			PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION
		)
	);

//POSSIBILIDADE DE ERRO
} catch (Exception $log) {
    
    //JSON DE FALHA NA CONEXAO
	echo(json_encode(array(
		"response"  => false,
		"message"   => "Não foi possível conectar-se ao Banco de Dados.",
		"code"      => $log->getCode()
	)));

	exit();

}

//SQL DE CONSULTA
$selectoperationplayer = $db->prepare("
	SELECT 
		NM_PLAYER, 
		SUM(NR_PLAYER_KILL) AS NR_PLAYER_KILL
	FROM PLAYER
	WHERE NM_PLAYER LIKE :NM_PLAYER
	GROUP BY NM_PLAYER;
;", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

//BIND DO PARAMETRO PESQUISADO
$selectoperationplayer->bindValue(":NM_PLAYER", "%".$search."%");

//EXECUTANDO CONSULTA
if($selectoperationplayer->execute()) {
    
    //CAPTURANDO DADOS RETORNADOS
	$data = $selectoperationplayer->fetchAll(PDO::FETCH_ASSOC);
    
    //RETORNANDO JSON DE RESPOSTA
	echo(json_encode($data));
	
} else {

	//RETORNANDO JSON DE RESPOSTA VAZIA
	echo(json_encode([]));
	
}