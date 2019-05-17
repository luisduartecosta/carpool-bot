<?php 
	require_once "Connection.php";
	require_once "Carona.php";

    
    class CaronaDAO{

		
		const QUERY_CREATE_CARPOOL = "INSERT INTO public.caroneiros (chat_id, user_id, username, timestamp, route, expiration) VALUES (:chat_id, :user_id, :username, :timestamp, :route::bit(1), :expiration)";
		const QUERY_CREATE_CARPOOL_WITH_DETAILS = "insert into public.caroneiros (chat_id, user_id, username, timestamp, location, route, expiration) VALUES (:chat_id, :user_id, :username, :timestamp, :location, :route::bit(1), :expiration)";

		const QUERY_UPDATE_CARPOOL = "UPDATE public.caroneiros SET timestamp = :timestamp, location = '', expiration = :expiration WHERE chat_id = :chat_id AND user_id = :user_id AND route = :route::bit(1)";
		const QUERY_UPDATE_CARPOOL_WITH_DETAILS = "UPDATE public.caroneiros SET timestamp = :timestamp, location = :location, expiration = :expiration WHERE chat_id = :chat_id AND user_id = :user_id AND route = :route::bit(1)";
        

		const QUERY_SEARCH = "SELECT * FROM public.caroneiros WHERE chat_id = :chat_id AND user_id = :user_id AND route = :route::bit(1) ORDER BY timestamp ASC;";
        
        const QUERY_LIST_CARPOOLS = "SELECT * FROM public.caroneiros WHERE chat_id = :chat_id AND route = :route::bit(1) ORDER BY timestamp ASC;";

		const QUERY_REMOVE_CARPOOL = "DELETE FROM public.caroneiros WHERE chat_id = :chat_id AND user_id = :user_id AND route = :route::bit(1)";

        const QUERY_REMOVE_EXPIRED_CARPOOLS = "DELETE FROM public.caroneiros WHERE expiration < :now";
        
        const QUERY_CREATE_CARPOOL_REQUEST = "INSERT INTO public.requests (chat_id, user_id, username, timestamp, location, route, expiration) VALUES (:chat_id, :user_id, :username, :timestamp, :location, :route::bit(1), :expiration)";
        
        const QUERY_SEARCH_CARPOOL_REQUEST = "SELECT * FROM public.requests WHERE chat_id = :chat_id AND route = :route::bit(1) AND (timestamp >= :minTimestamp OR timestamp <= :maxTimestamp);";

                
        private $db;	
		
        public function __construct(){
            $this->db = new Database();
        }

        
        /*
         * FUNCTION TO LIST CARPOOL
         * WITH ROUTE AS PARAMETER
         */
        
        public function getCarpoolList($chat_id, $route) {
            error_log("getCarpoolList");
            error_log("chat_id: " . $chat_id);
            error_log("route: " . $route);
            
            $this->removeExpiredCarpools();
            
            $this->db->query(CaronaDAO::QUERY_LIST_CARPOOLS);
			$this->db->bind(":chat_id", $chat_id);
            $this->db->bind(":route", $route);
            
            $this->db->execute();
                        
            return $this->createCarpoolList($this->db->resultSet());
        }
        
		
        /*
         * CREATES A NEW CARPOOL ON A SPECIFIC CHAT, OFFERED BY A SINGLE USER
         * LINKED TO HIS USER NAME ON A SPECIFIC TIME EITHER GOING OR RETURNING
         */
        
		public function createCarpool($chat_id, $user_id, $username, $travel_hour, $route) {

			$travel_hour = $this->setTimeString($travel_hour);
            $timestamp = $this->getCarpoolTimestamp($travel_hour);
            
            $expiration = $this->getExpirationTimestamp($travel_hour);
			error_log("createCarpool");
            error_log($travel_hour);
            error_log($expiration);
            
            error_log(QUERY_SEARCH);
			
			$this->db->query(CaronaDAO::QUERY_SEARCH);
			$this->db->bind(":chat_id", $chat_id);
			$this->db->bind(":user_id", $user_id);
			$this->db->bind(":route", $route);

			error_log("-- info --");
            error_log($chat_id);
			error_log($user_id);
            error_log($username);
            error_log($travel_hour);
            error_log($route);
            error_log($expiration);

			$this->db->execute();

			if (count($this->db->resultSet()) == 0) {
				error_log("insterting new carpool going");                
				$this->db->query(CaronaDAO::QUERY_CREATE_CARPOOL);
				$this->db->bind(":chat_id",$chat_id);
                $this->db->bind(":user_id",$user_id);
				$this->db->bind(":username", $username);
				$this->db->bind(":timestamp", $timestamp);
				$this->db->bind(":route", $route);
                $this->db->bind(":expiration", $expiration);

				$this->db->execute();
				error_log("Erro: " . $this->db->getError());

			} else {
				error_log("updating existing carpool going");
				$this->db->query(CaronaDAO::QUERY_UPDATE_CARPOOL);
				$this->db->bind(":chat_id",$chat_id);
                $this->db->bind(":user_id",$user_id);
				$this->db->bind(":timestamp", $timestamp);
				$this->db->bind(":route", $route);
                $this->db->bind(":expiration", $expiration);

				$this->db->execute();
				error_log("Erro: " . $this->db->getError());
			}

            return $this->checkForRequests($chat_id, $route, $timestamp);

		}

        /*
         * CREATES A NEW CARPOOL ON A SPECIFIC CHAT, OFFERED BY A SINGLE USER
         * LINKED TO HIS USER NAME ON A SPECIFIC TIME EITHER GOING OR RETURNING
         * WITH LOCATION AS REFERENCE AND NUMBER OF SPOTS
         */
        
		public function createCarpoolWithDetails($chat_id, $user_id, $username, $travel_hour, $location, $route) {

			error_log("create carpool with details");

			$travel_hour = $this->setTimeString($travel_hour);
            $timestamp = $this->getCarpoolTimestamp($travel_hour);
            
            $expiration = $this->getExpirationTimestamp($travel_hour);
			
			$this->db->query(CaronaDAO::QUERY_SEARCH);
			$this->db->bind(":chat_id",$chat_id);
			$this->db->bind(":user_id",$user_id);
			$this->db->bind(":route", $route);

			$this->db->execute();

			if (count($this->db->resultSet()) == 0) {
				error_log("insterting new carpool with details going");
				$this->db->query(CaronaDAO::QUERY_CREATE_CARPOOL_WITH_DETAILS);
				$this->db->bind(":chat_id",$chat_id);
                $this->db->bind(":user_id",$user_id);
				$this->db->bind(":username", $username);
				$this->db->bind(":timestamp", $timestamp);
				$this->db->bind(":location", strtolower($location));
				$this->db->bind(":route", $route);
                $this->db->bind(":expiration", $expiration);

				$this->db->execute();
				error_log("Erro: " . $this->db->getError());

			} else {
				error_log("updating existing carpool with details going");

				$this->db->query(CaronaDAO::QUERY_UPDATE_CARPOOL_WITH_DETAILS);
				$this->db->bind(":chat_id",$chat_id);
                $this->db->bind(":user_id",$user_id);
				$this->db->bind(":timestamp", $timestamp);
				$this->db->bind(":location", $location);
				$this->db->bind(":route", $route);
                $this->db->bind(":expiration", $expiration);

				$this->db->execute();
				error_log("Erro: " . $this->db->getError());
			}


		}

		public function removeCarpool($chat_id, $user_id, $route) {
            error_log(QUERY_REMOVE_CARPOOL);
			$this->db->query(CaronaDAO::QUERY_REMOVE_CARPOOL);
			$this->db->bind(":chat_id",$chat_id);
			$this->db->bind(":user_id",$user_id);
			$this->db->bind(":route", $route);
			
			$this->db->execute();
			error_log("Erro: " . $this->db->getError());
		}
        
        public function createCarpoolRequest($chat_id, $user_id, $username, $travel_hour, $route, $location) {
            error_log("create carpool request");
            
                        
            $travel_hour = $this->setTimeString($travel_hour);
            
            error_log($travel_hour);
            $timestamp = $this->getCarpoolTimestamp($travel_hour);
            
            $expiration = $this->getExpirationTimestamp($travel_hour);
            			
            $this->db->query(CaronaDAO::QUERY_CREATE_CARPOOL_REQUEST);
            $this->db->bind(":chat_id",$chat_id);
			$this->db->bind(":user_id",$user_id);
            $this->db->bind(":username", $username);
            $this->db->bind(":timestamp", $timestamp);
            $this->db->bind(":location", strtolower($location));
            $this->db->bind(":route", $route);
            $this->db->bind(":expiration", $expiration);

            $this->db->execute();
            error_log("Erro: " . $this->db->getError());
            
        }
        
        
        private function getCarpoolTimestamp($travel_hour) {
            
            error_log("getCarpoolTimestamp");
            error_log($travel_hour);
            
            $diffDay = new DateInterval('PT24H00M');

            $today = date("Y-m-d");

            $timezone = date_default_timezone_get();
            $now = date_create(date("Y-m-d G:i P"));
            $nowTimestamp = $now->getTimestamp();

            $hour = explode(":", $travel_hour)[0];
            $minutes = explode(":", $travel_hour)[1];
            error_log($today);
            error_log($hour);
            error_log($minutes);

            $datetime = date_create($today . " " . $hour . ":" . $minutes, timezone_open('America/Sao_Paulo'));
            $timestamp = $datetime->getTimestamp();
            
            /*
             * CHECKS IF CARPOOL EXPIRATION IS ON SOME SAME
             * DAY OR THE NEXT DAY AND
             * SETS CARPOOL EXPIRATION TIME
             */
            if ($nowTimestamp > $timestamp) {
                $datetime->add($diffDay);
            } 
            
            $timestamp = $datetime->getTimestamp();
            
            return $timestamp;
            
        }

        /*
         * SETS THE CARPOOL EXPIRATION TO 30 MINUTES
         * AFTER ITS TIME
         */
        private function getExpirationTimestamp($timestamp) {
            $delta = 30 * 60;
            return $timestamp + $delta;
        }

		private function setTimeString($travel_hour){
			return $travel_hour .= ":00";
		}
        
        private function timestampToTimeString($timestamp){
			return $travel_hour .= ":00";
		}
        
        /*
         * AUTOMATICALLY DELETES CARPOOLS EXPIRED
         * MORE THAN 30 MINUTES
         */
        private function removeExpiredCarpools() {
            $timezone = date_default_timezone_get();
            $now = date_create(date("Y-m-d G:i P"));
            $nowTimestamp = $now->getTimestamp();
            
            $this->db->query(CaronaDAO::QUERY_REMOVE_EXPIRED_CARPOOLS);
			$this->db->bind(":now", $nowTimestamp);

			$this->db->execute();
            
        }
        
        private function checkForRequests($chat_id, $route, $timestamp) {
            
            $this->db->query(CaronaDAO::QUERY_SEARCH_CARPOOL_REQUEST);
            $this->db->bind(":chat_id", $chat_id);
            $this->db->bind(":route", $route);
            $this->db->bind(":minTimestamp", $this->getMinTimestamp($timestamp));
            $this->db->bind(":maxTimestamp", $this->getMaxTimestamp($timestamp));
            
            $this->db->execute();
            
            return $this->db->resultSet();
            
        }
        
        private function getMinTimestamp($timestamp) {
            $delta = 15 * 60;
            return $timestamp - delta;
        }
        
        private function getMaxTimestamp($timestamp) {
            $delta = 15 * 60;
            return $timestamp + delta;
        }
		
		private function createCarpoolList($resultSet){

            error_log("createCarpoolList");
			$result = array();
			
			foreach ($resultSet as $entrada) {
                error_log("entrada");
				array_push($result, new Carona($entrada));
			}
			
			return $result;
		}
    }

    
