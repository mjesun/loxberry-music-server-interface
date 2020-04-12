# LoxBerry Plugin - Music Server Interface

This plugin emulates one music server into the LoxBerry, providing a way to get
the actions that happen in the UI back into the Loxone Miniserver.

## Motivation

There are multiple solutions out there to create your own music server,
generally using a Logitech Media Server (LMS) and Squeezelite - which is a
setup that copies the original Loxone Music Server. However, when visualizing
the data coming from it, the recommendation is to feed the data back to the UI
via virutal outputs, and emulate the UI. While this is a great overcoming of
the limitations, being able to use the native UI exposed by Loxone seems to be
the best. This plugin thus enables Loxone to use their specific UI for music
presentation into their applications.

## Code structure

Code is architected following the conventions proposed by the LoxBerry project.
The code is divided into the following folders:

- `bin`: where the service lives. The service is made in JavaScript (Node.js).
  service runs as a standalone server, and self restarts if something wrong
  happens.

- `config`: configuration file, read and written from the PHP frontend and from
  the JS code.

- `daemon`: contains the initialization code that is run by the LoxBerry at
  startup, and will bring the service to live.

- `icons` and `webfrontend`: contains the code used by the LoxBerry to show the
  configuration pages of the plugin under the web UI. They are developed in PHP
  and made generic enough so that just by editing the configuration file
  (`data.cfg`), new fields will appear.

## Service endpoints

The server runs in port `7091`, which is the default port used by Loxone Music
Server. The server contains a variety of endpoints (and a WebSocket) used by
the Loxone Miniserver and the UI to communicate with it. Other endpoints worth
noting are:

- `/restart`: useful for restarting the service. Accepts a `code` parameter
  through query string, with the following values:

  - `0`: the service finishes cleanly.
  - `254`: immediately restart the service.

  Any other code will restart the service after 5 seconds.
