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
						$regras = "	Este grupo tem como intuito principal facilitar o deslocamento entre Ilha e Fundão. Não visamos criar um serviço paralelo nem tirar algum lucro com isso.
									Este documento descreve como o grupo costuma funcionar para não ficar muito bagunçado. São conselhos baseados no bom senso e experiência adquirida.

									-Nome e foto: libere a exibição do nome e foto no Telegram. Isso oferece mais segurança para os motoristas e caroneiros. Caso não exiba, existe grande chance de você ser removido por engano ou considerado inativo.

									-Horários: Ao oferecer carona, informe o horário que vai sair do seu destino.

									-Carona para o dia seguinte: espere um horário que não atrapalhe quem está pedindo carona para voltar da faculdade. Sugestão: ofereça após as 19h.

									-Valor: Não é pagamento, ninguém é obrigado a pagar como também ninguém é obrigado a dar carona. É uma ajuda de custos. Chegamos, em comum acordo, em uma contribuição de 3,00 por trajeto. (Já são mais 3 anos de grupo e nunca tivemos maiores problemas com isso).

									-Não seja ganancioso, seu carro não é táxi.

									-Não seja mesquinho, você está indo para a faculdade no conforto e rapidez, colabore com o motorista.

									-Utilize o bot como forma principal de anunciar as caronas.

									-Sempre utilize os padrões propostos pelo comando /help. Eles foram escolhidos de forma a melhorar a exibição das caronas.

									-Evite conversar e fugir do tema do grupo. Este grupo é destinado apenas à carona. Para demais assuntos temos o grupo da zoeira (Para os interessados, mandar inbox para @LuisOctavioCosta)

									-Qualquer dúvida sobre o funcionamento do grupo, sugestão ou reclamação, podem me procurar por inbox (@LuisOctavioCosta).

									Obrigado";

						TelegramConnect::sendMessage($chat_id, $regras);
						break;
					
					case 'help':
						$help = "Utilize este Bot para agendar as caronas. A utilização é super simples e através de comandos:

								/ida [horario] [vagas] [local] --> Este comando serve para definir um horário que você está INDO para o FUNDÃO.
									Ex: /ida 10:00 2 jardim
									(Inclui uma carona de ida às 10:00 com 2 vagas saindo do jardim)

								/ida [horario] --> Este comando serve para definir um horário que você está INDO para o FUNDÃO. Nessa opção, não é necessário definir vagas e local.
									Ex: /ida 10:00
									(Inclui uma carona de ida às 10:00)

								Caso não seja colocado o parâmetro do horário (Ex: /ida) o bot irá apresentar a lista com as caronas registradas para o trajeto.

								/volta [horario] [vagas] [local] --> Este comando serve para definir um horário que você está VOLTANDO para o SEU BAIRRO. 
									Ex: /volta 15:00 3 jardim 
									(Inclui uma carona de volta às 15:00 com 3 vagas para o jardim)

								/volta [horario] --> Este comando serve para definir um horário que você está VOLTANDO para o SEU BAIRRO. Nessa opção, não é necessário definir vagas e local.
									Ex: /volta 15:00
									(Inclui uma carona de volta às 15:00)
								
								Caso não seja colocado o parâmetro do horário (Ex: /volta) o bot irá apresentar a lista com as caronas registradas para o trajeto.

								OBS --> Para o local utilize sempre letras minúsculas e para mais de um local siga o padrão : local01/local02 
									Ex: cacuia/cocotá/tauá/bancários

								/remover [ida|volta] --> Comando utilizado para remover a carona da lista. SEMPRE REMOVA a carona depois dela ter sido realizada. O sistema não faz isso automaticamente. 
									Ex: /remover ida

								/vagas [ida|volta] [vagas] --> Este comando serve para atualizar o número de vagas de uma carona
									Ex: /vagas ida 2 
									(Altera o número de vagas da ida para 2)";
						
						TelegramConnect::sendMessage($chat_id, $help);
						break;
						
					case 'teste':
						error_log("teste");
						$texto = "Versão BETA 1.4 - ChatId: $chat_id";

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
						$texto = "@Janoti oferece carona entre 08:00 e 12:00 com 6 vagas saindo de Ilha Toda";

						TelegramConnect::sendMessage($chat_id, $texto);
						break;

					/*Comandos de viagem*/
					case 'ida':
						if (count($args) == 1) {

							$resultado = $dao->getListaIda($chat_id);

							$source = Config::getBotConfig("source");
							$texto = "<b>Ida para " . $source . "</b>\n";
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
							$texto = "<b>Volta de " . $source . "</b>\n";
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
