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

The server runs in port `6090`, and each Music Server created will run on a
consecutive port. This means that your first Music Server will run in port
`6091`. The server contains a variety of endpoints (and a WebSocket) used by the
Loxone Miniserver and the UI to communicate with it. Other endpoints worth
noting are:

- `/restart`: useful for restarting the service. Accepts a `code` parameter
  through query string, with the following values:

  - `0`: the service finishes cleanly.
  - `254`: immediately restart the service.

  Any other code will restart the service after 5 seconds.

## Communication

Music Servers expect to get and receive data via UDP, through the same address
specified for TCP and Loxone Miniserver communication (i.e. in the case of the
first Music Server, that will be `6091`). This data is key to get actions
executed in the interface and to push data back to it.

### Data sent to Loxone Miniserver

All data is sent in the form of `<ID>::<COMMAND>::<ARGS>` (where `<ARGS>`
are also `::` separated). `<ID>` references the player executing the action,
`<COMMAND>` references the action performend, and `<ARGS>` will contain
attributes related to the command (if any). The following commands are
supported:

- `play`: Music started playing. It is also sent when resuming the play.

- `pause`: Music was paused.

- `volume::<VOLUME>`: the volume was modified to the new value provided. Values
  go from `0` (muted) to `100` (highest possible volume).

- `queueIndex::<QUEUE_INDEX>`: the index of the queue (e.g. playlist) was
  modified. Indices are 0-based, meaning the first song has an index of `0`.

- `time::<TIME>`: used when seeking, to indicate the new time from which we
  want to play.

### Data expected to be received by the Music Server

- `setTitle::<TITLE>`: sets the title of the song being played. This is used in
  multiple places of the UI.

- `setAlbum::<ALBUM>`: sets the album name.

- `setArtist::<ARTIST>`: sets the artist(s) name(s).

- `setCover::<COVER>`: sets the URL of the cover to be shown. Any HTTP or HTTPS
  URL is valid. This is not required, but covers are shown in multiple places
  of the UI.

- `setTime::<TIME>`: used to set internally the current time. This is not
  required, but it helps to keep the time shown in the UI accurately synced
  with the internal player time.

## Prior art

This plugin is based on the following elements:

- [An image posted in Loxforum](https://www.loxforum.com/forum/german/software-konfiguration-programm-und-visualisierung/23597-musikserver-protokoll?p=41938#post41938)
- [Loxone Music Server update](http://mediaupdate.loxone.com/updates/update022.tgz)
  (manually read the `/bin/lws` binary as a text file)
- Loxone Web Interface
