<?php 

//INICIANDO LEITURA DO LOG
print("## LEITURA DO ARQUIVO DE LOG \n");

//LEITURA DO ARQUIVO DE LOG
$log 	= htmlentities(file_get_contents("./log/games.log", FILE_BINARY));
$lines 	= explode("\n", $log);

//ACOES EM QUE FORAM SOLICITADO ANALISE
$keywords = array(
	"InitGame", 
	"ClientUserinfoChanged", 
	"Kill"
);

//ACOES CONSIDERADAS NAO RELAVENTES NESTE MOMENTO
$actionsnotrelavations = array(
	"Exit", 
	"ClientConnect", 
	"ClientBegin", 
	"ShutdownGame", 
	"Item",
	"ClientDisconnect",
	"score", 
	"say"
);

//ARRAY DOS JOGOS REALIZADOS
$games 	= array();

//VARIAVEIS DE CONTROLE
$ngame 	= 0;

//INICIANDO PARSER DO LOG
print("## INICIANDO PARSER DO LOG \n");

foreach($lines as $line){
	
	//RETIRANDO TIME
	$line 	= trim(substr($line, 6), " ");
	
	//ISOLANDO ACOES
	$word 	= explode(":", $line);
	
	//IGNORANDO ACOES NAO RELEVANTES NO MOMENTO
	if(in_array($word[0], $actionsnotrelavations)){
		continue;
	}
	
	//VERIFICANDO ACOES SOLICITADAS
	if(in_array($word[0], $keywords)){
		
		//REGISTRANDO UM JOGO
		if($word[0] === "InitGame"){
			
			unset($word[1]);
            
            //CONTROLANDO O NUMERO DO JOGO
            $ngame += 1;
            
            //CRIANDO ESTRUTURA DOS DADOS DO JOGO
			$games[$ngame] = array(
				"total_kills" 		=> 0,
				"players" 			=> array(),
				"kills" 			=> array(),
				"kills_by_means" 	=> array(
					"MOD_UNKNOWN" 			=> 0,
					"MOD_SHOTGUN" 			=> 0,
					"MOD_GAUNTLET" 			=> 0,
					"MOD_MACHINEGUN" 		=> 0,
					"MOD_GRENADE" 			=> 0,
					"MOD_GRENADE_SPLASH" 	=> 0,
					"MOD_ROCKET" 			=> 0,
					"MOD_ROCKET_SPLASH" 	=> 0,
					"MOD_PLASMA" 			=> 0,
					"MOD_PLASMA_SPLASH" 	=> 0,
					"MOD_RAILGUN" 			=> 0,
					"MOD_LIGHTNING" 		=> 0,
					"MOD_BFG" 				=> 0,
					"MOD_BFG_SPLASH" 		=> 0,
					"MOD_WATER" 			=> 0,
					"MOD_SLIME" 			=> 0,
					"MOD_LAVA" 				=> 0,
					"MOD_CRUSH" 			=> 0,
					"MOD_TELEFRAG" 			=> 0,
					"MOD_FALLING" 			=> 0,
					"MOD_SUICIDE" 			=> 0,
					"MOD_TARGET_LASER" 		=> 0,
					"MOD_TRIGGER_HURT" 		=> 0,
					"MOD_NAIL" 				=> 0,
					"MOD_CHAINGUN" 			=> 0,
					"MOD_PROXIMITY_MINE" 	=> 0,
					"MOD_KAMIKAZE" 			=> 0,
					"MOD_JUICED" 			=> 0,
					"MOD_GRAPPLE" 			=> 0
				)
			);
			
		}
		
		//ADICIONANDO OS JOGADORES DO JOGO
		if($word[0] === "ClientUserinfoChanged"){
            
            //CAPTURANDO JOGADOR CONECTADO AO JOGO
			$word[1] = explode("\\", $word[1]);
			$word[1] = $word[1][1];
            
            //ADICIONANDO JOGADORES AO CONSOLIDADO DO JOGO
			if(!in_array($word[1], $games[$ngame]["players"])){

				//ADICIONAR JOGADOR
				array_push($games[$ngame]["players"], $word[1]);

				//INICIO DA CONTAGEM DE MORTE DO JOGO
				$games[$ngame]["kills"][$word[1]] = 0;

			}
			
		}
		
		//REGISTRAR MORTES
		if($word[0] === "Kill"){
            
            //CAPTURANDO TRECHO REFERENTE AS INFORMACOES DAS MORTES
			$word[1] = trim($word[2], " ");
            
            //DESCARTANDO INRELEVANCIA
			unset($word[2]);
			
			//REGISTAR MORTE
			$games[$ngame]["total_kills"] += 1;
            
            //SEGREGANDO INFORMACAO DA MORTE PARA INTERPRETACAO
			$akill = preg_split("/killed|by/", $word[1], 3);
			$akill = preg_replace("/^\s+/", "", $akill);
			
			//REGISTRANDO MORTES
			if(strcmp(trim($akill[0], " "), htmlentities("<world>")) === 0){
				//RETIRANDO UM MORTE DA CONTA POIS O JOGADOR MORREU POR WORLD
				$games[$ngame]["kills"][trim($akill[1], " ")]--;
			} else {
				//REGISTRANDO QUE O JOGADOR MATOU MAIS UM
				$games[$ngame]["kills"][trim($akill[0], " ")]++;
			}
			
			//REGISTRANDO CAUSAS DAS MORTES
			$games[$ngame]["kills_by_means"][trim($akill[2], " ")]++;
		
		}

	}
	
}

//PARSER DO LOG
print("## PARSER DO LOG \n");

//OUTPUT DOS DADOS NO FORMATO SOLICITADO
echo json_encode($games, JSON_PRETTY_PRINT);

//INICIANDO REGISTRO NO BANCO DE DADOS
print("\n## INICIANDO REGISTRO NO BANCO DE DADOS \n");

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
    
    //CONEXAO REALIZADA COM SUCESSO
    print("## CONEXAO REALIZADA COM SUCESSO \n");

//POSSIBILIDADE DE ERRO
} catch (Exception $log) {

    //ERRO DURANTE A CONEXAO
    print("## ERRO DURANTE A CONEXAO \n");
    
    //OUTPUT DO LOG DA TENTATIVA DE CONEXAO
	echo json_encode(array(
		"response"  => false,
		"message"   => "Não foi possível conectar-se ao Banco de Dados.",
		"code"      => $log->getCode()
	), JSON_PRETTY_PRINT);

	exit();

}

//LIMPANDO O BANCO DE DADOS COM LOGS ANTERIORES
$deleteOperationGame 		= $db->prepare("DELETE FROM GAME;", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
$deleteOperationKillType 	= $db->prepare("DELETE FROM KILL_TYPE;", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
$deleteOperationPlayer 		= $db->prepare("DELETE FROM PLAYER;", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

//DELETANDO DADOS ANTERIORES
print("## DELETANDO DADOS ANTERIORES \n");

//EXECUTANDO LIMPEZA DO BANCO DE DADOS
if($deleteOperationKillType->execute()){
	if($deleteOperationPlayer->execute()){
		if($deleteOperationGame->execute()){

            //INSERIDO DADOS DE LOG NO BANCO DE DADOS
            print("## INSERIDO DADOS DE LOG NO BANCO DE DADOS \n");

            //INTERAR DADOS
			foreach($games as $key => $game){
			
				//INSERT DE DADOS DO GAMER
				$InsertOperationGame = $db->prepare("INSERT INTO GAME (CD_GAME, NR_TOTAL_KILL) VALUES (:CD_GAME, :NR_TOTAL_KILL);", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
				
				//BIND DATA DO GAMER
				$InsertOperationGame->bindValue(":CD_GAME", $key);
				$InsertOperationGame->bindValue(":NR_TOTAL_KILL", $game["total_kills"]);

                //INSERIR GAMER
				if($InsertOperationGame->execute()){
                    
                    //INTERAR JOGADORES E AS MORTES
					foreach($game["kills"] as $player => $kills){

						//INSERT DE DADOS DOS JOGADORES E DAS MORTES
						$InsertOperationPlayer = $db->prepare(
							"INSERT INTO PLAYER (CD_GAME, NM_PLAYER, NR_PLAYER_KILL) VALUES (:CD_GAME, :NM_PLAYER, :NR_PLAYER_KILL);", 
							array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
						);

						//BIND DATA DOS JOGADORES E DAS MORTES
						$InsertOperationPlayer->bindValue(":CD_GAME", $key);	
						$InsertOperationPlayer->bindValue(":NM_PLAYER", $player);
						$InsertOperationPlayer->bindValue(":NR_PLAYER_KILL", $kills);					

                        //INSERIR DADOS DOS JOGADORES E DAS MORTES
						$InsertOperationPlayer->execute();

					}
                    
                    //INTERAR CAUSAS DAS MORTES DO GAMER EM QUESTAO
					foreach($game["kills_by_means"] as $type => $count){
					
						//INSERT DE DADOS DAS MORTES
						$InsertOperationKillType = $db->prepare(
							"INSERT INTO KILL_TYPE (CD_GAME, NM_KILL_TYPE, NR_KILL_TYPE_COUNT) VALUES (:CD_GAME, :NM_KILL_TYPE, :NR_KILL_TYPE_COUNT);", 
							array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
						);
					
						//BIND DATA DAS MORTES
						$InsertOperationKillType->bindValue(":CD_GAME", $key);
						$InsertOperationKillType->bindValue(":NM_KILL_TYPE", $type);
						$InsertOperationKillType->bindValue(":NR_KILL_TYPE_COUNT", $count);

                        //INSERIR DADOS DAS MORTES
						$InsertOperationKillType->execute();

					}
					
				}
			
            }
            
            //PARSER FINALIZADO
            print("## PARSER FINALIZADO \n");
			
		}
	}
}
