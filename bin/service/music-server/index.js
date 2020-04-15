'use strict';

const dgram = require('dgram');
const http = require('http');
const querystring = require('querystring');
const websocket = require('websocket');

const MusicZone = require('./music-zone');

const headers = {
  'Content-Type': 'text/plain; charset=utf-8',
};

module.exports = class MusicServer {
  constructor(config) {
    const zones = [];

    for (let i = 0; i < config.zones; i++) {
      zones[i] = new MusicZone(this, i + 1);
    }

    this._config = config;
    this._zones = zones;

    this._wsConnections = new Set();
    this._miniserverIp = null;
  }

  start() {
    if (this._httpServer || this._wsServer || this._dgramServer) {
      throw new Error('Music server already started');
    }

    const dgramServer = dgram.createSocket('udp4', async (message) => {
      console.log('[DGRM] Received message: ' + message);

      const [zoneId, command, ...args] = message.toString().split('::');
      const zone = this._zones[+zoneId - 1];

      if (typeof zone[command] === 'function' && command.match(/^[a-z]+$/i)) {
        zone[command](...args);
      } else {
        console.warn('[DGRM] Unknown command: ' + command);
      }
    });

    const httpServer = http.createServer(async (req, res) => {
      console.log('[HTTP] Received message: ' + req.url);

      try {
        res.writeHead(200, headers);
        res.end(await this._handler(req.url));
      } catch (err) {
        res.writeHead(500, headers);
        res.end(err.stack);
      }
    });

    const wsServer = new websocket.server({
      httpServer,
      autoAcceptConnections: true,
    });

    wsServer.on('connect', (connection) => {
      this._wsConnections.add(connection);

      connection.on('message', async (message) => {
        console.log('[WSCK] Received message: ' + message.utf8Data);

        if (message.type !== 'utf8') {
          throw new Error('Unknown message type: ' + message.type);
        }

        connection.sendUTF(await this._handler(message.utf8Data));
      });

      connection.on('close', () => {
        this._wsConnections.delete(connection);
      });

      connection.send('LWSS V 2.3.9.2 | ~API:1.6~');
    });

    dgramServer.bind(this._config.port);
    httpServer.listen(this._config.port);

    setInterval(this._sendAudioEvents.bind(this, this._zones), 4000);

    this._dgramServer = dgramServer;
    this._httpServer = httpServer;
    this._wsServer = wsServer;
  }

  pushPlayerState(id, command, args) {
    const miniserverIp = this._miniserverIp;

    if (miniserverIp) {
      const message = [].concat(id, command, args).join('::');
      const client = dgram.createSocket('udp4');

      client.send(message, this._config.port, miniserverIp, () => {
        client.close();
      });
    }
  }

  pushAudioEvent(id) {
    this._sendAudioEvents([this._zones[id - 1]]);
  }

  _sendAudioEvents(zones) {
    const audioEvents = zones.map((zone) => {
      return zone.audioState();
    });

    const message = JSON.stringify({
      audio_event: audioEvents,
    });

    this._wsConnections.forEach((connection) => {
      connection.send(message);
    });
  }

  _handler(method) {
    const index = method.indexOf('?');
    const url = index === -1 ? method : method.substr(0, index);
    const query = querystring.parse(method.substr(url.length + 1));

    switch (true) {
      case /(?:^|\/)audio\/cfg\/all(?:\/|$)/.test(url):
        return this._audioCfgAll(url);

      case /(?:^|\/)audio\/cfg\/equalizer\//.test(url):
        return this._audioCfgEqualizer(url);

      case /(?:^|\/)audio\/cfg\/getfavorites\//.test(url):
        return this._emptyCommand(url, []);

      case /(?:^|\/)audio\/cfg\/getinputs(?:\/|$)/.test(url):
        return this._emptyCommand(url, []);

      case /(?:^|\/)audio\/cfg\/get(?:paired)?master(?:\/|$)/.test(url):
        return this._audioCfgGetMaster(url);

      case /(?:^|\/)audio\/cfg\/getplayersdetails(?:\/|$)/.test(url):
        return this._audioCfgGetPlayersDetails(url);

      case /(?:^|\/)audio\/cfg\/getradios(?:\/|$)/.test(url):
        return this._emptyCommand(url, []);

      case /(?:^|\/)audio\/cfg\/getroomfavs\//.test(url):
        return this._audioCfgGetRoomFavs(url);

      case /(?:^|\/)audio\/cfg\/getservices(?:\/|$)/.test(url):
        return this._emptyCommand(url, []);

      case /(?:^|\/)audio\/cfg\/getsyncedplayers(?:\/|$)/.test(url):
        return this._audioCfgGetSyncedPlayers(url);

      case /(?:^|\/)audio\/cfg\/iamaminiserver\//.test(url):
        return this._audioCfgIAmAMiniserver(url);

      case /(?:^|\/)audio\/cfg\/mac(?:\/|$)/.test(url):
        return this._audioCfgMac(url);

      case /(?:^|\/)audio\/\d+\/pause(?:\/|$)/.test(url):
        return this._audioPause(url);

      case /(?:^|\/)audio\/\d+\/(?:play|resume)(?:\/|$)/.test(url):
        return this._audioPlay(url);

      case /(?:^|\/)audio\/\d+\/position\/\d+(?:\/|$)/.test(url):
        return this._audioPosition(url);

      case /(?:^|\/)audio\/\d+\/queueminus(?:\/|$)/.test(url):
        return this._audioQueueMinus(url);

      case /(?:^|\/)audio\/\d+\/queueplus(?:\/|$)/.test(url):
        return this._audioQueuePlus(url);

      case /(?:^|\/)audio\/\d+\/repeat\/\d+(?:\/|$)/.test(url):
        return this._audioRepeat(url);

      case /(?:^|\/)audio\/\d+\/shuffle\/\d+(?:\/|$)/.test(url):
        return this._audioShuffle(url);

      case /(?:^|\/)audio\/\d+\/volume\/[+-]?\d+(?:\/|$)/.test(url):
        return this._audioVolume(url);

      default:
        return this._unknownCommand(url);
    }
  }

  _audioCfgAll(url) {
    return this._response(url, 'configall', [
      {
        airplay: false,
        dns: '8.8.8.8',
        errortts: false,
        gateway: '0.0.0.0',
        hostname: 'loxberry-music-server-' + this._config.port,
        ip: '0.255.255.255',
        language: 'en',
        lastconfig: '',
        macaddress: this._mac(),
        mask: '255.255.255.255',
        master: true,
        maxplayers: this._config.players,
        ntp: '0.europe.pool.ntp.org',
        upnplicences: 0,
        usetrigger: false,
        players: this._zones.map((zone, i) => ({
          playerid: i + 1,
          clienttype: 0,
          default_volume: zone.getVolume(),
          enabled: true,
          internalname: 'zone-' + (i + 1),
          max_volume: 100,
          name: 'Zone ' + (i + 1),
          upnpmode: 0,
          upnppredelay: 0,
        })),
      },
    ]);
  }

  _audioCfgEqualizer(url) {
    const playerId = +url.split('/').pop();

    return this._response(url, 'equalizer', [
      {
        playerid: playerId,
        equalizer: 'default',
      },
    ]);
  }

  _audioCfgGetMaster(url) {
    return JSON.stringify(url, url.split('/').pop(), null);
  }

  _audioCfgGetPlayersDetails(url) {
    const audioStates = this._zones.map((zone, i) => {
      return zone.audioState();
    });

    return this._response(url, 'getplayersdetails', audioStates);
  }

  _audioCfgGetRoomFavs(url) {
    return this._response(url, 'getroomfavs', []);
  }

  _audioCfgGetSyncedPlayers(url) {
    return this._emptyCommand(url, []);
  }

  _audioCfgIAmAMiniserver(url) {
    this._miniserverIp = url.split('/').pop();

    return this._response(url, 'iamamusicserver', {
      iamamusicserver: 'i love miniservers!',
    });
  }

  _audioCfgMac(url) {
    return this._response(url, 'mac', {
      macaddress: this._mac(),
    });
  }

  _audioPause(url) {
    const [, zoneId, , volume] = url.split('/');
    const zone = this._zones[+zoneId - 1];

    zone.setMode('pause');

    return this._audioCfgGetPlayersDetails('audio/cfg/getplayersdetails');
  }

  _audioPlay(url) {
    const [, zoneId, , volume] = url.split('/');
    const zone = this._zones[+zoneId - 1];

    zone.setMode('play');

    return this._audioCfgGetPlayersDetails('audio/cfg/getplayersdetails');
  }

  _audioPosition(url) {
    const [, zoneId, , position] = url.split('/');
    const zone = this._zones[+zoneId - 1];

    zone.setPosition(+position);

    return this._audioCfgGetPlayersDetails('audio/cfg/getplayersdetails');
  }

  _audioQueueMinus(url) {
    const [, zoneId] = url.split('/');
    const zone = this._zones[+zoneId - 1];

    zone.setQueueIndex(zone.getQueueIndex() - 1);

    return this._audioCfgGetPlayersDetails('audio/cfg/getplayersdetails');
  }

  _audioQueuePlus(url) {
    const [, zoneId] = url.split('/');
    const zone = this._zones[+zoneId - 1];

    zone.setQueueIndex(zone.getQueueIndex() + 1);

    return this._audioCfgGetPlayersDetails('audio/cfg/getplayersdetails');
  }

  _audioRepeat(url) {
    const [, zoneId, , repeatMode] = url.split('/');
    const zone = this._zones[+zoneId - 1];
    const repeatModes = {0: 0, 1: 2, 3: 1};

    zone.setRepeat(repeatModes[repeatMode]);

    return this._audioCfgGetPlayersDetails('audio/cfg/getplayersdetails');
  }

  _audioShuffle(url) {
    const [, zoneId, , shuffle] = url.split('/');
    const zone = this._zones[+zoneId - 1];

    zone.setShuffle(+shuffle);

    return this._audioCfgGetPlayersDetails('audio/cfg/getplayersdetails');
  }

  _audioVolume(url) {
    const [, zoneId, , volume] = url.split('/');
    const zone = this._zones[+zoneId - 1];

    if (/^[+-]/.test(volume)) {
      zone.setVolume(zone.getVolume() + +volume);
    } else {
      zone.setVolume(+volume);
    }

    return this._audioCfgGetPlayersDetails('audio/cfg/getplayersdetails');
  }

  _emptyCommand(url, response) {
    const parts = url.split('/');

    for (let i = parts.length; i--; ) {
      if (/^[a-z]/.test(parts[i])) {
        return this._response(url, parts[i], response);
      }
    }
  }

  _unknownCommand(url) {
    console.warn('[HTWS] Unknown command: ' + url);

    return this._emptyCommand(url, null);
  }

  _response(url, name, result) {
    const message = {
      [name + '_result']: result,
      command: url,
    };

    return JSON.stringify(message, null, 2);
  }

  _mac() {
    const portAsMacAddress = (this._config.port / 256)
      .toString(16)
      .replace('.', ':')
      .padStart(5, '0');

    return '50:4f:94:ff:' + portAsMacAddress;
  }
};
