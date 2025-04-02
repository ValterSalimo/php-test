<?php

// Add swagger UI routes
$router->addRoute('GET', '/swagger', 'swagger.controller', 'showUI', false);
$router->addRoute('GET', '/swagger.json', 'swagger.controller', 'getJson', false);
