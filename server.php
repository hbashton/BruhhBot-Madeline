<?php
require_once 'SocketServer.class.php'; // Include the File
$PORT = getenv("PORT");
$server = new SocketServer("0.0.0.0", $PORT); // Create a Server binding to the given ip address and listen to port 31337 for connections
$server->max_clients = 10; // Allow no more than 10 people to connect at a time
$server->hook("CONNECT","handle_connect"); // Run handle_connect every time someone connects
$server->hook("INPUT","handle_input"); // Run handle_input whenever text is sent to the server
$server->infinite_loop(); // Run Server Code Until Process is terminated.

