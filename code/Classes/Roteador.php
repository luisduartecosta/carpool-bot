<?php
	require_once "Config.php";
	require_once "TelegramConnect.php";
	require_once "CaronaDAO.php";
	require_once "Carona.php";

	class Roteador{

		/*Espera o objeto 'message' já como array*/
		private static function processData($data){
			$processedData = array();

			/*TODO inicializar objeto telegramConnect com dados da mensagem*/
			$processedData['username'] = $data["message"]["from"]["username"];
			$processedData['chatId'] = $data["message"]["chat"]["id"];
			$processedData['userId'] = $data["message"]["from"]["id"];

			error_log( print_r( $processedData, true ) );

			return $processedData;
		}

		private static function processCommand($stringComando, &$args){
			/* Trata uma string que começa com '/', seguido por no maximo 32 numeros, letras ou '_', seguido ou não de '@nomeDoBot */
			$regexComando = '~^/(?P<comando>[\d\w_]{1,32})(?:@'. Config::getBotConfig('botName') .')?~';
			$command = NULL;
			$args = NULL;

			if(preg_match($regexComando, $stringComando, $match)){
				$command = $match['comando'];
				$stringComando = str_replace($match[0], "", $stringComando);
				$args = explode(" ", $stringComando);
			}

			error_log( print_r( $command, true ) );
			error_log( print_r( $args, true ) );
			error_log( strlen($args[1]) );
			return $command;
		}

		public static function direcionar($request){
			$args = array();
			$command = self::processCommand($request['message']['text'], $args);
			$dados = self::processData($request);

			$chat_id = $dados["chatId"];
			$user_id = $dados["userId"];
			$username = $dados['username'];
			
			/*Dividir cada comando em seu controlador*/
			if($username){
				$dao = new CaronaDAO();

				switch (strtolower($command)) {
					/*comandos padrão*/
					case 'regras':
						$regras = "Este grupo tem como intuito principal facilitar o deslocamento entre Ilha e Fundão. Não visamos criar um serviço paralelo nem tirar algum lucro com isso.
						Este documento descreve como o grupo costuma funcionar para não ficar muito bagunçado. São conselhos baseados no bom senso e experiência adquirida.
			   
						- Nome e foto: libere a exibição do nome e foto no Telegram. Isso oferece mais segurança para os motoristas e caroneiros. Caso não exiba, existe grande chance de você ser removido por engano ou considerado inativo.
			   
						- Oferta: Ao oferecer carona, informe o horário que vai sair do seu destino e local de partida.
			   
						- Carona para o dia seguinte: espere um horário que não atrapalhe quem está pedindo carona para voltar da faculdade. Ofereça após as 19h.
			   
						- Valor: Não é pagamento, ninguém é obrigado a pagar como também ninguém é obrigado a dar carona. É uma ajuda de custos. Chegamos, em comum acordo, em uma contribuição de 3,50 por trajeto. (Já são mais 6 anos de grupo e nunca tivemos maiores problemas com isso).
			   
						- Não seja ganancioso, seu carro não é táxi.
			   
						- Não seja mesquinho, você está indo para a faculdade no conforto e rapidez, colabore com o motorista.
			   
						- Utilize o bot como forma principal de anunciar as caronas.
			   
						- Sempre utilize os padrões propostos pelo comando /help. Eles foram escolhidos de forma a melhorar a exibição das caronas.
			   
						- Evite conversar e fugir do tema do grupo. Este grupo é destinado apenas à carona.
			   
						- Qualquer dúvida sobre o funcionamento do grupo, sugestão ou reclamação, podem procurar os admins por inbox (@Igor_Linhares, @Jess1ca_Lima ou @Janoti).
			   
						Obrigado";

						TelegramConnect::sendMessage($chat_id, $regras);
						break;
					
					case 'help':
						$help = "Utilize este Bot para agendar as caronas. A utilização é super simples e através de comandos:

						Listar caronas disponíveis:
							/ida 	--> Lista as caronas de ida para o Fundão.
							/volta 	--> Lista de caronas de volta para a Ilha.

						Oferecer caronas:
							/[ida|volta] [horario] --> Cria uma carona de ida ou volta informando o horário.
								Ex: /ida 10:00   (Oferece uma carona de ida com início as 10:00)
									/volta 20:00 (Oferece uma carona de volta com início as 20:00)

							/[ida|volta] [horario] [local] -->> Cria uma carona de ida ou volta informando o horário e local.
								Ex: /ida 7:20 jardimGuanabara  (Oferece uma carona de ida com início as 7:20 saindo do Jardim Guanabara)
									/volta 17:00 cacuia (Oferece uma carona de volta com início as 17:00 até o Cacuia)

							OBS --> Respeitar o padrão de hora XX:XX ou XX para horas exatas.
								--> No campo [local] não é aceito o caracter 'espaço'. Para mais de um local, utilizar '/' (Ex: cocotá/tauá/bancários).

						Remover caronas:
							/remover [ida|volta] --> Comando utilizado para remover a carona da lista. 
								Ex: /remover ida

							OBS --> O bot exclui automaticamente todas caronas após 30min do seu início.";

								
						TelegramConnect::sendMessage($chat_id, $help);
						break;
						
					case 'teste':
						error_log("teste");
						$texto = "Versão 2.1 - ChatId: $chat_id";

						TelegramConnect::sendMessage($chat_id, $texto);
						break;

					case 'stop':
						$texto = "GALERA, OLHA A ZOEIRA...";

						TelegramConnect::sendMessage($chat_id, $texto);
						break;

					case 'luiza':
						$texto = "Luiiiis, me espera! Só vou atrasar uns minutinhos!";

						TelegramConnect::sendMessage($chat_id, $texto);
						break;

					case 'janoti':
						$texto = "Janoti oferece carona entre 08:00 e 12:00 com 6 vagas saindo de Ilha Toda";

						TelegramConnect::sendMessage($chat_id, $texto);
						break;

					/*Comandos de viagem*/
					case 'ida':
						if (count($args) == 1) {

							$resultado = $dao->getListaIda($chat_id);

							$source = Config::getBotConfig("source");
							$texto = "<b>Ida para " . $source . "</b>\n(Combinar as caronas no privado)";
							foreach ($resultado as $carona){
								$texto .= (string)$carona . "\n";
							}

							TelegramConnect::sendMessage($chat_id, $texto);
						} elseif (count($args) == 2) {

							$horarioRaw = $args[1];
							$horarioRegex = '/^(?P<hora>[01]?\d|2[0-3])(?::(?P<minuto>[0-5]\d))?$/';

							$horarioValido = preg_match($horarioRegex, $horarioRaw, $resultado);

							if ($horarioValido){
								$hora = $resultado['hora'];
								$minuto = isset($resultado['minuto']) ? $resultado['minuto'] : "00";

								$travel_hour = $hora . ":" . $minuto;
				
								$dao->createCarpool($chat_id, $user_id, $username, $travel_hour, '0');

								TelegramConnect::sendMessage($chat_id, "@" . $username . " oferece carona de ida às " . $travel_hour);
							} else{
								TelegramConnect::sendMessage($chat_id, "Horário inválido.");
							}

						} elseif (count($args) == 3) {

							$horarioRaw = $args[1];
							$horarioRegex = '/^(?P<hora>[01]?\d|2[0-3])(?::(?P<minuto>[0-5]\d))?$/';

							$horarioValido = preg_match($horarioRegex, $horarioRaw, $resultado);

							// $spots = $args[2];
							$location = $args[2];

							if ($horarioValido){
								$hora = $resultado['hora'];
								$minuto = isset($resultado['minuto']) ? $resultado['minuto'] : "00";

								$travel_hour = $hora . ":" . $minuto;
				
								$dao->createCarpoolWithDetails($chat_id, $user_id, $username, $travel_hour, $location, '0');

								TelegramConnect::sendMessage($chat_id, "@" . $username . " oferece carona de ida às " . $travel_hour . " saindo de " . $location);
							} else{
								TelegramConnect::sendMessage($chat_id, "Horário inválido.");
							}
						} else {
							TelegramConnect::sendMessage($chat_id, "Uso: /ida [horario] [local] \nEx: /ida 10:00 2 jardim");
						}
						break;

					case 'volta':
						if (count($args) == 1) {
							$resultado = $dao->getListaVolta($chat_id);

							$source = Config::getBotConfig("source");
							$texto = "<b>Volta de " . $source . "</b>\n(Combinar as caronas no privado)";
							foreach ($resultado as $carona){
								$texto .= (string)$carona . "\n";
							}

							TelegramConnect::sendMessage($chat_id, $texto);

						} elseif (count($args) == 2) {

							$horarioRaw = $args[1];
							$horarioRegex = '/^(?P<hora>[01]?\d|2[0-3])(?::(?P<minuto>[0-5]\d))?$/';

							$horarioValido = preg_match($horarioRegex, $horarioRaw, $resultado);

							if ($horarioValido){
								$hora = $resultado['hora'];
								$minuto = isset($resultado['minuto']) ? $resultado['minuto'] : "00";

								$travel_hour = $hora . ":" . $minuto;
				
								$dao->createCarpool($chat_id, $user_id, $username, $travel_hour, '1');

								TelegramConnect::sendMessage($chat_id, "@" . $username . " oferece carona de volta às " . $travel_hour);
							} else{
								TelegramConnect::sendMessage($chat_id, "Horário inválido.");
							}

						} elseif (count($args) == 3) {

							$horarioRaw = $args[1];

							$horarioRegex = '/^(?P<hora>[0-2]?\d)(:(?P<minuto>[0-5]\d))?$/';

							$horarioValido = preg_match($horarioRegex, $horarioRaw, $resultado);

							// $spots = $args[2];
							$location = $args[2];

							if ($horarioValido){
								$hora = $resultado['hora'];
								$minuto = isset($resultado['minuto']) ? $resultado['minuto'] : "00";

								$travel_hour = $hora . ":" . $minuto;

								$dao->createCarpoolWithDetails($chat_id, $user_id, $username, $travel_hour, $location, '1');

								TelegramConnect::sendMessage($chat_id, "@" . $username . " oferece carona de volta às " . $travel_hour . " indo até " . $location);

							}else{
								TelegramConnect::sendMessage($chat_id, "Horário inválido.");
							}
						} else {
							TelegramConnect::sendMessage($chat_id, "Uso: /volta [horario] [local] \nEx: /volta 15:00 2 jardim");
						}
						break;

					/* case 'vagas':
						if (count($args) == 3) {
							$spots = $args[2];
							if($args[1] == 'ida') {
								$dao->updateSpots($chat_id, $user_id, $spots, '0');
								TelegramConnect::sendMessage($chat_id, "@".$username." atualizou o número de vagas de ida para " . $spots);
							} elseif ($args[1] == 'volta') {
								$dao->updateSpots($chat_id, $user_id, $spots, '1');
								TelegramConnect::sendMessage($chat_id, "@".$username." atualizou o número de vagas de volta para " . $spots);
							} else {
								TelegramConnect::sendMessage($chat_id, "Formato: /vagas [ida|volta] [vagas]\nEx: /volta ida 2");
							}
						} else {
							TelegramConnect::sendMessage($chat_id, "Formato: /vagas [ida|volta] [vagas]\nEx: /volta ida 2");
						}
						break; */

					case 'remover':
						if (count($args) == 2) {
							if($args[1] == 'ida') {
								$dao->removeCarpool($chat_id, $user_id, '0');
								TelegramConnect::sendMessage($chat_id, "@".$username." removeu sua ida");
							} elseif ($args[1] == 'volta') {
								$dao->removeCarpool($chat_id, $user_id, '1');
								TelegramConnect::sendMessage($chat_id, "@".$username." removeu sua volta");
							} else {
								TelegramConnect::sendMessage($chat_id, "Formato: /remover [ida|volta]");
							}
						} else {
							TelegramConnect::sendMessage($chat_id, "Formato: /remover [ida|volta]");
						}

						break;
				}
			} else {
				TelegramConnect::sendMessage($chat_id, "Registre seu username nas configurações do Telegram para utilizar o Bot.");
			}
		}
	}
