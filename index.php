<?php

require 'vendor/autoload.php';
include 'bootstrap.php';

use Event\Models\Event;
use Event\Middleware\Logging as EventLogging;
use Event\Middleware\Authentication as EventAuth;
use Event\Middleware\FileFilter;
use Event\Middleware\FileMove;
use Event\Middleware\ImageRemoveExif;

$app = new \Slim\App();
$app->add(new EventAuth());
$app->add(new EventLogging());

$app->group('/v1' , function() {
    $app->group('/events', function () {
        $this->map(['GET'], '', function ($request, $response, $args) {
            $_event = new Event();

            $events = $_event->all();

            $payload = [];
            foreach($events as $_msg) {
                $payload[$_msg->id] = $_msg->output();
            }

            return $response->withStatus(200)->withJson($payload);
        })->setName('get_events');
    });
});

$app->group('/v2' , function() {
    $app->group('/events', function () {
        $this->map(['GET'], '', function ($request, $response, $args) {
            $_event = new Event();

            $events = $_event->all();

            $payload = [];
            foreach($events as $_msg) {
                $payload[$_msg->id] = $_msg->output();
            }

            return $response->withStatus(200)->withJson($payload);
        })->setName('get_events');
    });
});

    $filter = new FileFilter();
    $removeExif = new ImageRemoveExif();
    $move   = new FileMove();
    
    $this->map(['POST'], '', function ($request, $response, $args) {
        $_event = $request->getParsedBodyParam('event', '');

        $event = new Event();
        $event->body = $_event;
        $event->user_id = $request->getAttribute('user_id');
        $event->image_url = $request->getAttribute('filename');
        $event->save();

        if ($event->id) {
            $payload = ['event_id' => $event->id,
                'event_uri' => '/events/' . $event->id,
                'image_url' => $event->image_url
            ];
            return $response->withStatus(201)->withJson($payload);
        } else {
            return $response->withStatus(400);
        }
    })->add($filter)->add($removeExif)->add($move)->setName('create_events');

    $this->delete('/{event_id}', function ($request, $response, $args) {
        $event = Event::find($args['event_id']);
        $event->delete();

        if ($event->exists) {
            return $response->withStatus(400);
        } else {
            return $response->withStatus(204);
        }
    })->setName('delete_events');

// Run app
$app->run();