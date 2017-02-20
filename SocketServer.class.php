<?php
	/*!	@class		SocketServer
		@author		Navarr Barnier
		@abstract 	A Framework for creating a multi-client server using the PHP language.
	 */
	class SocketServer
	{
		/*!	@var		config
			@abstract	Array - an array of configuration information used by the server.
		 */
		protected $config;

		/*!	@var		hooks
			@abstract	Array - a dictionary of hooks and the callbacks attached to them.
		 */
		protected $hooks;

		/*!	@var		master_socket
			@abstract	resource - The master socket used by the server.
		 */
		protected $master_socket;
		
		/*!	@var		max_clients
			@abstract	unsigned int - The maximum number of clients allowed to connect.
		 */
		public $max_clients = 10;

		/*!	@var		max_read
			@abstract	unsigned int - The maximum number of bytes to read from a socket at a single time.
		 */
		public $max_read = 1024;

		/*!	@var		clients
			@abstract	Array - an array of connected clients.
		 */
		public $clients;

		/*!	@function	__construct
			@abstract	Creates the socket and starts listening to it.
			@param		string	- IP Address to bind to, NULL for default.
			@param		int	- Port to bind to
			@result		void
		 */
		public function __construct($bind_ip,$port)
		{
			set_time_limit(0);
			$this->hooks = array();

			$this->config["ip"] = $bind_ip;
			$this->config["port"] = $port;

			$this->master_socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_bind($this->master_socket,$this->config["ip"],$this->config["port"]) or die("Issue Binding");
			socket_getsockname($this->master_socket,$bind_ip,$port);
			socket_listen($this->master_socket);
			SocketServer::debug("Listenting for connections on {$bind_ip}:{$port}");
		}

		/*!	@function	hook
			@abstract	Adds a function to be called whenever a certain action happens.  Can be extended in your implementation.
			@param		string	- Command
			@param		callback- Function to Call.
			@see		unhook
			@see		trigger_hooks
			@result		void
		 */
		public function hook($command,$function)
		{
			$command = strtoupper($command);
			if(!isset($this->hooks[$command])) { $this->hooks[$command] = array(); }
			$k = array_search($function,$this->hooks[$command]);
			if($k === FALSE)
			{
				$this->hooks[$command][] = $function;
			}
		}

		/*!	@function	unhook
			@abstract	Deletes a function from the call list for a certain action.  Can be extended in your implementation.
			@param		string	- Command
			@param		callback- Function to Delete from Call List
			@see		hook
			@see		trigger_hooks
			@result		void
		 */
		public function unhook($command = NULL,$function)
		{
			$command = strtoupper($command);
			if($command !== NULL)
			{
				$k = array_search($function,$this->hooks[$command]);
				if($k !== FALSE)
				{
					unset($this->hooks[$command][$k]);
				}
			} else {
				$k = array_search($this->user_funcs,$function);
				if($k !== FALSE)
				{
					unset($this->user_funcs[$k]);
				}
			}
		}

		/*!	@function	loop_once
			@abstract	Runs the class's actions once.
			@discussion	Should only be used if you want to run additional checks during server operation.  Otherwise, use infinite_loop()
			@param		void
			@see		infinite_loop
			@result 	bool	- True
		*/
		public function loop_once()
		{
			// Setup Clients Listen Socket For Reading
			$read[0] = $this->master_socket;
			for($i = 0; $i < $this->max_clients; $i++)
			{
				if(isset($this->clients[$i]))
				{
					$read[$i + 1] = $this->clients[$i]->socket;
				}
			}
            $write = NULL;
            $except = NULL;
            $tv_sec = 5;
			// Set up a blocking call to socket_select
			if(socket_select($read, $write, $except, $tv_sec) < 1)
			{
			//	SocketServer::debug("Problem blocking socket_select?");
				return true;
			}

			// Handle new Connections
			if(in_array($this->master_socket, $read))
			{
				for($i = 0; $i < $this->max_clients; $i++)
				{
					if(empty($this->clients[$i]))
					{
						$temp_sock = $this->master_socket;
						$this->clients[$i] = new SocketServerClient($this->master_socket,$i);
						$this->trigger_hooks("CONNECT",$this->clients[$i],"");
						break;
					}
					elseif($i == ($this->max_clients-1))
					{
						SocketServer::debug("Too many clients... :( ");
					}
				}

			}

			// Handle Input
			for($i = 0; $i < $this->max_clients; $i++) // for each client
			{
				if(isset($this->clients[$i]))
				{
					if(in_array($this->clients[$i]->socket, $read))
					{
						$input = socket_read($this->clients[$i]->socket, $this->max_read);
						if($input == null)
						{
							$this->disconnect($i);
						}
						else
						{
							SocketServer::debug("{$i}@{$this->clients[$i]->ip} --> {$input}");
							$this->trigger_hooks("INPUT",$this->clients[$i],$input);
						}
					}
				}
			}
			return true;
		}

		/*!	@function	disconnect
			@abstract	Disconnects a client from the server.
			@param		int	- Index of the client to disconnect.
			@param		string	- Message to send to the hooks
			@result		void
		*/
		public function disconnect($client_index,$message = "")
		{
			$i = $client_index;
			SocketServer::debug("Client {$i} from {$this->clients[$i]->ip} Disconnecting");
			$this->trigger_hooks("DISCONNECT",$this->clients[$i],$message);
			$this->clients[$i]->destroy();
			unset($this->clients[$i]);			
		}

		/*!	@function	trigger_hooks
			@abstract	Triggers Hooks for a certain command.
			@param		string	- Command who's hooks you want to trigger.
			@param		object	- The client who activated this command.
			@param		string	- The input from the client, or a message to be sent to the hooks.
			@result		void
		*/
		public function trigger_hooks($command,&$client,$input)
		{
			if(isset($this->hooks[$command]))
			{
				foreach($this->hooks[$command] as $function)
				{
					SocketServer::debug("Triggering Hook '{$function}' for '{$command}'");
					$continue = call_user_func($function,$this,$client,$input);
					if($continue === FALSE) { break; }
				}
			}
		}

		/*!	@function	infinite_loop
			@abstract	Runs the server code until the server is shut down.
			@see		loop_once
			@param		void
			@result		void
		*/
		public function infinite_loop()
		{
			$test = true;
			do
			{
				$test = $this->loop_once();
			}
			while($test);
		}

		/*!	@function	debug
			@static
			@abstract	Outputs Text directly.
			@discussion	Yeah, should probably make a way to turn this off.
			@param		string	- Text to Output
			@result		void
		*/
		public static function debug($text)
		{
			echo("{$text}\r\n");
		}

		/*!	@function	socket_write_smart
			@static
			@abstract	Writes data to the socket, including the length of the data, and ends it with a CRLF unless specified.
			@discussion	It is perfectly valid for socket_write_smart to return zero which means no bytes have been written. Be sure to use the === operator to check for FALSE in case of an error. 
			@param		resource- Socket Instance
			@param		string	- Data to write to the socket.
			@param		string	- Data to end the line with.  Specify a "" if you don't want a line end sent.
			@result		mixed	- Returns the number of bytes successfully written to the socket or FALSE on failure. The error code can be retrieved with socket_last_error(). This code may be passed to socket_strerror() to get a textual explanation of the error.
		*/
		public static function socket_write_smart(&$sock,$string,$crlf = "\r\n")
		{
			SocketServer::debug("<-- {$string}");
			if($crlf) { $string = "{$string}{$crlf}"; }
			return socket_write($sock,$string,strlen($string));
		}

		/*!	@function	__get
			@abstract	Magic Method used for allowing the reading of protected variables.
			@discussion	You never need to use this method, simply calling $server->variable works because of this method's existence.
			@param		string	- Variable to retrieve
			@result		mixed	- Returns the reference to the variable called.
		*/
		function &__get($name)
		{
			return $this->{$name};
		}
	}

	/*!	@class		SocketServerClient
		@author		Navarr Barnier
		@abstract	A Client Instance for use with SocketServer
	 */
	class SocketServerClient
	{
		/*!	@var		socket
			@abstract	resource - The client's socket resource, for sending and receiving data with.
		 */
		protected $socket;

		/*!	@var		ip
			@abstract	string - The client's IP address, as seen by the server.
		 */
		protected $ip;

		/*!	@var		hostname
			@abstract	string - The client's hostname, as seen by the server.
			@discussion	This variable is only set after calling lookup_hostname, as hostname lookups can take up a decent amount of time.
			@see		lookup_hostname
		 */
		protected $hostname;

		/*!	@var		server_clients_index
			@abstract	int - The index of this client in the SocketServer's client array.
		 */
		protected $server_clients_index;

		/*!	@function	__construct
			@param		resource- The resource of the socket the client is connecting by, generally the master socket.
			@param		int	- The Index in the Server's client array.
			@result		void
		 */
		public function __construct(&$socket,$i)
		{
			$this->server_clients_index = $i;
			$this->socket = socket_accept($socket) or die("Failed to Accept");
			SocketServer::debug("New Client Connected");
			socket_getpeername($this->socket,$ip);
			$this->ip = $ip;
		}
		
		/*!	@function	lookup_hostname
			@abstract	Searches for the user's hostname and stores the result to hostname.
			@see		hostname
			@param		void
			@result		string	- The hostname on success or the IP address on failure.
		 */
		public function lookup_hostname()
		{
			$this->hostname = gethostbyaddr($this->ip);
			return $this->hostname;
		}

		/*!	@function	destroy
			@abstract	Closes the socket.  Thats pretty much it.
			@param		void
			@result		void
		 */
		public function destroy()
		{
			socket_close($this->socket);
		}

		function &__get($name)
		{
			return $this->{$name};
		}
		
		function __isset($name)
		{
			return isset($this->{$name});
		}
	}
